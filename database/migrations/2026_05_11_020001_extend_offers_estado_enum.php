<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE offers MODIFY COLUMN estado ENUM('borrador','en_preparacion','listo','enviado','adjudicada','perdida','impugnacion') NOT NULL DEFAULT 'borrador'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE offers MODIFY COLUMN estado ENUM('borrador','en_preparacion','listo','enviado') NOT NULL DEFAULT 'borrador'");
    }
};
