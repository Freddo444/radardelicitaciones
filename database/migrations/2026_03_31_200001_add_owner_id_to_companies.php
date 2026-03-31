<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // Backfill: set owner_id to the first user attached to each company
        // who also owns a subscription, or fallback to first user
        $companies = DB::table('companies')->get();
        foreach ($companies as $company) {
            $ownerId = DB::table('company_user')
                ->join('subscriptions', 'subscriptions.user_id', '=', 'company_user.user_id')
                ->where('company_user.company_id', $company->id)
                ->value('company_user.user_id');

            if (! $ownerId) {
                $ownerId = DB::table('company_user')
                    ->where('company_id', $company->id)
                    ->orderBy('joined_at')
                    ->value('user_id');
            }

            if ($ownerId) {
                DB::table('companies')->where('id', $company->id)->update(['owner_id' => $ownerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
