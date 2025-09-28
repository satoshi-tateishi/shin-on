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
        Schema::table('phase_equipment', function (Blueprint $table) {
            $table->unsignedBigInteger('source_phase_equipment_id')->nullable()->after('note');
            $table->index('source_phase_equipment_id', 'idx_source_phase_equipment_id');
            $table->foreign('source_phase_equipment_id')->references('id')->on('phase_equipment')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phase_equipment', function (Blueprint $table) {
            $table->dropForeign(['source_phase_equipment_id']);
            $table->dropIndex('idx_source_phase_equipment_id');
            $table->dropColumn('source_phase_equipment_id');
        });
    }
};
