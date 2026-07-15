<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Heuristics for detecting automated submissions to the public contact form.
 *
 * All checks fail "silently" from the bot's perspective — the caller shows the
 * same success message it would for a real submission, so a bot never learns
 * which signal tripped it and can't iterate against us.
 */
class ContactSpamGuard
{
    /**
     * Minimum seconds between rendering the form and submitting it. Humans
     * writing a message take far longer; scripted posts are near-instant.
     */
    private const MIN_FILL_SECONDS = 4;

    /**
     * Maximum age of a rendered form before its token is considered stale.
     * Generous — someone may leave a tab open — but bounded so a harvested
     * token can't be replayed indefinitely.
     */
    private const MAX_FORM_AGE_SECONDS = 86400; // 24h

    /**
     * Field name for the honeypot. Deliberately plausible-but-unused: bots
     * fill anything that looks like a real field, and "website"/"url" are so
     * common that sophisticated scripts now skip them specifically.
     */
    public const HONEYPOT_FIELD = 'company_fax';

    /**
     * Field name carrying the encrypted render timestamp.
     */
    public const TIMESTAMP_FIELD = 'form_ts';

    /**
     * Substrings that indicate the message is unsolicited marketing/crypto
     * spam. Matched case-insensitively against name + email + message.
     */
    private const SPAM_KEYWORDS = [
        // Crypto
        'bitcoin', 'btc', 'ethereum', 'crypto', 'blockchain', 'nft',
        'binance', 'coinbase', 'usdt', 'tether', 'forex', 'trading bot',
        'invest now', 'guaranteed profit', 'roi guaranteed',
        // SEO / marketing spam
        'seo service', 'seo expert', 'seo package', 'backlink',
        'first page of google', 'rank your website', 'increase your traffic',
        'digital marketing agency', 'guest post', 'link building',
        'web design service', 'boost your sales',
        // Generic
        'viagra', 'casino', 'porn', 'xxx', 'loan offer', 'work from home',
        'make money fast', 'click here now', 'limited time offer',
        'telegram.me', 't.me/', 'whatsapp me',
    ];

    /**
     * Encrypted timestamp to embed in the form. Encrypted rather than plain
     * so a bot can't simply forge an old value.
     */
    public static function issueTimestamp(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    /**
     * Decide whether a submission looks automated.
     */
    public function isSpam(Request $request): bool
    {
        $reason = $this->detect($request);

        if ($reason !== null) {
            Log::info('support.contact_spam_blocked', [
                'reason' => $reason,
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'user_agent' => $request->userAgent(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Returns the name of the tripped heuristic, or null when the submission
     * looks human.
     */
    private function detect(Request $request): ?string
    {
        // 1. Honeypot — a hidden field no human ever sees, let alone fills.
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return 'honeypot';
        }

        // Legacy honeypot field, kept so older cached pages still work.
        if (filled($request->input('website'))) {
            return 'honeypot_legacy';
        }

        // 2. Time trap.
        $timeReason = $this->checkTimestamp($request->input(self::TIMESTAMP_FIELD));
        if ($timeReason !== null) {
            return $timeReason;
        }

        // 3. Keyword scoring.
        $haystack = mb_strtolower(implode(' ', [
            (string) $request->input('name'),
            (string) $request->input('email'),
            (string) $request->input('message'),
        ]));

        foreach (self::SPAM_KEYWORDS as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return 'keyword:'.$keyword;
            }
        }

        // 4. Links in the message body. Legitimate enquiries from procurement
        // companies effectively never paste URLs; spam almost always does.
        if (preg_match_all('#https?://#i', (string) $request->input('message')) >= 2) {
            return 'excessive_links';
        }

        return null;
    }

    private function checkTimestamp(mixed $token): ?string
    {
        // Missing token — the form was not rendered by us (or was posted
        // directly to the endpoint).
        if (! is_string($token) || $token === '') {
            return 'missing_timestamp';
        }

        try {
            $issuedAt = (int) Crypt::decryptString($token);
        } catch (\Throwable) {
            return 'invalid_timestamp';
        }

        $elapsed = now()->timestamp - $issuedAt;

        if ($elapsed < self::MIN_FILL_SECONDS) {
            return 'too_fast';
        }

        if ($elapsed > self::MAX_FORM_AGE_SECONDS) {
            return 'stale_form';
        }

        return null;
    }
}
