<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class EmpresaPageTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_company_profile_page_renders(): void
    {
        // Regression: the sobre-theme block once called ->only() on a plain
        // array and 500'd; keep /empresa rendering.
        [$user] = $this->makeOwnerWithCompany();

        $this->actingAs($user)->get('/empresa')
            ->assertOk()
            ->assertSee('Perfil de empresa');
    }
}
