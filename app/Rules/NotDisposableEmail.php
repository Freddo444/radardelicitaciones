<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuses signups from throwaway mail domains.
 *
 * The list already existed to keep re-engagement mail away from addresses that
 * no longer accept it; this applies it at the door instead, where it also stops
 * the account being created in the first place. One list, two uses — see
 * services.disposable_email_domains.
 */
class NotDisposableEmail implements ValidationRule
{
    public static function isDisposable(?string $email): bool
    {
        $domain = strtolower(trim(substr(strrchr((string) $email, '@') ?: '', 1)));

        if ($domain === '') {
            return false;
        }

        return in_array($domain, config('services.disposable_email_domains', []), true);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (static::isDisposable((string) $value)) {
            $fail('Usa un correo permanente de tu empresa para registrarte.');
        }
    }
}
