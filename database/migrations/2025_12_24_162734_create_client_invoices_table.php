<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_invoices', function (Blueprint $table) {
            $table->id()->comment('委託元請求書ID');
            $table->foreignId('client_id')->constrained('clients')->comment('客先ID');
            $table->string('invoice_number')->comment('請求書番号');
            $table->date('issue_date')->comment('発行日');
            $table->date('period_from')->comment('請求期間開始');
            $table->date('period_to')->comment('請求期間終了');
            $table->decimal('subtotal', 12, 2)->comment('小計');
            $table->decimal('adjustment_amount', 12, 2)->default(0)->comment('差額調整金額');
            $table->string('adjustment_reason')->nullable()->comment('差額調整理由');
            $table->decimal('tax', 12, 2)->comment('消費税');
            $table->decimal('total', 12, 2)->comment('合計金額');
            $table->enum('status', ['draft', 'fixed', 'issued'])->comment('ステータス');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_invoices');
    }
};
