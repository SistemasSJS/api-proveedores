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
        Schema::table('import_audits', function (Blueprint $table) {
            $table->enum('fase', ['parse', 'validate', 'preview', 'confirm', 'execute', 'rollback'])->nullable()->after('estado');
            $table->longText('logs')->nullable()->after('fase');
            $table->integer('eta_seconds')->nullable()->after('logs');
            $table->integer('mem_peak_mb')->nullable()->after('eta_seconds');
            $table->string('formato')->nullable()->after('mem_peak_mb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_audits', function (Blueprint $table) {
            $table->dropColumn(['fase', 'logs', 'eta_seconds', 'mem_peak_mb', 'formato']);
        });
    }
};
