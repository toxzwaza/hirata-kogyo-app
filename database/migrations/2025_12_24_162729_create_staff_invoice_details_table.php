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
        Schema::create('staff_invoice_details', function (Blueprint $table) {
            $table->id()->comment('請求書明細ID');
            $table->foreignId('staff_invoice_id')
                ->constrained('staff_invoices')
                ->comment('スタッフ請求書ID');
            $table->foreignId('work_record_id')
                ->constrained('work_records')
                ->comment('作業実績ID');
            $table->string('description')->comment('明細説明');
            $table->decimal('quantity', 10, 3)->comment('数量（kg）');
            $table->decimal('unit_price', 10, 2)->comment('単価（円/kg）');
            $table->decimal('amount', 12, 2)->comment('金額');
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
        Schema::dropIfExists('staff_invoice_details');
    }
};
