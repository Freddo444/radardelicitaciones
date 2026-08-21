<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class FlashMessageTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    public function test_rubro_sync_without_rpe_shows_a_visible_warning(): void
    {
        [$user] = $this->makeOwnerWithCompany();

        $response = $this->actingAs($user)
            ->from('/rubros')
            ->post(route('rubros.sync'));

        $response->assertRedirect('/rubros');
        $response->assertSessionHas('warning');

        $this->actingAs($user)->get('/rubros')
            ->assertOk()
            ->assertSee('Agrega tu n', false);
    }

    public function test_warning_flash_renders_in_the_layout(): void
    {
        [$user] = $this->makeOwnerWithCompany();

        $this->actingAs($user)
            ->withSession(['warning' => 'Mensaje de advertencia visible'])
            ->get('/rubros')
            ->assertOk()
            ->assertSee('Mensaje de advertencia visible');
    }

    public function test_info_flash_renders_in_the_layout(): void
    {
        [$user] = $this->makeOwnerWithCompany();

        $this->actingAs($user)
            ->withSession(['info' => 'Mensaje informativo visible'])
            ->get('/rubros')
            ->assertOk()
            ->assertSee('Mensaje informativo visible');
    }
}
