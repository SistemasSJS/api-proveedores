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
        Schema::table('user_device_tokens', function (Blueprint $table) {
            // Modificamos el tamaño del campo device_name
            $table->string('device_name', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_device_tokens', function (Blueprint $table) {
            // Revertimos al tamaño original (100)
            $table->string('device_name', 100)->nullable()->change();
        });
    }
};
