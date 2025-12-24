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
        Schema::create('staff', function (Blueprint $table) {
            $table->id()->comment('スタッフID');
            $table->foreignId('staff_type_id')
                ->constrained('staff_types')
                ->comment('スタッフ種別ID');
            $table->string('name')->comment('氏名');
            $table->string('login_id')->unique()->comment('ログインID（ユニーク）');
            $table->string('password')->comment('ログイン用パスワード（ハッシュ）');
            $table->string('address')->nullable()->comment('住所');
            $table->string('tel')->nullable()->comment('電話番号');
            $table->string('email')->nullable()->comment('メールアドレス');
            $table->enum('tax_type', ['taxable', 'tax_exempt'])->comment('課税区分');
            $table->boolean('active_flag')->default(true)->comment('有効フラグ');
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
        Schema::dropIfExists('staff');
    }
};
