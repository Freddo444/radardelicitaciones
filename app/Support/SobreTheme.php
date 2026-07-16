<?php

namespace App\Support;

/**
 * Resolves a company's chosen sobre theme into a concrete palette + typographic
 * config consumed by the cover / index / separator PDF templates.
 *
 * A theme is intentionally parameterized by a single accent color so every
 * company gets a distinct-looking document from the same small set of
 * professionally-designed layouts.
 */
class SobreTheme
{
    public const THEMES = ['corporativo', 'construccion', 'minimalista', 'oscuro'];

    /**
     * @return array{
     *   key:string, label:string, accent:string, accent_dark:string,
     *   ink:string, muted:string, page_bg:string, band:string,
     *   on_accent:string, font:string, heading_upper:bool
     * }
     */
    public static function resolve(?string $theme, ?string $accent): array
    {
        $theme = in_array($theme, self::THEMES, true) ? $theme : 'corporativo';
        $accent = self::sanitizeHex($accent) ?? self::defaultAccent($theme);
        $accentDark = self::darken($accent, 0.22);

        $base = [
            'key' => $theme,
            'accent' => $accent,
            'accent_dark' => $accentDark,
            'font' => 'dejavusans', // dompdf built-in, full UTF-8 (acentos/ñ)
        ];

        return match ($theme) {
            'construccion' => $base + [
                'label' => 'Construcción',
                'ink' => '#18181b',
                'muted' => '#71717a',
                'page_bg' => '#ffffff',
                'band' => $accent,
                'on_accent' => '#ffffff',
                'heading_upper' => true,
            ],
            'minimalista' => $base + [
                'label' => 'Minimalista',
                'ink' => '#111827',
                'muted' => '#9ca3af',
                'page_bg' => '#ffffff',
                'band' => '#ffffff',
                'on_accent' => $accent,
                'heading_upper' => false,
            ],
            'oscuro' => $base + [
                'label' => 'Elegante Oscuro',
                'ink' => '#f4f4f5',
                'muted' => '#a1a1aa',
                'page_bg' => '#0f172a',
                'band' => $accent,
                'on_accent' => '#ffffff',
                'heading_upper' => true,
            ],
            default => $base + [ // corporativo
                'label' => 'Corporativo',
                'ink' => '#18181b',
                'muted' => '#6b7280',
                'page_bg' => '#ffffff',
                'band' => $accent,
                'on_accent' => '#ffffff',
                'heading_upper' => false,
            ],
        };
    }

    public static function defaultAccent(string $theme): string
    {
        return match ($theme) {
            'construccion' => '#c2410c', // industrial orange
            'minimalista' => '#111827',  // near-black hairline
            'oscuro' => '#38bdf8',       // sky accent on dark
            default => '#1e40af',        // corporate blue
        };
    }

    public static function sanitizeHex(?string $hex): ?string
    {
        if (! is_string($hex)) {
            return null;
        }
        $hex = trim($hex);
        if (preg_match('/^#?([0-9a-fA-F]{6})$/', $hex, $m)) {
            return '#'.strtolower($m[1]);
        }

        return null;
    }

    /**
     * Darken a hex color by a 0..1 fraction (for gradients / bands / borders).
     */
    public static function darken(string $hex, float $fraction): string
    {
        $hex = ltrim($hex, '#');
        $r = (int) round(hexdec(substr($hex, 0, 2)) * (1 - $fraction));
        $g = (int) round(hexdec(substr($hex, 2, 2)) * (1 - $fraction));
        $b = (int) round(hexdec(substr($hex, 4, 2)) * (1 - $fraction));

        return sprintf('#%02x%02x%02x', max(0, $r), max(0, $g), max(0, $b));
    }
}
