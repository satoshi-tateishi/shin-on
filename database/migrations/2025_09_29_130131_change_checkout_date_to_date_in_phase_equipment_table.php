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
            // checkout_dateをdatetime型からdate型に変更
            $table->date('checkout_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phase_equipment', function (Blueprint $table) {
            // 元に戻す場合はdatetime型に戻す
            $table->datetime('checkout_date')->nullable()->change();
        });
    }
};
