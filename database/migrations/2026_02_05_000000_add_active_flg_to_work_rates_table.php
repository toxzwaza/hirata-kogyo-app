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
        Schema::table('work_rates', function (Blueprint $table) {
            $table->boolean('active_flg')->default(true)->after('effective_to')->comment('適用フラグ（true: 作業実績で使用する単価として有効）');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_rates', function (Blueprint $table) {
            $table->dropColumn('active_flg');
        });
    }
};
