<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a signup name that carries a payload rather than a name.
 *
 * Registration spam puts its message in the one free-text field the form has.
 * Observed in production: Cyrillic text plus a rotating link shortener
 * (goo.su, nip.io, sslip.io), repeated every few days from disposable
 * addresses. The name is what reaches our own outbound mail and the admin
 * panel, so it is worth checking rather than storing.
 *
 * Two signals, both of which a real name never trips: a link, and a writing
 * system this market does not use. The audience is Dominican, so Latin script
 * with accents is the whole legitimate range.
 */
class HumanName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = (string) $value;

        if (preg_match('~https?://|www\.|t\.me/|[a-z0-9-]+\.[a-z]{2,}/~iu', $name)) {
            $fail('El nombre no puede contener enlaces.');

            return;
        }

        // Scripts that no Dominican legal or personal name uses. Matching on
        // these rather than allow-listing Latin keeps accents, apostrophes and
        // hyphens working without enumerating them.
        if (preg_match('~[\p{Cyrillic}\p{Greek}\p{Han}\p{Arabic}\p{Hebrew}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Thai}]~u', $name)) {
            $fail('Ingresa tu nombre en alfabeto latino.');

            return;
        }

        // "THRT332726SVWVE" — the tracking token these messages are tagged
        // with. A name does not carry a run of digits this long.
        if (preg_match('~\d{5,}~', $name)) {
            $fail('El nombre no parece válido. Revísalo e intenta de nuevo.');
        }
    }
}
