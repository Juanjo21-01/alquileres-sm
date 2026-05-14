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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estancia_id')->constrained('estancias')->restrictOnDelete();
            $table->foreignId('tipo_pago_id')->constrained('tipos_pago')->restrictOnDelete();
            $table->date('fecha_pago');
            $table->date('mes_aplicado')->nullable();
            $table->decimal('monto_bruto', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->string('motivo_descuento', 255)->nullable();
            $table->decimal('monto_neto', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'cuenta'])->default('efectivo');
            $table->string('referencia', 100)->nullable();
            $table->string('recibo_numero', 50)->nullable()->unique();
            $table->text('notas')->nullable();
            $table->foreignId('user_registro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estancia_id', 'fecha_pago']);
            $table->index('mes_aplicado');
            $table->index(['tipo_pago_id', 'fecha_pago']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
