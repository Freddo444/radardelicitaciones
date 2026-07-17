<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Offer;
use App\Support\SobreTheme;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Assembles the per-document PDFs of a sobre into a single, branded, paginated
 * PDF: aesthetic cover → index → (separator + document)* with consecutive page
 * numbering (foliado) stamped on every content page.
 *
 * Cover / index / separator pages are rendered from themed Blade templates via
 * dompdf; the merge + foliado is done with FPDI on top of TCPDF. Documents are
 * normalized so FPDI can always import them regardless of PDF version.
 */
class SobreBinder
{
    private static array $labels = [
        'A' => 'Sobre A',
        'B' => 'Sobre B',
        'U' => 'Sobre Único',
    ];

    /**
     * @param  array<int, array{path:string, label:string}>  $documents  already-PDF docs, in order
     * @return string|null absolute path to the bound PDF, or null if nothing to bind
     */
    public function bind(Offer $offer, string $sobre, array $documents, string $outputPath): ?string
    {
        if (empty($documents)) {
            return null;
        }

        $company = $offer->company;
        $theme = SobreTheme::resolve($company?->sobre_theme, $company?->sobre_accent_color);
        $sobreLabel = self::$labels[$sobre] ?? "Sobre {$sobre}";

        $proceso = [
            'codigo' => $offer->proceso_codigo ?: 'N/D',
            'nombre' => $offer->proceso_nombre ?: 'Proceso de contratación',
            'entidad' => $offer->entidad_nombre ?: '—',
        ];

        $tmp = [];
        try {
            // 1. Normalize each document so FPDI can import it, and record page counts.
            $normalized = [];
            foreach ($documents as $doc) {
                $norm = $this->normalizeForFpdi($doc['path'], $tmp);
                if ($norm === null) {
                    Log::warning('[SobreBinder] skipped unreadable document', ['label' => $doc['label']]);

                    continue;
                }
                $normalized[] = ['path' => $norm, 'label' => $doc['label'], 'pages' => $this->pageCount($norm)];
            }

            if (empty($normalized)) {
                return null;
            }

            // 2. Render cover.
            $coverPath = $this->renderView('pdf.sobre.cover', [
                't' => $theme,
                'company' => $company,
                'sobre' => $sobreLabel,
                'proceso' => $proceso,
                'fecha' => now()->isoFormat('D [de] MMMM, YYYY'),
                'logo' => $this->logoDataUri($company),
                'footer' => $this->footerLine($company),
            ], $tmp);
            $coverPages = $this->pageCount($coverPath);

            // 3. Render the index once WITHOUT page numbers to learn its own length.
            $indexEntries = array_map(fn ($n) => ['label' => $n['label'], 'page' => null], $normalized);
            $indexProbe = $this->renderView('pdf.sobre.index', [
                't' => $theme, 'company' => $company, 'sobre' => $sobreLabel,
                'proceso' => $proceso, 'entries' => $indexEntries,
            ], $tmp);
            $indexPages = $this->pageCount($indexProbe);

            // 4. Compute the starting folio of each document in the final PDF.
            //    Layout: [cover][index][sep,doc][sep,doc]...  (1 separator page each).
            //    The index references the FOLIADO number (the cover is unfoliated,
            //    so folio 1 is the index page) — it must match the stamped footer.
            $cursor = $coverPages + $indexPages;
            foreach ($normalized as $i => &$n) {
                $cursor += 1;                                      // separator page
                $indexEntries[$i]['page'] = ($cursor + 1) - $coverPages; // doc's first page, as foliado
                $cursor += $n['pages'];                            // document pages
            }
            unset($n);

            // 5. Re-render the index WITH resolved page numbers.
            $indexPath = $this->renderView('pdf.sobre.index', [
                't' => $theme, 'company' => $company, 'sobre' => $sobreLabel,
                'proceso' => $proceso, 'entries' => $indexEntries,
            ], $tmp);

            // 6. Render one separator per document.
            $separators = [];
            foreach ($normalized as $i => $n) {
                $separators[] = $this->renderView('pdf.sobre.separator', [
                    't' => $theme, 'company' => $company, 'sobre' => $sobreLabel,
                    'proceso' => $proceso, 'number' => $i + 1, 'label' => $n['label'],
                ], $tmp);
            }

            // 7. Merge everything and stamp foliado.
            $this->mergeAndFoliar(
                $outputPath,
                $coverPath,
                $indexPath,
                $separators,
                $normalized,
                $coverPages,
                $theme,
                $sobreLabel,
                $proceso['codigo'],
            );

            return $outputPath;
        } finally {
            foreach ($tmp as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * FPDI import of every source PDF onto fresh TCPDF pages, with a foliado
     * footer stamped on all pages after the cover.
     *
     * @param  array<int, string>  $separators
     * @param  array<int, array{path:string, label:string, pages:int}>  $documents
     */
    private function mergeAndFoliar(
        string $outputPath,
        string $coverPath,
        string $indexPath,
        array $separators,
        array $documents,
        int $coverPages,
        array $theme,
        string $sobreLabel,
        string $procesoCodigo,
    ): void {
        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pdf->setMargins(0, 0, 0);

        // Height of the footer band reserved for the foliado (mm). Imported
        // document pages are scaled down to leave this band clear so our stamp
        // never collides with the document's own footer.
        $footerBand = 12.0;

        // Ordered list of source files to concatenate, flagged by whether the
        // page is an imported document (needs a reserved footer band) or one of
        // our own generated pages (cover/index/separator — already margined).
        $sources = [
            ['path' => $coverPath, 'is_document' => false],
            ['path' => $indexPath, 'is_document' => false],
        ];
        foreach ($documents as $i => $doc) {
            $sources[] = ['path' => $separators[$i], 'is_document' => false];
            $sources[] = ['path' => $doc['path'], 'is_document' => true];
        }

        // First pass counts total pages for the "de N" foliado.
        $totalPages = 0;
        foreach ($sources as $src) {
            $totalPages += $this->pageCount($src['path']);
        }
        $totalContent = max(0, $totalPages - $coverPages);

        $folio = 0;      // consecutive content page number (post-cover)
        $absolute = 0;   // absolute page index across the whole document

        foreach ($sources as $src) {
            $count = $pdf->setSourceFile($src['path']);
            for ($p = 1; $p <= $count; $p++) {
                $absolute++;
                $tpl = $pdf->importPage($p);
                $size = $pdf->getTemplateSize($tpl);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);

                if ($src['is_document']) {
                    // Scale the page down to reserve a clear footer band, keeping
                    // it centered horizontally and top-aligned.
                    $avail = $size['height'] - $footerBand;
                    $scale = min(1.0, $avail / $size['height']);
                    $newW = $size['width'] * $scale;
                    $newH = $size['height'] * $scale;
                    $x = ($size['width'] - $newW) / 2;
                    $pdf->useTemplate($tpl, ['x' => $x, 'y' => 0, 'height' => $newH, 'adjustPageSize' => false]);
                } else {
                    $pdf->useTemplate($tpl, ['adjustPageSize' => true]);
                }

                // Cover is not foliated; everything after is.
                if ($absolute > $coverPages) {
                    $folio++;
                    $this->stampFolio($pdf, $size, $folio, $totalContent, $theme, $sobreLabel, $procesoCodigo);
                }
            }
        }

        $pdf->Output($outputPath, 'F');
    }

    private function stampFolio(Fpdi $pdf, array $size, int $folio, int $total, array $theme, string $sobreLabel, string $codigo): void
    {
        $w = $size['width'];
        $h = $size['height'];

        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetFont('helvetica', '', 8);

        // Sits in the reserved footer band, ~7mm from the bottom edge.
        $y = $h - 7;

        // Left: traceability. Right: folio.
        $pdf->SetXY(12, $y);
        $pdf->Cell(120, 5, "{$sobreLabel} · {$codigo}", 0, 0, 'L');

        $pdf->SetXY($w - 52, $y);
        $pdf->Cell(40, 5, "Página {$folio} de {$total}", 0, 0, 'R');
    }

    /**
     * Render a Blade view to a temporary PDF file via dompdf; returns its path.
     */
    private function renderView(string $view, array $data, array &$tmp): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sobrepg_').'.pdf';
        $tmp[] = $path;

        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'portrait');
        file_put_contents($path, $pdf->output());

        return $path;
    }

    /**
     * Ensure a PDF is importable by FPDI. Most PDFs pass directly; those using
     * PDF 1.5+ cross-reference streams need normalization. Prefer Ghostscript
     * (lossless), fall back to a LibreOffice round-trip.
     */
    private function normalizeForFpdi(string $pdfPath, array &$tmp): ?string
    {
        // Fast path: FPDI can already read it — no work, no quality loss.
        try {
            $probe = new Fpdi;
            $probe->setSourceFile($pdfPath);

            return $pdfPath;
        } catch (\Throwable) {
            // needs normalization
        }

        $out = tempnam(sys_get_temp_dir(), 'sobrenorm_').'.pdf';
        $tmp[] = $out;

        // Ghostscript: lossless downgrade to 1.4.
        if ($this->hasBinary('gs')) {
            $cmd = sprintf(
                'gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%s %s 2>&1',
                escapeshellarg($out),
                escapeshellarg($pdfPath),
            );
            exec($cmd, $o, $code);
            if ($code === 0 && filesize($out) > 0 && $this->fpdiReadable($out)) {
                return $out;
            }
        }

        // Fallback: LibreOffice round-trip (re-flattens to an importable version).
        $dir = dirname($out);
        $cmd = sprintf(
            'HOME=/var/www soffice --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($dir),
            escapeshellarg($pdfPath),
        );
        exec($cmd, $o2, $code2);
        $converted = $dir.'/'.pathinfo($pdfPath, PATHINFO_FILENAME).'.pdf';
        if ($code2 === 0 && file_exists($converted) && $this->fpdiReadable($converted)) {
            $tmp[] = $converted;

            return $converted;
        }

        return null;
    }

    private function fpdiReadable(string $path): bool
    {
        try {
            (new Fpdi)->setSourceFile($path);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function pageCount(string $path): int
    {
        try {
            return (new Fpdi)->setSourceFile($path);
        } catch (\Throwable) {
            return 1;
        }
    }

    private function logoDataUri(?Company $company): ?string
    {
        $path = $company?->logo_path;
        if (! $path) {
            return null;
        }

        foreach (['public', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    $bytes = Storage::disk($disk)->get($path);
                    $mime = Storage::disk($disk)->mimeType($path) ?: 'image/png';

                    return 'data:'.$mime.';base64,'.base64_encode($bytes);
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function footerLine(?Company $company): string
    {
        $parts = array_filter([
            $company?->direccion,
            $company?->municipio,
            $company?->telefono,
            $company?->email,
            $company?->web,
        ]);

        return implode('  ·  ', $parts);
    }

    private function hasBinary(string $bin): bool
    {
        exec('command -v '.escapeshellarg($bin).' 2>/dev/null', $o, $code);

        return $code === 0;
    }
}
