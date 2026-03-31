<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backup existing settings
        $existing = DB::table('settings')->get();

        Schema::drop('settings');

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['company_id', 'key']);
        });

        // Restore existing settings as system-wide (company_id = null)
        foreach ($existing as $row) {
            DB::table('settings')->insert([
                'company_id' => null,
                'key' => $row->key,
                'value' => $row->value,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        $existing = DB::table('settings')->whereNull('company_id')->get();

        Schema::drop('settings');

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        foreach ($existing as $row) {
            DB::table('settings')->insert([
                'key' => $row->key,
                'value' => $row->value,
                'updated_at' => $row->updated_at,
            ]);
        }
    }
};
