<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validate an uploaded PDF.
 *
 * Laravel's built-in `mimes:pdf` rule uses PHP's finfo under the hood, which
 * sometimes mis-detects scanner-produced or re-exported PDFs as
 * application/octet-stream and rejects them. This rule sidesteps MIME-sniff
 * fragility by checking the file's actual magic bytes — every PDF file
 * starts with the literal ASCII string "%PDF-" per the PDF spec.
 */
class PdfFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('El archivo no es válido.');

            return;
        }

        if (strtolower($value->getClientOriginalExtension()) !== 'pdf') {
            $fail('El archivo debe tener extensión .pdf.');

            return;
        }

        $handle = @fopen($value->getRealPath(), 'rb');
        if ($handle === false) {
            $fail('No se pudo leer el archivo cargado.');

            return;
        }
        $header = @fread($handle, 5);
        @fclose($handle);

        if ($header !== '%PDF-') {
            $fail('El archivo no parece ser un PDF válido. Si lo abrió en otro programa antes de subirlo, intente exportarlo nuevamente como PDF.');
        }
    }
}
