<?php

namespace Tests\Feature;

use App\Services\PortalScraperService;
use App\Services\PortalTrustStore;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PortalTrustStoreTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = sys_get_temp_dir().'/portal-trust-test-'.uniqid();
        mkdir($this->tmp);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    // ── The CA change, end to end ─────────────────────────────────────

    public function test_a_missing_intermediate_is_learned_over_aia_when_it_chains_to_a_trusted_root(): void
    {
        [$root, $rootKey] = $this->cert('Test Root', null, ca: true);
        [$int, $intKey] = $this->cert('Test Intermediate', [$root, $rootKey], ca: true);
        [$leaf] = $this->cert(PortalTrustStore::HOST, [$int, $intKey], aia: 'http://ca.test/int.crt');

        // Served as DER, the way most CAs publish their AIA issuer.
        $store = $this->store(served: [$leaf], downloads: ['http://ca.test/int.crt' => $this->der($int)],
            anchors: [$this->file('anchors.pem', $root)]);

        $this->assertSame($this->tmp.'/pinned.pem', $store->bundlePath());

        $this->assertTrue($store->repair());

        $bundle = $store->bundlePath();
        $this->assertSame($this->tmp.'/store/ca.pem', $bundle);
        $this->assertStringContainsString($this->body($int), preg_replace('/\s+/', '', (string) file_get_contents($bundle)),
            'The learned intermediate must be in the bundle curl is handed.');
        $this->assertStringContainsString('PINNED', (string) file_get_contents($bundle),
            'The shipped bundle must still be part of it.');
    }

    public function test_an_issuer_that_does_not_chain_to_a_trusted_root_is_never_trusted(): void
    {
        [$trustedRoot] = $this->cert('Trusted Root', null, ca: true);
        [$rogueRoot, $rogueKey] = $this->cert('Rogue Root', null, ca: true);
        [$rogueInt, $rogueIntKey] = $this->cert('Rogue Intermediate', [$rogueRoot, $rogueKey], ca: true,
            aia: 'http://evil.test/root.crt');
        [$leaf] = $this->cert(PortalTrustStore::HOST, [$rogueInt, $rogueIntKey], aia: 'http://evil.test/int.crt');

        // Every link is downloadable; none of it reaches a root the OS trusts.
        $store = $this->store(served: [$leaf], downloads: [
            'http://evil.test/int.crt' => $rogueInt,
            'http://evil.test/root.crt' => $rogueRoot,
        ], anchors: [$this->file('anchors.pem', $trustedRoot)]);

        $this->assertFalse($store->repair());
        $this->assertFileDoesNotExist($this->tmp.'/store/learned.pem');
        $this->assertSame($this->tmp.'/pinned.pem', $store->bundlePath());
    }

    public function test_a_leaf_without_an_issuer_link_cannot_be_repaired(): void
    {
        [$root, $rootKey] = $this->cert('Test Root', null, ca: true);
        [$int, $intKey] = $this->cert('Test Intermediate', [$root, $rootKey], ca: true);
        [$leaf] = $this->cert(PortalTrustStore::HOST, [$int, $intKey]);

        $store = $this->store(served: [$leaf], downloads: [], anchors: [$this->file('anchors.pem', $root)]);

        $this->assertFalse($store->repair());
        $this->assertFileDoesNotExist($this->tmp.'/store/learned.pem');
    }

    public function test_a_complete_served_chain_verifies_without_downloading_anything(): void
    {
        [$root, $rootKey] = $this->cert('Test Root', null, ca: true);
        [$int, $intKey] = $this->cert('Test Intermediate', [$root, $rootKey], ca: true);
        [$leaf] = $this->cert(PortalTrustStore::HOST, [$int, $intKey], aia: 'http://ca.test/int.crt');

        $store = $this->store(served: [$leaf, $int], downloads: [], anchors: [$this->file('anchors.pem', $root)]);

        $this->assertTrue($store->repair());
    }

    public function test_an_unreachable_portal_is_a_failed_repair_not_an_exception(): void
    {
        $store = $this->store(served: [], downloads: [], anchors: []);

        $this->assertFalse($store->repair());
    }

    // ── The scraper's use of it ───────────────────────────────────────

    public function test_the_scraper_repairs_once_and_retries_on_a_trust_failure(): void
    {
        $trust = $this->mockTrust(repairs: true);
        $calls = 0;

        Http::fake(['comunidad.comprasdominicana.gob.do/*' => function () use (&$calls) {
            if (++$calls === 1) {
                throw new ConnectionException(
                    'cURL error 60: SSL certificate problem: unable to get local issuer certificate');
            }

            return Http::response('<html>AdvancedSearchAjax?mkey=abc123_0 <table></table></html>');
        }]);

        $scraper = new PortalScraperService($trust);
        $scraper->scrapeAll();

        $this->assertSame([], $scraper->failedProcedures());
    }

    public function test_a_chain_that_cannot_be_repaired_is_chased_once_per_run_not_per_request(): void
    {
        $trust = $this->mockTrust(repairs: false);

        Http::fake(['comunidad.comprasdominicana.gob.do/*' => fn () => throw new ConnectionException(
            'cURL error 60: SSL certificate problem: unable to get local issuer certificate')]);

        $scraper = new PortalScraperService($trust);
        $scraper->scrapeAll();

        $this->assertCount($scraper->procedureCount(), $scraper->failedProcedures());
    }

    public function test_a_timeout_is_not_treated_as_a_trust_problem(): void
    {
        $trust = Mockery::mock(PortalTrustStore::class);
        $trust->shouldReceive('bundlePath')->andReturn('/dev/null');
        $trust->shouldReceive('osCaDirectory')->andReturn(null);
        $trust->shouldNotReceive('repair');

        Http::fake(['comunidad.comprasdominicana.gob.do/*' => fn () => throw new ConnectionException(
            'cURL error 28: Operation timed out')]);

        (new PortalScraperService($trust))->scrapeAll();
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function mockTrust(bool $repairs): PortalTrustStore
    {
        $trust = Mockery::mock(PortalTrustStore::class);
        $trust->shouldReceive('bundlePath')->andReturn('/dev/null');
        $trust->shouldReceive('osCaDirectory')->andReturn(null);
        $trust->shouldReceive('repair')->once()->andReturn($repairs);

        return $trust;
    }

    /**
     * @param  list<string>  $served
     * @param  array<string, string>  $downloads
     * @param  list<string>  $anchors
     */
    private function store(array $served, array $downloads, array $anchors): PortalTrustStore
    {
        $pinned = $this->file('pinned.pem', "# PINNED\n");

        return new class($pinned, $this->tmp.'/store', $anchors, $served, $downloads) extends PortalTrustStore
        {
            public function __construct(string $pinned, string $dir, array $anchors,
                private array $served, private array $downloads)
            {
                parent::__construct($pinned, $dir, $anchors);
            }

            protected function fetchServedChain(): array
            {
                return $this->served;
            }

            protected function download(string $url): array
            {
                return isset($this->downloads[$url]) ? self::parseCertificates($this->downloads[$url]) : [];
            }
        };
    }

    /**
     * @param  array{0: string, 1: \OpenSSLAsymmetricKey}|null  $issuer
     * @return array{0: string, 1: \OpenSSLAsymmetricKey}
     */
    private function cert(string $cn, ?array $issuer, bool $ca = false, ?string $aia = null): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);

        $ext = $ca
            ? "basicConstraints = critical, CA:TRUE\nkeyUsage = critical, keyCertSign, cRLSign\n"
            : "basicConstraints = critical, CA:FALSE\nkeyUsage = critical, digitalSignature\n"
                ."extendedKeyUsage = serverAuth\nsubjectAltName = DNS:{$cn}\n";
        $ext .= "subjectKeyIdentifier = hash\nauthorityKeyIdentifier = keyid\n";
        if ($aia) {
            $ext .= "authorityInfoAccess = caIssuers;URI:{$aia}\n";
        }

        $config = $this->file('openssl-'.uniqid().'.cnf',
            "[req]\ndistinguished_name = dn\n[dn]\n[ext]\n{$ext}");
        $options = ['config' => $config, 'x509_extensions' => 'ext', 'digest_alg' => 'sha256'];

        $csr = openssl_csr_new(['commonName' => $cn], $key, $options);
        $cert = openssl_csr_sign($csr, $issuer[0] ?? null, $issuer[1] ?? $key, 30, $options, random_int(1, PHP_INT_MAX));
        openssl_x509_export($cert, $pem);

        return [$pem, $key];
    }

    private function der(string $pem): string
    {
        return base64_decode($this->body($pem));
    }

    private function body(string $pem): string
    {
        return trim(preg_replace('/-----[A-Z ]+-----|\s+/', '', $pem));
    }

    private function file(string $name, string $contents): string
    {
        file_put_contents($this->tmp.'/'.$name, $contents);

        return $this->tmp.'/'.$name;
    }
}
