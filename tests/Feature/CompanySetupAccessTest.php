<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySetupAccessTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithoutCompany(bool $verified): User
    {
        $user = User::create([
            'name' => 'Setup User',
            'email' => 'setup'.($verified ? 'v' : 'u').'@test.do',
            'password' => bcrypt('secret1234'),
        ]);
        if ($verified) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'custom',
            'status' => 'active',
            'max_companies' => 1,
            'max_users' => 2,
            'monthly_amount' => 45,
            'current_period_end' => now()->addMonth(),
        ]);

        return $user;
    }

    public function test_verified_owner_without_company_reaches_setup(): void
    {
        $user = $this->ownerWithoutCompany(verified: true);

        $this->actingAs($user)->get('/configurar-empresa')->assertOk();
    }

    public function test_unverified_owner_is_blocked_from_setup(): void
    {
        $user = $this->ownerWithoutCompany(verified: false);

        $response = $this->actingAs($user)->get('/configurar-empresa');

        $response->assertStatus(302);
        $this->assertStringContainsString('verificar', $response->headers->get('Location'));
    }
}
