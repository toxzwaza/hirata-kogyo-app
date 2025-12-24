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
        Schema::table('staff', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('email')->comment('取引銀行名');
            $table->string('branch_name')->nullable()->after('bank_name')->comment('支店名');
            $table->string('account_type')->nullable()->after('branch_name')->comment('口座種別');
            $table->string('account_number')->nullable()->after('account_type')->comment('口座番号');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'branch_name', 'account_type', 'account_number']);
        });
    }
};
