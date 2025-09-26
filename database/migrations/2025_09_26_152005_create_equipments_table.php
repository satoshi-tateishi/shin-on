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
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained('equipment_subcategories')->onDelete('restrict')->comment('サブカテゴリID');
            $table->integer('sort')->default(0)->comment('ソート順');
            $table->string('manufacturer')->nullable()->comment('メーカー名');
            $table->string('name')->comment('機材名');
            $table->string('company_number', 50)->nullable()->comment('新音番号');
            $table->enum('management_type', ['individual', 'quantity'])->default('individual')->comment('管理方式');
            $table->integer('quantity')->default(1)->comment('在庫数量');
            $table->enum('unit', ['台', '個', '本', '箱', 'ケース', 'ラック', 'セット'])->default('台')->comment('単位');
            $table->string('model_number', 100)->nullable()->comment('型番');
            $table->string('serial_number', 100)->nullable()->comment('シリアル番号');
            $table->string('supplier')->nullable()->comment('仕入先');
            $table->date('purchase_date')->nullable()->comment('購入日');
            $table->date('warranty_expiry')->nullable()->comment('保証期限');
            $table->decimal('price', 12, 2)->nullable()->comment('価格');
            $table->enum('status', ['available', 'in_use', 'repair', 'maintenance', 'retired', 'lost'])->default('available')->comment('状態');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null')->comment('基本倉庫ID');
            $table->foreignId('now_location_id')->nullable()->constrained('locations')->onDelete('set null')->comment('現在地ID');
            $table->boolean('is_discard')->default(false)->comment('廃棄フラグ');
            $table->boolean('is_schedule_visible')->default(true)->comment('スケジュール表に表示する対象機材かどうか');
            $table->date('discard_at')->nullable()->comment('廃棄日');
            $table->text('notes')->nullable()->comment('備考');
            $table->timestamps();

            $table->index('subcategory_id');
            $table->index('management_type');
            $table->index('status');
            $table->index('manufacturer');
            $table->index('name');
            $table->index('location_id');
            $table->index('now_location_id');
            $table->index('is_discard');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
