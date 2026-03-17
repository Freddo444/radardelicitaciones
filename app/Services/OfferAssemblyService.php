<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\OfferGeneratedFile;
use App\Models\OfferSnapshot;
use App\Models\VaultDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class OfferAssemblyService
{
    /**
     * Assemble an offer package: snapshot all data, create ZIP, return snapshot record.
     */
    public function assemble(Offer $offer): OfferSnapshot
    {
        $offer->load([
            'personnel.person',
            'projects.project',
            'equipment.equipment',
            'financials.financialRecord',
            'activeRequirements.items',
        ]);

        // ── Step 1: Freeze data snapshots ────────────────────────────
        $company = $offer->company;

        $personnelSnapshot = $offer->personnel->map(fn ($op) => [
            'offer_personnel_id' => $op->id,
            'role_note' => $op->role_note,
            'data' => $op->person?->toArray(),
        ])->toArray();

        $projectsSnapshot = $offer->projects->map(fn ($op) => [
            'offer_project_id' => $op->id,
            'role_note' => $op->role_note,
            'data' => $op->project?->toArray(),
        ])->toArray();

        $equipmentSnapshot = $offer->equipment->map(fn ($oe) => [
            'offer_equipment_id' => $oe->id,
            'role_note' => $oe->role_note,
            'data' => $oe->equipment?->toArray(),
        ])->toArray();

        $financialsSnapshot = $offer->financials->map(fn ($of) => [
            'offer_financial_id' => $of->id,
            'role_note' => $of->role_note,
            'data' => $of->financialRecord?->toArray(),
        ])->toArray();

        // Requirements + items
        $requirementsSnapshot = $offer->activeRequirements->map(fn ($r) => [
            'id' => $r->id,
            'descripcion' => $r->descripcion,
            'tipo' => $r->tipo,
            'estado' => $r->estado,
            'notes' => $r->notes,
            'items' => $r->items->map(fn ($i) => [
                'vault_ref_type' => $i->vault_ref_type,
                'vault_ref_id' => $i->vault_ref_id,
                'role_note' => $i->role_note,
                'label' => $i->refLabel(),
            ])->toArray(),
        ])->toArray();

        // File hashes for all referenced vault items
        $fileHashes = $this->collectFileHashes($offer);

        // ── Step 2: Create ZIP ────────────────────────────────────────
        [$zipPath, $zipSha256] = $this->buildZip($offer, $fileHashes);

        // ── Step 3: Persist snapshot ──────────────────────────────────
        return OfferSnapshot::create([
            'offer_id' => $offer->id,
            'company_snapshot' => $company->toArray(),
            'personnel_snapshot' => $personnelSnapshot,
            'projects_snapshot' => $projectsSnapshot,
            'equipment_snapshot' => $equipmentSnapshot,
            'financials_snapshot' => $financialsSnapshot,
            'requirements_snapshot' => $requirementsSnapshot,
            'file_hashes' => $fileHashes,
            'zip_path' => $zipPath,
            'zip_sha256' => $zipSha256,
            'assembled_at' => now(),
            'assembled_by' => Auth::id(),
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────

    private function collectFileHashes(Offer $offer): array
    {
        $hashes = [];

        foreach ($offer->activeRequirements as $req) {
            foreach ($req->items as $item) {
                $path = null;

                if ($item->vault_ref_type === 'vault_documents') {
                    $doc = VaultDocument::find($item->vault_ref_id);
                    if ($doc?->path) {
                        $fullPath = Storage::disk('vault')->path($doc->path);
                        $path = $doc->path;
                        if (file_exists($fullPath)) {
                            $hashes[] = [
                                'type' => 'vault_document',
                                'id' => $item->vault_ref_id,
                                'path' => $path,
                                'sha256' => hash_file('sha256', $fullPath),
                                'label' => $doc->name ?? $doc->nombre ?? '',
                            ];
                        }
                    }
                } elseif ($item->vault_ref_type === 'offer_generated_files') {
                    $gen = OfferGeneratedFile::find($item->vault_ref_id);
                    if ($gen?->path) {
                        $fullPath = storage_path('app/'.$gen->path);
                        if (file_exists($fullPath)) {
                            $hashes[] = [
                                'type' => 'generated_file',
                                'id' => $item->vault_ref_id,
                                'path' => $gen->path,
                                'sha256' => $gen->sha256,
                                'label' => $gen->form_code,
                            ];
                        }
                    }
                }
            }
        }

        return $hashes;
    }

    private function buildZip(Offer $offer, array $fileHashes): array
    {
        $slug = implode('_', array_filter([
            $offer->proceso_codigo ?? 'oferta',
            now()->format('Ymd_His'),
        ]));
        $zipDir = storage_path('app/generated');
        $zipPath = "{$zipDir}/{$slug}.zip";
        $relative = "generated/{$slug}.zip";

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Add vault documents
        foreach ($fileHashes as $entry) {
            if ($entry['type'] === 'vault_document') {
                $fullPath = Storage::disk('vault')->path($entry['path']);
                if (file_exists($fullPath)) {
                    $zipName = 'documentos/'.basename($entry['path']);
                    $zip->addFile($fullPath, $zipName);
                }
            } elseif ($entry['type'] === 'generated_file') {
                $fullPath = storage_path('app/'.$entry['path']);
                if (file_exists($fullPath)) {
                    $zipName = 'formularios/'.$entry['label'].'_'.basename($entry['path']);
                    $zip->addFile($fullPath, $zipName);
                }
            }
        }

        // Add a manifest
        $manifest = $this->buildManifest($offer, $fileHashes);
        $zip->addFromString('MANIFIESTO.txt', $manifest);

        $zip->close();

        $sha256 = file_exists($zipPath) ? hash_file('sha256', $zipPath) : '';

        return [$relative, $sha256];
    }

    private function buildManifest(Offer $offer, array $fileHashes): string
    {
        $lines = [
            'OFERTA — '.($offer->proceso_codigo ?? 'Sin código'),
            $offer->proceso_nombre ?? '',
            'Entidad: '.($offer->entidad_nombre ?? ''),
            'Ensamblado: '.now()->format('d/m/Y H:i:s'),
            '',
            '── DOCUMENTOS INCLUIDOS ─────────────────────────────',
        ];

        foreach ($fileHashes as $entry) {
            $lines[] = sprintf('%-50s %s', $entry['label'], substr($entry['sha256'], 0, 12).'...');
        }

        $lines[] = '';
        $lines[] = '── FIN DEL MANIFIESTO ───────────────────────────────';

        return implode("\n", $lines);
    }
}
