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
        Schema::create('staff_invoices', function (Blueprint $table) {
            $table->id()->comment('スタッフ請求書ID');
            $table->foreignId('staff_id')->constrained('staff')->comment('スタッフID');
            $table->string('invoice_number')->comment('請求書番号');
            $table->date('issue_date')->comment('発行日');
            $table->date('period_from')->comment('請求期間開始');
            $table->date('period_to')->comment('請求期間終了');
            $table->decimal('subtotal', 12, 2)->comment('小計');
            $table->decimal('tax', 12, 2)->comment('消費税');
            $table->decimal('total', 12, 2)->comment('合計金額');
            $table->enum('status', ['draft', 'fixed', 'paid'])->comment('ステータス');
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
        Schema::dropIfExists('staff_invoices');
    }
};
