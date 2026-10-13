<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Structural check on a Dominican RNC.
 *
 * Deliberately only structural. The DGCP registry can lag a genuinely new
 * business by months, so absence from it cannot be grounds for refusal —
 * that lives in DgcpProviderService::isRegistered(), which records a flag
 * rather than blocking.
 *
 * Length is the only lag-free discriminator that is safe. Checked against 500
 * live DGCP registrations: every one is 9 digits (a company RNC) or 11 (a
 * cédula), and none is degenerate. The accounts that prompted this used "9999"
 * and "32928".
 *
 * The RNC check digit was tested and rejected: 16 of those 488 nine-digit
 * registrations fail the standard mod-11 algorithm, so enforcing it would
 * refuse roughly one real business in thirty. Do not add it back without
 * re-running that measurement.
 */
class ValidRnc implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (! in_array(strlen((string) $digits), [9, 11], true)) {
            $fail('El RNC debe tener 9 dígitos (empresa) u 11 (cédula). Verifica el número.');

            return;
        }

        // 000000000, 999999999 and the like: structurally well formed, obviously
        // placeholder. No real registration in the sample looks like this.
        if (count(array_unique(str_split((string) $digits))) === 1) {
            $fail('Ese RNC no es válido. Ingresa el RNC real de tu empresa.');

            return;
        }

        if (in_array((string) $digits, ['123456789', '012345678', '12345678901'], true)) {
            $fail('Ese RNC no es válido. Ingresa el RNC real de tu empresa.');
        }
    }
}
