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
        Schema::create('inquilinos', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('dpi', 13)->nullable()->unique();
            $table->string('telefono', 8)->nullable();
            $table->string('email', 150)->nullable();
            $table->enum('ocupacion', ['estudiante', 'salud', 'otro'])->default('otro');
            $table->string('institucion', 150)->nullable();
            $table->enum('vehiculo_tipo', ['carro', 'moto'])->nullable();
            $table->string('vehiculo_placa', 20)->nullable();
            $table->string('contacto_emergencia_nombre', 150)->nullable();
            $table->string('contacto_emergencia_telefono', 8)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['nombres', 'apellidos']);
            $table->index('telefono');
            $table->index('vehiculo_placa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquilinos');
    }
};
