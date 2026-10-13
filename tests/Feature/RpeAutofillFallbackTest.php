<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class RpeAutofillFallbackTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private const RUBRO = 'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores/rubro*';

    private const PROVIDERS = 'datosabiertos.dgcp.gob.do/api-dgcp/v1/proveedores*';

    private function actor()
    {
        [$user] = $this->makeOwnerWithCompany(
            ['status' => 'trialing', 'trial_ends_at' => now()->addDays(7), 'max_companies' => 3],
        );

        return $user;
    }

    /** Grupo Alzare's real shape: /proveedores 500s, /proveedores/rubro answers. */
    private function fakeBrokenProviderRecord(): void
    {
        Http::fake([
            self::RUBRO => Http::response([
                'payload' => ['content' => [
                    ['rpe' => 135240, 'razon_social' => 'Grupo Alzare, SRL', 'familia_unspsc' => '43210000', 'descripcion' => 'Componentes para tecnología'],
                    ['rpe' => 135240, 'razon_social' => 'Grupo Alzare, SRL', 'familia_unspsc' => '81110000', 'descripcion' => 'Servicios informáticos'],
                ]],
                'pages' => 1,
            ]),
            self::PROVIDERS => Http::response([
                'code' => 500,
                'hasError' => true,
                'payload' => ['content' => null, 'message' => 'Ha ocurrido un error al completar la consulta...'],
            ], 500),
        ]);
    }

    public function test_a_broken_provider_record_still_autofills_name_and_rubros(): void
    {
        $this->fakeBrokenProviderRecord();

        $response = $this->actingAs($this->actor())
            ->postJson(route('company-setup.lookup-rpe'), ['rpe' => 135240]);

        $response->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('partial', true)
            ->assertJsonPath('company.razon_social', 'Grupo Alzare, SRL')
            ->assertJsonPath('company.rpe_numero', 135240);

        // The rubros are the valuable half — they are the part of setup that
        // takes real work.
        $this->assertCount(2, $response->json('rubros'));
        $this->assertSame('43210000', $response->json('rubros.0.code'));
    }

    public function test_the_partial_lookup_explains_itself(): void
    {
        $this->fakeBrokenProviderRecord();

        $this->actingAs($this->actor())
            ->postJson(route('company-setup.lookup-rpe'), ['rpe' => 135240])
            ->assertJsonPath('partial', true)
            ->assertJsonStructure(['notice']);
    }

    public function test_a_full_provider_record_is_unchanged_and_not_partial(): void
    {
        Http::fake([
            self::RUBRO => Http::response(['payload' => ['content' => [
                ['rpe' => 19582, 'razon_social' => 'Inversiones Tuira, SRL', 'familia_unspsc' => '43210000', 'descripcion' => 'Tecnología'],
            ]], 'pages' => 1]),
            self::PROVIDERS => Http::response(['payload' => ['content' => [[
                'rpe' => 19582,
                'razon_social' => 'Inversiones Tuira, SRL',
                'numero_documento' => '130107505',
                'direccion' => 'Av. Principal 1',
                'provincia' => 'Santo Domingo',
            ]]]]),
        ]);

        $this->actingAs($this->actor())
            ->postJson(route('company-setup.lookup-rpe'), ['rpe' => 19582])
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonMissingPath('partial')
            ->assertJsonPath('company.rnc', '130107505')
            ->assertJsonPath('company.provincia', 'Santo Domingo');
    }

    public function test_an_rpe_nobody_knows_is_reported_as_not_found(): void
    {
        // VOCASER's 647726: both endpoints answer 200 with nothing.
        Http::fake([
            self::RUBRO => Http::response(['payload' => ['content' => null]]),
            self::PROVIDERS => Http::response(['payload' => ['content' => null]]),
        ]);

        $this->actingAs($this->actor())
            ->postJson(route('company-setup.lookup-rpe'), ['rpe' => 647726])
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonMissingPath('dgcp_unavailable');
    }

    public function test_a_total_outage_still_offers_manual_entry(): void
    {
        Http::fake([
            self::RUBRO => Http::response('', 503),
            self::PROVIDERS => Http::response('', 503),
        ]);

        $this->actingAs($this->actor())
            ->postJson(route('company-setup.lookup-rpe'), ['rpe' => 135240])
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('dgcp_unavailable', true);
    }
}
