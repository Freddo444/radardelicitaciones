<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->boolean('is_bookmarked')->default(false)->after('raw_data');
            $table->boolean('mipymes')->nullable()->after('is_bookmarked');
            $table->boolean('mipymes_mujeres')->nullable()->after('mipymes');
            $table->json('cached_documents')->nullable()->after('mipymes_mujeres');
            $table->json('cached_articles')->nullable()->after('cached_documents');
            $table->json('cached_contracts')->nullable()->after('cached_articles');
            $table->timestamp('cache_refreshed_at')->nullable()->after('cached_contracts');
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn([
                'is_bookmarked', 'mipymes', 'mipymes_mujeres',
                'cached_documents', 'cached_articles', 'cached_contracts',
                'cache_refreshed_at',
            ]);
        });
    }
};
