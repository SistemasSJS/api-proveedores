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
        Schema::create('import_validation_caches', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique()->index();
            $table->unsignedBigInteger('proveedor_id');
            $table->string('file_name');
            $table->integer('total_rows')->default(0);
            $table->json('validation_data');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
            $table->index(['proveedor_id', 'expires_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_validation_caches');
    }
};
