<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('awarded_articles', function (Blueprint $table) {
            $table->fullText(
                ['description', 'provider_name', 'institution_name', 'process_code', 'contract_code', 'unspsc_description'],
                'ft_awarded_articles_search'
            );
            $table->index('total', 'awarded_articles_total_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->fullText(
                ['description', 'provider_name', 'institution_name', 'process_code', 'contract_code'],
                'ft_contracts_search'
            );
        });

        Schema::table('pacc_acquisitions', function (Blueprint $table) {
            $table->fullText(
                ['description', 'institution_name', 'purpose', 'unspsc_description'],
                'ft_pacc_acquisitions_search'
            );
            $table->index('estimated_amount', 'pacc_acquisitions_estimated_amount_index');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->fullText(
                ['razon_social', 'rnc', 'rpe', 'email', 'contact_name'],
                'ft_providers_search'
            );
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->fullText(
                ['name', 'acronym', 'code', 'email'],
                'ft_institutions_search'
            );
        });
    }

    public function down(): void
    {
        Schema::table('awarded_articles', function (Blueprint $table) {
            $table->dropFullText('ft_awarded_articles_search');
            $table->dropIndex('awarded_articles_total_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropFullText('ft_contracts_search');
        });

        Schema::table('pacc_acquisitions', function (Blueprint $table) {
            $table->dropFullText('ft_pacc_acquisitions_search');
            $table->dropIndex('pacc_acquisitions_estimated_amount_index');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropFullText('ft_providers_search');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropFullText('ft_institutions_search');
        });
    }
};
