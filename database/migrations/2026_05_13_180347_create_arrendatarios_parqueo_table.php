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
        Schema::create('arrendatarios_parqueo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo', 150);
            $table->string('telefono', 8)->nullable();
            $table->enum('ocupacion', ['estudiante', 'salud', 'otro'])->default('otro');
            $table->string('placa', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('nombre_completo');
            $table->index('placa');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrendatarios_parqueo');
    }
};
