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
        Schema::table('staff_invoices', function (Blueprint $table) {
            $table->date('payment_due_date')->nullable()->after('issue_date')->comment('支払い期限');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staff_invoices', function (Blueprint $table) {
            $table->dropColumn('payment_due_date');
        });
    }
};
