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
        Schema::create('drawings', function (Blueprint $table) {
            $table->id()->comment('図番ID');
            $table->foreignId('client_id')
                ->constrained('clients')
                ->comment('客先ID');
            $table->string('product_name')->comment('品名');
            $table->string('drawing_number')->comment('図番');
            $table->string('image_path')->nullable()->comment('図面画像パス');
            $table->decimal('weight_per_unit', 8, 3)->comment('1個あたり重量（kg）');
            $table->boolean('active_flag')->default(true)->comment('使用中フラグ');
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
        Schema::dropIfExists('drawings');
    }
};
