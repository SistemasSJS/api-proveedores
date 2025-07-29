
<?php

use App\Enums\EstadoGeneral;
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
        Schema::create('unidad_medidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->string('nombre');
            $table->string('clave')->nullable();
            $table->string('descripcion')->nullable();
            $table->enum('estatus', EstadoGeneral::values())->default(EstadoGeneral::ACTIVO->value);
            $table->timestamps();
            $table->unique(['nombre', 'proveedor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidad_medidas');
    }
};
