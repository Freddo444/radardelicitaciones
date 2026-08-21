<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class SmokePageTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public static function tenantPages(): array
    {
        return array_map(fn ($p) => [$p], [
            '/dashboard',
            '/convocatorias',
            '/tablero',
            '/rubros',
            '/ofertas',
            '/ofertas/create',
            '/documentos',
            '/documentos-generados',
            '/personal',
            '/proyectos',
            '/equipos',
            '/financiero',
            '/formularios',
            '/empresa',
            '/facturacion',
            '/configuracion',
            '/usuarios',
            '/inteligencia/adjudicados',
            '/inteligencia/pacc',
            '/inteligencia/contratos',
            '/inteligencia/instituciones',
            '/inteligencia/proveedores',
        ]);
    }

    #[DataProvider('tenantPages')]
    public function test_tenant_page_loads(string $path): void
    {
        [$user] = $this->makeOwnerWithCompany();

        $this->actingAs($user)->get($path)->assertOk();
    }

    public static function adminPages(): array
    {
        return array_map(fn ($p) => [$p], [
            '/admin',
            '/admin/empresas',
            '/admin/usuarios',
            '/admin/suscripciones',
            '/admin/pagos',
            '/admin/pagos/huerfanos',
            '/admin/newsletter',
            '/admin/salud',
            '/admin/ajustes/facturacion',
        ]);
    }

    #[DataProvider('adminPages')]
    public function test_admin_page_loads(string $path): void
    {
        [$user] = $this->makeOwnerWithCompany();
        $user->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($user)->get($path)->assertOk();
    }
}
