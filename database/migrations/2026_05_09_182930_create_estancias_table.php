<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->restrictOnDelete();
            $table->foreignId('cuarto_id')->constrained('cuartos')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->date('fecha_fin_estimada')->nullable();
            $table->decimal('precio_acordado', 10, 2);
            $table->decimal('anticipo', 10, 2)->default(0);
            $table->enum('estado', ['activa', 'finalizada', 'cancelada'])->default('activa');
            $table->string('motivo_cierre', 255)->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('user_registro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index(['cuarto_id', 'estado']);
            $table->index(['inquilino_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estancias');
    }
};
