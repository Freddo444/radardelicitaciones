<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The CA file the portal is verified against, and the means to repair it.
 *
 * comunidad.comprasdominicana.gob.do serves its leaf certificate without the
 * intermediates. Browsers fetch the missing issuer from the leaf's Authority
 * Information Access URL; curl does not, so every pinned bundle we have shipped
 * has died the day the portal changed CA (2026-10-13, then 2026-11-02 for twelve
 * days). repair() does what a browser does: follow the AIA chain from the live
 * leaf and keep what it downloads only if OpenSSL verifies the result up to a
 * root the OS already trusts. Nothing new is trusted — a downloaded certificate
 * that does not chain to an OS anchor is discarded.
 */
class PortalTrustStore
{
    public const HOST = 'comunidad.comprasdominicana.gob.do';

    private const MAX_DEPTH = 4;

    private string $pinned;

    private string $dir;

    /** @var list<string> Files and directories holding the OS trust anchors. */
    private array $anchors;

    /** @param list<string>|null $anchors */
    public function __construct(?string $pinned = null, ?string $dir = null, ?array $anchors = null)
    {
        $this->pinned = $pinned ?? resource_path('certs/comprasdominicana-ca.pem');
        $this->dir = $dir ?? storage_path('app/portal-trust');
        $this->anchors = $anchors ?? self::osAnchors();
    }

    /**
     * The file to hand curl as its CA bundle: the shipped bundle, plus
     * anything repair() has learned since.
     */
    public function bundlePath(): string
    {
        $learned = $this->dir.'/learned.pem';
        $combined = $this->dir.'/ca.pem';

        if (! is_file($learned)) {
            return $this->pinned;
        }

        if (! is_file($combined) || filemtime($combined) < max(filemtime($learned), filemtime($this->pinned))) {
            if (! $this->write($combined, file_get_contents($this->pinned)."\n".file_get_contents($learned))) {
                return $this->pinned;
            }
        }

        return $combined;
    }

    /**
     * The OS trust directory, passed to curl alongside the bundle so a portal
     * that one day serves a complete chain from any public CA just works.
     */
    public function osCaDirectory(): ?string
    {
        foreach ($this->anchors as $anchor) {
            if (is_dir($anchor)) {
                return $anchor;
            }
        }

        return null;
    }

    /**
     * Chase the live leaf's issuers over AIA until the chain verifies against
     * the OS store, and remember the intermediates that made it verify.
     *
     * @return bool whether the portal's live chain now verifies
     */
    public function repair(): bool
    {
        try {
            return $this->chase();
        } catch (\Throwable $e) {
            Log::error('[PortalTrust] Chain repair failed.', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function chase(): bool
    {
        $served = $this->fetchServedChain();

        if ($served === []) {
            Log::error('[PortalTrust] Could not read the portal\'s certificate to repair the chain.');

            return false;
        }

        $leaf = array_shift($served);
        $intermediates = $served;
        $current = end($served) ?: $leaf;

        for ($depth = 0; $depth <= self::MAX_DEPTH; $depth++) {
            if ($this->verifies($leaf, $intermediates)) {
                $this->remember($intermediates, $leaf);

                return true;
            }

            if ($depth === self::MAX_DEPTH) {
                break;
            }

            $url = $this->issuerUrl($current);
            $issuers = $url ? $this->download($url) : [];

            $known = array_map([$this, 'fingerprint'], array_merge([$leaf], $intermediates));
            $issuers = array_values(array_filter($issuers,
                fn ($pem) => ! in_array($this->fingerprint($pem), $known, true)));

            if ($issuers === []) {
                break;
            }

            array_push($intermediates, ...$issuers);
            $current = $issuers[0];
        }

        Log::error('[PortalTrust] The portal\'s certificate does not chain to any root the OS trusts, '
            .'even after following its AIA issuer links. Nothing was trusted.', [
                'leaf' => $this->describe($leaf),
                'tried' => array_map([$this, 'describe'], $intermediates),
            ]);

        return false;
    }

    /**
     * The certificates the portal presents, leaf first. Read with verification
     * off on purpose — this connection sends nothing and is only used to look
     * at what the server offers; everything taken from it is verified in
     * verifies() before it is trusted.
     *
     * @return list<string> PEM
     */
    protected function fetchServedChain(): array
    {
        $context = stream_context_create(['ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'capture_peer_cert' => true,
            'capture_peer_cert_chain' => true,
            'SNI_enabled' => true,
            'peer_name' => self::HOST,
        ]]);

        $socket = @stream_socket_client('ssl://'.self::HOST.':443', $errno, $errstr, 15,
            STREAM_CLIENT_CONNECT, $context);

        if (! $socket) {
            return [];
        }

        $params = stream_context_get_params($socket)['options']['ssl'] ?? [];
        fclose($socket);

        $chain = $params['peer_certificate_chain'] ?? array_filter([$params['peer_certificate'] ?? null]);
        $pems = [];
        foreach ($chain as $cert) {
            if (openssl_x509_export($cert, $pem)) {
                $pems[] = $pem;
            }
        }

        return $pems;
    }

    /** @return list<string> PEM */
    protected function download(string $url): array
    {
        if (! preg_match('#^https?://#i', $url)) {
            return [];
        }

        try {
            $response = Http::timeout(15)->get($url);
        } catch (\Throwable) {
            return [];
        }

        return $response->successful() ? self::parseCertificates($response->body()) : [];
    }

    /** @param list<string> $intermediates */
    private function verifies(string $leaf, array $intermediates): bool
    {
        $untrusted = null;

        if ($intermediates !== []) {
            $untrusted = tempnam(sys_get_temp_dir(), 'portal-chain-');
            file_put_contents($untrusted, implode("\n", $intermediates));
        }

        try {
            return openssl_x509_checkpurpose($leaf, X509_PURPOSE_SSL_SERVER, $this->anchors, $untrusted) === true;
        } finally {
            if ($untrusted) {
                @unlink($untrusted);
            }
        }
    }

    /** @param list<string> $intermediates */
    private function remember(array $intermediates, string $leaf): void
    {
        $existing = (string) @file_get_contents($this->dir.'/learned.pem');
        $shipped = (string) @file_get_contents($this->pinned);

        $have = array_map([$this, 'fingerprint'],
            array_merge(self::parseCertificates($existing), self::parseCertificates($shipped)));

        $new = array_values(array_filter($intermediates,
            fn ($pem) => ! in_array($this->fingerprint($pem), $have, true)));

        if ($new === []) {
            return;
        }

        $this->write($this->dir.'/learned.pem', $existing."\n".implode("\n", $new));

        Log::error('[PortalTrust] The portal changed its certificate chain; learned its new issuer over AIA '
            .'and verified it against the OS store. No action needed, but '
            .'resources/certs/comprasdominicana-ca.pem can be updated to match.', [
                'leaf' => $this->describe($leaf),
                'learned' => array_map([$this, 'describe'], $new),
            ]);
    }

    private function issuerUrl(string $pem): ?string
    {
        $aia = openssl_x509_parse($pem)['extensions']['authorityInfoAccess'] ?? '';

        return preg_match('/CA Issuers - URI:(\S+)/', $aia, $m) ? $m[1] : null;
    }

    /**
     * AIA issuers are served as DER, PEM or PKCS#7 depending on the CA.
     *
     * @return list<string> PEM
     */
    public static function parseCertificates(string $body): array
    {
        if (str_contains($body, '-----BEGIN CERTIFICATE-----')) {
            preg_match_all('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', $body, $m);

            return array_values(array_filter(array_map(
                fn ($pem) => openssl_x509_read($pem) ? $pem."\n" : null, $m[0])));
        }

        if ($body === '') {
            return [];
        }

        $pem = "-----BEGIN CERTIFICATE-----\n".chunk_split(base64_encode($body), 64, "\n")."-----END CERTIFICATE-----\n";
        if (@openssl_x509_read($pem)) {
            return [$pem];
        }

        $p7 = "-----BEGIN PKCS7-----\n".chunk_split(base64_encode($body), 64, "\n")."-----END PKCS7-----\n";
        if (@openssl_pkcs7_read($p7, $certs)) {
            return array_values($certs);
        }

        return [];
    }

    private function fingerprint(string $pem): string
    {
        return (string) openssl_x509_fingerprint($pem, 'sha256');
    }

    private function describe(string $pem): string
    {
        $c = openssl_x509_parse($pem) ?: [];

        return ($c['subject']['CN'] ?? '?').' (issued by '.($c['issuer']['CN'] ?? '?').')';
    }

    /**
     * Written to a temp file and renamed into place, group-writable: the
     * scheduler runs as one user and the queue worker as another, and either
     * may be the one that learns something.
     */
    private function write(string $path, string $contents): bool
    {
        if (! is_dir($this->dir)) {
            if (! @mkdir($this->dir, 02775, true) && ! is_dir($this->dir)) {
                return false;
            }

            @chgrp($this->dir, (int) @filegroup(dirname($this->dir)));
            @chmod($this->dir, 02775);
        }

        $tmp = $path.'.'.getmypid().'.tmp';
        if (@file_put_contents($tmp, $contents) === false) {
            return false;
        }

        @chmod($tmp, 0664);

        if (! @rename($tmp, $path)) {
            @unlink($tmp);

            return false;
        }

        return true;
    }

    /** @return list<string> */
    private static function osAnchors(): array
    {
        $locations = openssl_get_cert_locations();

        $candidates = [
            getenv('SSL_CERT_FILE') ?: null,
            $locations['default_cert_file'] ?? null,
            '/etc/ssl/certs/ca-certificates.crt',
            getenv('SSL_CERT_DIR') ?: null,
            $locations['default_cert_dir'] ?? null,
            '/etc/ssl/certs',
        ];

        return array_values(array_unique(array_filter($candidates, fn ($p) => $p && file_exists($p))));
    }
}
