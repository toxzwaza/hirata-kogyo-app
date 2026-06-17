<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 未登録図番の手動入力に対応するため、図番・単価を後から紐づけられるよう
     * drawing_id / work_rate_id を nullable 化し、手動入力テキスト列を追加する。
     *
     * @return void
     */
    public function up()
    {
        // FK列の nullable 化（doctrine/dbal 不要の生SQL。FK制約は維持される）
        DB::statement('ALTER TABLE work_records MODIFY drawing_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE work_records MODIFY work_rate_id BIGINT UNSIGNED NULL');

        Schema::table('work_records', function (Blueprint $table) {
            $table->string('manual_drawing_number')->nullable()->after('work_rate_id')
                ->comment('手動入力の品番（未登録図番。後で図番へ紐づけ）');
            $table->string('manual_product_name')->nullable()->after('manual_drawing_number')
                ->comment('手動入力の品名（任意）');
            $table->string('manual_client_name')->nullable()->after('manual_product_name')
                ->comment('手動入力の得意先名（任意）');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('work_records', function (Blueprint $table) {
            $table->dropColumn(['manual_drawing_number', 'manual_product_name', 'manual_client_name']);
        });

        // NOT NULL に戻す（null データが残っている場合は失敗するため、必要に応じて手当て）
        DB::statement('ALTER TABLE work_records MODIFY work_rate_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE work_records MODIFY drawing_id BIGINT UNSIGNED NOT NULL');
    }
};
