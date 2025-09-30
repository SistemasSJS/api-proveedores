<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('import_audits', function (Blueprint $table) {
            if (!Schema::hasColumn('import_audits', 'archivo')) {
                $table->string('archivo')
                    ->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('import_audits', function (Blueprint $table) {
            if (Schema::hasColumn('import_audits', 'archivo')) {
                $table->dropColumn('archivo');
            }
        });
    }
};
