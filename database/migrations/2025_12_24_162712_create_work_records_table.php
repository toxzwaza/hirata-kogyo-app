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
        Schema::create('work_records', function (Blueprint $table) {
            $table->id()->comment('作業実績ID');
            $table->foreignId('drawing_id')->constrained('drawings')->comment('図番ID');
            $table->foreignId('work_method_id')->constrained('work_methods')->comment('作業方法ID');
            $table->foreignId('staff_id')->constrained('staff')->comment('スタッフID');
            $table->foreignId('work_rate_id')->constrained('work_rates')->comment('作業単価ID');
            $table->dateTime('start_time')->comment('作業開始時刻');
            $table->dateTime('end_time')->comment('作業終了時刻');
            $table->integer('work_minutes')->comment('作業時間（分）');
            $table->integer('quantity_good')->comment('良品数');
            $table->integer('quantity_ng')->comment('不良数');
            $table->text('memo')->nullable()->comment('備考');
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
        Schema::dropIfExists('work_records');
    }
};
