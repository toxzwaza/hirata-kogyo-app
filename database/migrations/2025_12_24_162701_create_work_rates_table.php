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
        Schema::create('work_rates', function (Blueprint $table) {
            $table->id()->comment('作業単価ID');
            $table->foreignId('drawing_id')
                ->constrained('drawings')
                ->comment('図番ID');
            $table->foreignId('work_method_id')
                ->constrained('work_methods')
                ->comment('作業方法ID');
            $table->decimal('rate_employee', 10, 2)->nullable()->comment('正社員用単価（円/kg）');
            $table->decimal('rate_contractor', 10, 2)->nullable()->comment('個人事業主用通常単価（円/kg）');
            $table->decimal('rate_overtime', 10, 2)->nullable()->comment('個人事業主用残業単価（円/kg）');
            $table->text('note')->nullable()->comment('単価備考');
            $table->date('effective_from')->comment('適用開始日');
            $table->date('effective_to')->nullable()->comment('適用終了日');
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
        Schema::dropIfExists('work_rates');
    }
};
