<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Private-disk path to an uploaded bank-transfer voucher.
            $table->string('receipt_path')->nullable()->after('notes');
        });

        // Backfill from existing rows that stored the path inside notes as
        // "Comprobante: receipts/...".
        foreach (DB::table('payments')->whereNull('receipt_path')->whereNotNull('notes')->get(['id', 'notes']) as $row) {
            if (preg_match('/Comprobante:\s*(receipts\/[^\s—]+)/', (string) $row->notes, $m)) {
                DB::table('payments')->where('id', $row->id)->update(['receipt_path' => $m[1]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });
    }
};
