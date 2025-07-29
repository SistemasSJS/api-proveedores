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
            $table->enum('fase', ['parse', 'validate', 'preview', 'confirm', 'execute', 'rollback'])->nullable();
            $table->longText('logs')->nullable();
            $table->integer('eta_seconds')->nullable();
            $table->integer('mem_peak_mb')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_audits', function (Blueprint $table) {
            $table->dropColumn(['fase', 'logs', 'eta_seconds', 'mem_peak_mb']);
        });
    }
};
