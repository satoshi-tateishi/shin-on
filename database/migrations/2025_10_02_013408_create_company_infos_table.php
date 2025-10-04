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
        Schema::create('company_infos', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->comment('会社名');
            $table->string('postal_code', 10)->nullable()->comment('郵便番号');
            $table->string('address')->nullable()->comment('住所');
            $table->string('phone', 20)->nullable()->comment('電話番号');
            $table->string('repair_contact_person')->nullable()->comment('修理担当者名');
            $table->string('repair_contact_email')->nullable()->comment('修理担当者メールアドレス');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_infos');
    }
};
