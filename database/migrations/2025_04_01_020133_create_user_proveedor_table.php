<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProveedorTable extends Migration
{
    public function up()
    {
        Schema::create('user_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained()->onDelete('cascade');
            $table->boolean('is_main')->default(false); // Campo para marcar si es principal
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_proveedor');
    }
}
