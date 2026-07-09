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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_gasto_id')->constrained('categorias_gasto')->restrictOnDelete();
            $table->foreignId('propiedad_id')->nullable()->constrained('propiedades')->nullOnDelete();
            $table->foreignId('cuarto_id')->nullable()->constrained('cuartos')->nullOnDelete();
            $table->date('fecha');
            $table->decimal('monto', 10, 2);
            $table->string('descripcion', 255);
            $table->string('proveedor', 150)->nullable();
            $table->enum('metodo_pago', ['efectivo', 'cuenta'])->default('efectivo');
            $table->string('comprobante_path', 255)->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('user_registro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fecha', 'categoria_gasto_id']);
            $table->index('propiedad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
