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
        Schema::create('repair_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipments')->onDelete('cascade')->comment('機材ID');
            $table->enum('repair_type', ['preventive', 'corrective', 'emergency'])->comment('修理タイプ');
            $table->text('problem_description')->comment('問題内容');
            $table->text('repair_description')->nullable()->comment('修理内容');
            $table->decimal('repair_cost', 10, 2)->nullable()->comment('修理費用');
            $table->string('repair_company')->nullable()->comment('修理業者');
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null')->comment('報告者ID');
            $table->string('repaired_by')->nullable()->comment('修理担当者');
            $table->timestamp('reported_at')->useCurrent()->comment('報告日時');
            $table->timestamp('started_at')->nullable()->comment('修理開始日時');
            $table->timestamp('completed_at')->nullable()->comment('修理完了日時');
            $table->enum('status', ['reported', 'in_progress', 'completed', 'cancelled'])->default('reported')->comment('ステータス');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->comment('優先度');
            $table->text('parts_replaced')->nullable()->comment('交換部品');
            $table->date('warranty_until')->nullable()->comment('修理保証期限');
            $table->text('note')->nullable()->comment('備考');
            $table->timestamps();

            // インデックス設定
            $table->index('equipment_id');
            $table->index('repair_type');
            $table->index('status');
            $table->index('priority');
            $table->index('reported_at');
            $table->index('completed_at');
            $table->index('repair_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_records');
    }
};
