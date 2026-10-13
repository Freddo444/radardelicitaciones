<?php

namespace Tests\Feature;

use App\Services\DgcpProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class RubroSyncTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private const ENDPOINT = 'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores/rubro*';

    /**
     * The DGCP answers 200 with payload.content explicitly null for an RPE it
     * has no rubros for. The key exists, so a data_get default never applies.
     */
    public function test_an_unknown_rpe_yields_no_rubros_instead_of_crashing(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'code' => 200,
            'hasError' => false,
            'payload' => ['content' => null],
            'page' => 1,
            'limit' => 100,
        ])]);

        $this->assertSame([], app(DgcpProviderService::class)->fetchRubros(99999999));
    }

    public function test_syncing_an_unknown_rpe_tells_the_user_instead_of_500ing(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'code' => 200,
            'hasError' => false,
            'payload' => ['content' => null],
        ])]);

        [$user] = $this->makeOwnerWithCompany([], ['rpe_numero' => 99999999]);

        $this->actingAs($user)
            ->post(route('rubros.sync'))
            ->assertRedirect()
            ->assertSessionHas('info');

        $this->assertDatabaseCount('rubros', 0);
    }

    public function test_a_dgcp_outage_is_reported_not_thrown(): void
    {
        Http::fake([self::ENDPOINT => fn () => throw new ConnectionException('timed out')]);

        [$user] = $this->makeOwnerWithCompany([], ['rpe_numero' => 10811]);

        $this->assertSame([], app(DgcpProviderService::class)->fetchRubros(10811));

        $this->actingAs($user)
            ->post(route('rubros.sync'))
            ->assertRedirect()
            ->assertSessionHas('info');
    }

    public function test_malformed_rows_are_skipped(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'payload' => ['content' => [
                'no soy un objeto',
                ['familia_unspsc' => '30120000', 'descripcion' => '  Carreteras  '],
                ['descripcion' => 'sin código'],
            ]],
            'pages' => 1,
        ])]);

        $this->assertSame(
            [['code' => '30120000', 'name' => 'Carreteras']],
            app(DgcpProviderService::class)->fetchRubros(10811)
        );
    }

    public function test_rubros_are_paginated_and_deduped(): void
    {
        Http::fake([self::ENDPOINT => function (Request $request) {
            $page = (int) ($request->data()['page'] ?? 1);

            return Http::response([
                'payload' => ['content' => $page === 1
                    ? [
                        ['familia_unspsc' => '30120000', 'descripcion' => 'Carreteras'],
                        ['familia_unspsc' => '30200000', 'descripcion' => 'Estructuras'],
                    ]
                    : [
                        // Repeated across pages — the DGCP lists one row per
                        // clase, which collapses to the same familia code.
                        ['familia_unspsc' => '30120000', 'descripcion' => 'Carreteras'],
                        ['familia_unspsc' => '44110000', 'descripcion' => 'Accesorios'],
                    ]],
                'pages' => 2,
            ]);
        }]);

        $codes = array_column(app(DgcpProviderService::class)->fetchRubros(10811), 'code');

        $this->assertSame(['30120000', '30200000', '44110000'], $codes);
    }

    public function test_sync_without_an_rpe_asks_the_user_to_add_one(): void
    {
        Http::fake();

        [$user] = $this->makeOwnerWithCompany([], ['rpe_numero' => null]);

        $this->actingAs($user)
            ->post(route('rubros.sync'))
            ->assertRedirect()
            ->assertSessionHas('warning');

        Http::assertNothingSent();
    }
}
