<?php

namespace Tests\Feature;

use App\Models\Rubro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_a_company_never_sees_another_companys_rubros(): void
    {
        [, , $companyA] = $this->makeOwnerWithCompany();
        Rubro::create(['company_id' => $companyA->id, 'code' => '11110000', 'name' => 'Rubro de la empresa A', 'level' => 'familia', 'active' => true]);

        [$userB, , $companyB] = $this->makeOwnerWithCompany();
        Rubro::create(['company_id' => $companyB->id, 'code' => '22220000', 'name' => 'Rubro de la empresa B', 'level' => 'familia', 'active' => true]);

        $this->actingAs($userB)->get('/rubros')
            ->assertOk()
            ->assertSee('22220000')
            ->assertDontSee('11110000');
    }
}
