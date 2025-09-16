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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->integer('sort')->default(0)->comment('ソート順');
            $table->enum('type', ['株式会社', '有限会社', '合同会社', '財団法人', '公益財団法人', 'その他'])->default('株式会社')->comment('法人種別');
            $table->string('name')->comment('プロダクション名');
            $table->string('postal_code', 8)->nullable()->comment('郵便番号');
            $table->text('address')->nullable()->comment('住所');
            $table->text('note')->nullable()->comment('備考');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();

            $table->index('sort');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
