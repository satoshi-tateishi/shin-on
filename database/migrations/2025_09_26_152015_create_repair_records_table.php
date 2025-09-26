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
            $table->date('failure_occurred_at')->nullable()->comment('故障発生日');
            $table->foreignId('staff_user_id')->constrained('users')->onDelete('restrict')->comment('担当者ID');
            $table->string('performance_name')->nullable()->comment('公演名');
            $table->string('usage_location')->nullable()->comment('使用場所');
            $table->json('photos')->nullable()->comment('故障箇所写真パス配列');
            $table->text('problem_description')->comment('問題内容');
            $table->text('repair_description')->nullable()->comment('修理内容');
            $table->decimal('repair_cost', 10, 2)->nullable()->comment('修理費用');
            $table->string('repair_company')->nullable()->comment('修理業者');
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null')->comment('報告者ID');
            $table->string('repaired_by')->nullable()->comment('修理担当者');
            $table->date('reported_at')->comment('報告日');
            $table->date('started_at')->nullable()->comment('修理開始日');
            $table->date('completed_at')->nullable()->comment('修理完了日');
            $table->enum('status', ['reported', 'in_progress', 'completed', 'cancelled'])->default('reported')->comment('ステータス');
            $table->date('warranty_until')->nullable()->comment('修理保証期限');
            $table->text('note')->nullable()->comment('備考');
            $table->timestamps();

            // インデックス設定
            $table->index('equipment_id');
            $table->index('failure_occurred_at', 'idx_failure_occurred_at');
            $table->index('staff_user_id', 'idx_staff_user_id');
            $table->index('performance_name', 'idx_performance_name');
            $table->index('status');
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