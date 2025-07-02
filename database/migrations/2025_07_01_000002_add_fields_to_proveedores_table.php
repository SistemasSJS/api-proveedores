<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->boolean('principal')->default(false)->after('activo');
            $table->decimal('calificacion', 3, 2)->default(0)->after('principal');
            $table->string('categoria')->nullable()->after('calificacion');
            $table->string('ciudad')->nullable()->after('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['principal', 'calificacion', 'categoria', 'ciudad']);
        });
    }
};
