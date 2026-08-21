<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_renders(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('licitaciones');
    }
}
