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
        Schema::table('repair_records', function (Blueprint $table) {
            $table->dropColumn(['repair_type', 'priority', 'parts_replaced']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_records', function (Blueprint $table) {
            $table->enum('repair_type', ['preventive', 'corrective', 'emergency'])->default('corrective')->after('photos');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('status');
            $table->text('parts_replaced')->nullable()->after('repair_cost');
        });
    }
};
