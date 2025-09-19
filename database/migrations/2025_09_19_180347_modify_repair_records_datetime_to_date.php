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
            // 既存のdatetimeフィールドをdateに変更
            $table->date('failure_occurred_at')->nullable()->comment('故障発生日')->change();
            $table->date('reported_at')->comment('報告日')->change();
            $table->date('started_at')->nullable()->comment('修理開始日')->change();
            $table->date('completed_at')->nullable()->comment('修理完了日')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_records', function (Blueprint $table) {
            // dateフィールドをdatetimeに戻す
            $table->datetime('failure_occurred_at')->nullable()->comment('故障発生日時')->change();
            $table->timestamp('reported_at')->useCurrent()->comment('報告日時')->change();
            $table->timestamp('started_at')->nullable()->comment('修理開始日時')->change();
            $table->timestamp('completed_at')->nullable()->comment('修理完了日時')->change();
        });
    }
};
