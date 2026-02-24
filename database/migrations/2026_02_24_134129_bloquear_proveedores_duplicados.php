<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Enums\EstadoUsuario;

return new class extends Migration
{
    public function up(): void
    {
        $ids = [
            98,
            99,
            100,
            101,
            96,
            97,
            52,
            88,
            89,
            55,
            56,
            94,
            95,
            105,
            106,
            107,
            113,
            114,
            115
        ];

        DB::table('proveedores')
            ->whereIn('id', $ids)
            ->update([
                'estatus' => EstadoUsuario::BLOQUEADO->value,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $ids = [
            98,
            99,
            100,
            101,
            96,
            97,
            52,
            88,
            89,
            55,
            56,
            94,
            95,
            105,
            106,
            107,
            113,
            114,
            115
        ];

        DB::table('proveedores')
            ->whereIn('id', $ids)
            ->update([
                'estatus' => EstadoUsuario::REGISTRADO->value,
                'updated_at' => now(),
            ]);
    }
};
