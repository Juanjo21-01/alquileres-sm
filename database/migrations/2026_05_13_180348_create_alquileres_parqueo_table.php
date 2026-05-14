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
        Schema::create('alquileres_parqueo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrendatario_parqueo_id')
                ->constrained('arrendatarios_parqueo')
                ->restrictOnDelete();
            $table->date('mes');
            $table->decimal('monto', 10, 2);
            $table->boolean('pagado')->default(false);
            $table->date('fecha_pago')->nullable();
            $table->enum('metodo_pago', ['efectivo', 'cuenta'])->default('efectivo');
            $table->text('notas')->nullable();
            $table->foreignId('user_registro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['arrendatario_parqueo_id', 'mes']);
            $table->index('mes');
            $table->index('pagado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alquileres_parqueo');
    }
};
