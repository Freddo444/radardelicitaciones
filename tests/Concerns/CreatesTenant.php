<?php

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;

trait CreatesTenant
{
    private static int $tenantSeq = 0;

    /**
     * Create a verified subscription owner with one company attached and active.
     *
     * @return array{0: User, 1: Subscription, 2: Company}
     */
    protected function makeOwnerWithCompany(array $subAttrs = [], array $companyAttrs = [], bool $verified = true): array
    {
        $n = ++self::$tenantSeq;

        $user = User::create([
            'name' => "Owner {$n}",
            'email' => "owner{$n}@test.do",
            'password' => bcrypt('secret1234'),
        ]);
        if ($verified) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $subscription = Subscription::create(array_merge([
            'user_id' => $user->id,
            'plan' => 'custom',
            'status' => 'active',
            'max_companies' => 1,
            'max_users' => 2,
            'monthly_amount' => 45,
            'current_period_end' => now()->addMonth(),
        ], $subAttrs));

        $company = Company::create(array_merge([
            'razon_social' => "Empresa {$n} SRL",
            'rnc' => '1-30-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT).'-1',
            'owner_id' => $user->id,
        ], $companyAttrs));

        $user->companies()->attach($company->id, ['joined_at' => now()]);
        $user->update(['current_company_id' => $company->id]);

        return [$user, $subscription, $company];
    }
}
