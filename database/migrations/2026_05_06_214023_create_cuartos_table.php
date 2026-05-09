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
        Schema::create('cuartos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propiedad_id')->constrained('propiedades')->restrictOnDelete();
            $table->string('codigo', 20);
            $table->unsignedTinyInteger('nivel')->default(1);
            $table->string('tamano', 50)->nullable();
            $table->decimal('precio_base', 10, 2);
            $table->enum('estado', ['disponible', 'ocupado', 'reservado', 'mantenimiento'])
                  ->default('disponible');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['propiedad_id', 'codigo']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuartos');
    }
};
