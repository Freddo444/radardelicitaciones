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
    public function __construct(
        private readonly SobreBinder $binder,
    ) {}

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

    /**
     * Generate Sobre A, Sobre B, and Sobre Único (U) ZIP packages.
     * Each ZIP contains all requirement-item files converted to PDF.
     */
    public function assembleSobres(Offer $offer): array
    {
        $offer->load('activeRequirements.items', 'company');

        $sobreDir = storage_path('app/generated/sobres');
        if (! is_dir($sobreDir)) {
            mkdir($sobreDir, 0755, true);
        }

        $pdfCacheDir = storage_path('app/generated/pdf_cache');
        if (! is_dir($pdfCacheDir)) {
            mkdir($pdfCacheDir, 0755, true);
        }

        $result = [];
        $code = preg_replace('/[^A-Za-z0-9_\-]/', '_', $offer->proceso_codigo ?? 'oferta');

        foreach (['A', 'B', 'U'] as $sobre) {
            $reqs = $offer->activeRequirements->where('sobre', $sobre);
            if ($reqs->isEmpty()) {
                continue;
            }

            $files = $this->collectSobreFiles($reqs);
            if (empty($files)) {
                continue;
            }

            // Convert each file to PDF and collect paths, preserving order.
            $pdfPaths = [];
            foreach ($files as $file) {
                $pdf = $this->ensurePdf($file['full_path'], $pdfCacheDir);
                if ($pdf) {
                    $pdfPaths[] = ['path' => $pdf, 'label' => $file['label']];
                }
            }

            if (empty($pdfPaths)) {
                continue;
            }

            // Bind into a single branded, paginated PDF (cover + index +
            // separators + documents with foliado).
            $boundPath = "{$sobreDir}/Sobre {$sobre}-{$code}.pdf";
            $bound = $this->binder->bind($offer, $sobre, $pdfPaths, $boundPath);

            if ($bound) {
                $result[$sobre] = $bound;
            }
        }

        return $result;
    }

    // ── Private ───────────────────────────────────────────────────────

    private function collectSobreFiles($requirements): array
    {
        $files = [];

        foreach ($requirements as $req) {
            foreach ($req->items as $item) {
                if ($item->vault_ref_type === 'vault_documents') {
                    $doc = VaultDocument::find($item->vault_ref_id);
                    if ($doc?->path) {
                        $fullPath = Storage::disk('vault')->path($doc->path);
                        if (file_exists($fullPath)) {
                            $files[] = [
                                'full_path' => $fullPath,
                                'label' => $doc->name ?? basename($doc->path),
                            ];
                        }
                    }
                } elseif ($item->vault_ref_type === 'offer_generated_files') {
                    $gen = OfferGeneratedFile::find($item->vault_ref_id);
                    if ($gen?->path) {
                        $fullPath = storage_path('app/'.$gen->path);
                        if (file_exists($fullPath)) {
                            $files[] = [
                                'full_path' => $fullPath,
                                'label' => OfferGeneratedFile::$forms[$gen->form_code] ?? $gen->form_code,
                            ];
                        }
                    }
                }
            }
        }

        return $files;
    }

    private function ensurePdf(string $filePath, string $cacheDir): ?string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Already PDF
        if ($ext === 'pdf') {
            return $filePath;
        }

        $hash = md5($filePath.filemtime($filePath));
        $pdfPath = "{$cacheDir}/{$hash}.pdf";

        if (file_exists($pdfPath)) {
            return $pdfPath;
        }

        // Convert with LibreOffice
        if (in_array($ext, ['docx', 'doc', 'xlsx', 'xls', 'pptx', 'ppt', 'odt', 'ods'])) {
            $cmd = sprintf(
                'HOME=/var/www soffice --headless --convert-to pdf --outdir %s %s 2>&1',
                escapeshellarg($cacheDir),
                escapeshellarg($filePath)
            );
            exec($cmd, $output, $exitCode);

            // LibreOffice outputs with original filename
            $convertedName = pathinfo($filePath, PATHINFO_FILENAME).'.pdf';
            $convertedPath = "{$cacheDir}/{$convertedName}";

            if ($exitCode === 0 && file_exists($convertedPath)) {
                // Rename to hash-based name to avoid collisions
                rename($convertedPath, $pdfPath);

                return $pdfPath;
            }

            return null;
        }

        // Images → convert with LibreOffice as fallback
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'tiff'])) {
            $cmd = sprintf(
                'HOME=/var/www soffice --headless --convert-to pdf --outdir %s %s 2>&1',
                escapeshellarg($cacheDir),
                escapeshellarg($filePath)
            );
            exec($cmd, $output, $exitCode);

            $convertedName = pathinfo($filePath, PATHINFO_FILENAME).'.pdf';
            $convertedPath = "{$cacheDir}/{$convertedName}";

            if ($exitCode === 0 && file_exists($convertedPath)) {
                rename($convertedPath, $pdfPath);

                return $pdfPath;
            }

            return null;
        }

        // Unsupported format — include as-is won't work, skip
        return null;
    }

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
