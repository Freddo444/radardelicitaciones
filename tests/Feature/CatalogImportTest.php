<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The UNSPSC catalog is 17 sequential pages of 1,000 from an API that is slow
 * under load. On 2026-11-01 page 12 timed out, the ConnectionException escaped
 * handle(), and the catalog sat a month stale while the admin page went on
 * reporting the previous month's import as current.
 */
class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'datosabiertos.dgcp.gob.do/api-dgcp/v1/catalogo*';

    /** One page of $count items, numbered from $from so pages do not collide. */
    private function page(int $count, int $from = 0): array
    {
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $n = $from + $i;
            $items[] = [
                'subclase' => str_pad((string) (10000000 + $n), 8, '0'),
                'descripcion_subclase' => "Subclase {$n}",
                'clase' => '10000000',
                'descripcion_clase' => 'Clase',
                'familia' => '10000000',
                'descripcion_familia' => 'Familia',
                'segmento' => '10000000',
                'descripcion_segmento' => 'Segmento',
            ];
        }

        return ['payload' => ['content' => $items]];
    }

    public function test_a_transient_timeout_on_one_page_does_not_lose_the_import(): void
    {
        $calls = 0;

        Http::fake([self::URL => function () use (&$calls) {
            $calls++;

            if ($calls === 2) {
                throw new ConnectionException('cURL error 28: Operation timed out after 30002 milliseconds');
            }

            // Short of the page limit, so the loop stops after one good page.
            return Http::response($this->page($calls === 1 ? 3 : 2, 100));
        }]);

        $this->artisan('secp:import-catalog', ['--attempts' => 3])->assertSuccessful();

        $this->assertSame('3', Setting::get('catalog_item_count', null, null));
        $this->assertNotNull(Setting::get('catalog_last_imported_at', null, null));
    }

    public function test_a_page_that_keeps_failing_fails_the_run(): void
    {
        Http::fake([self::URL => function () {
            throw new ConnectionException('cURL error 28: Operation timed out after 30002 milliseconds');
        }]);

        $this->artisan('secp:import-catalog', ['--attempts' => 2])
            ->expectsOutputToContain('el catálogo queda incompleto')
            ->assertFailed();
    }

    public function test_a_partial_import_is_not_recorded_as_the_current_catalog(): void
    {
        Setting::set('catalog_item_count', '16989', null);
        Setting::set('catalog_last_imported_at', '2026-10-01 00:00:58', null);

        $calls = 0;

        Http::fake([self::URL => function () use (&$calls) {
            $calls++;

            if ($calls === 1) {
                return Http::response($this->page(1000));
            }

            throw new ConnectionException('cURL error 28: Operation timed out after 30002 milliseconds');
        }]);

        $this->artisan('secp:import-catalog', ['--attempts' => 1])->assertFailed();

        // The rows that did arrive are kept — it is an upsert, nothing is
        // deleted — but the count and timestamp still describe the last
        // complete import, not this broken one.
        $this->assertSame(1000, CatalogItem::count());
        $this->assertSame('16989', Setting::get('catalog_item_count', null, null));
        $this->assertSame('2026-10-01 00:00:58', Setting::get('catalog_last_imported_at', null, null));
    }

    public function test_a_client_error_is_not_retried(): void
    {
        $calls = 0;

        Http::fake([self::URL => function () use (&$calls) {
            $calls++;

            return Http::response('nope', 404);
        }]);

        $this->artisan('secp:import-catalog', ['--attempts' => 4])->assertFailed();

        $this->assertSame(1, $calls, 'A 404 will not become a 200 on the fourth try.');
    }

    public function test_a_server_error_is_retried(): void
    {
        $calls = 0;

        Http::fake([self::URL => function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? Http::response('upstream hiccup', 503)
                : Http::response($this->page(2));
        }]);

        $this->artisan('secp:import-catalog', ['--attempts' => 3])->assertSuccessful();

        $this->assertSame(2, CatalogItem::count());
    }

    public function test_an_item_missing_its_hierarchy_does_not_break_the_page(): void
    {
        Http::fake([self::URL => Http::response(['payload' => ['content' => [
            ['subclase' => '10000001', 'descripcion_subclase' => 'Solo subclase'],
            ['subclase' => '10000002', 'descripcion_subclase' => 'Completa', 'clase' => '10000000',
                'descripcion_clase' => 'C', 'familia' => '10000000', 'descripcion_familia' => 'F',
                'segmento' => '10000000', 'descripcion_segmento' => 'S'],
            ['descripcion_subclase' => 'Sin subclase, se descarta'],
        ]]])]);

        $this->artisan('secp:import-catalog')->assertSuccessful();

        $this->assertSame(2, CatalogItem::count());
        $this->assertSame('', CatalogItem::find('10000001')->clase);
    }
}
