<?php

use App\Models\Location;
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
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('is_main_warehouse')->default(false)->after('is_transfer_visible');
        });

        // 既存の主要倉庫（すみだ倉庫、クローゼット豪徳寺A102/A412）にフラグを設定
        Location::whereIn('id', [92, 93, 94])->update(['is_main_warehouse' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('is_main_warehouse');
        });
    }
};
