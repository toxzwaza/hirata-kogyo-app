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
        Schema::create('staff_invoice_items', function (Blueprint $table) {
            $table->id()->comment('紐付ID');
            $table->foreignId('client_invoice_id')
                ->constrained('client_invoices')
                ->comment('委託元請求書ID');
            $table->foreignId('staff_invoice_id')
                ->constrained('staff_invoices')
                ->comment('スタッフ請求書ID');
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
        Schema::dropIfExists('staff_invoice_items');
    }
};
