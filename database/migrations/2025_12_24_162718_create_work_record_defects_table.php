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
        Schema::create('work_record_defects', function (Blueprint $table) {
            $table->id()->comment('不良内訳ID');
            $table->foreignId('work_record_id')
                ->constrained('work_records')
                ->comment('作業実績ID');
            $table->foreignId('defect_type_id')
                ->constrained('defect_types')
                ->comment('不良種類ID');
            $table->integer('defect_quantity')->comment('不良数');
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
        Schema::dropIfExists('work_record_defects');
    }
};
