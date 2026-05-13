<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE estancias
            ADD COLUMN cuarto_activo_id BIGINT UNSIGNED
            GENERATED ALWAYS AS (
                CASE WHEN estado = 'activa' AND deleted_at IS NULL THEN cuarto_id END
            ) VIRTUAL,
            ADD UNIQUE KEY uk_cuarto_activa (cuarto_activo_id)
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE estancias DROP INDEX uk_cuarto_activa');
        DB::statement('ALTER TABLE estancias DROP COLUMN cuarto_activo_id');
    }
};
