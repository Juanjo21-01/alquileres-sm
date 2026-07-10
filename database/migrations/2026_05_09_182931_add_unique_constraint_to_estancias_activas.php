<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Garantía: a lo sumo una estancia activa (no borrada) por cuarto.
        // MySQL 8 no soporta índices parciales -> columna generada virtual + UNIQUE.
        // SQLite (tests) sí soporta índices únicos parciales -> índice equivalente.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                CREATE UNIQUE INDEX uk_cuarto_activa ON estancias (cuarto_id)
                WHERE estado = 'activa' AND deleted_at IS NULL
            ");

            return;
        }

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
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS uk_cuarto_activa');

            return;
        }

        DB::statement('ALTER TABLE estancias DROP INDEX uk_cuarto_activa');
        DB::statement('ALTER TABLE estancias DROP COLUMN cuarto_activo_id');
    }
};
