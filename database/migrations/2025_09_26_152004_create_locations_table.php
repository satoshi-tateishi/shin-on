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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->integer('sort')->default(0)->comment('ソート順');
            $table->enum('type', ['劇場', '稽古場', '倉庫'])->comment('場所タイプ');
            $table->string('name')->comment('場所名');
            $table->string('furigana')->nullable()->comment('ふりがな');
            $table->string('tel1_name')->nullable()->comment('電話1名称');
            $table->string('tel1')->nullable()->comment('電話1');
            $table->string('tel2_name')->nullable()->comment('電話2名称');
            $table->string('tel2')->nullable()->comment('電話2');
            $table->string('fax')->nullable()->comment('FAX');
            $table->string('email1_name')->nullable()->comment('メール1名称');
            $table->string('email1')->nullable()->comment('メール1');
            $table->string('email2_name')->nullable()->comment('メール2名称');
            $table->string('email2')->nullable()->comment('メール2');
            $table->string('postal_code', 8)->nullable()->comment('郵便番号');
            $table->text('address')->nullable()->comment('住所');
            $table->text('note')->nullable()->comment('備考');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->boolean('is_inventory_visible')->default(true)->comment('在庫管理フィルタに表示するか');
            $table->boolean('is_transfer_visible')->default(true)->comment('倉庫間移動フィルタに表示するか');
            $table->timestamps();

            $table->index('type');
            $table->index('sort');
            $table->index('name');
            $table->index('is_active');
            $table->index(['is_inventory_visible', 'is_active'], 'locations_inventory_visible_active_index');
            $table->index(['is_transfer_visible', 'is_active'], 'locations_transfer_visible_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
