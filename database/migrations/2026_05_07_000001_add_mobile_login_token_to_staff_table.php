<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('mobile_login_token', 64)->nullable()->unique()->after('password');
        });

        DB::table('staff')->whereNull('mobile_login_token')->orderBy('id')->each(function ($row) {
            do {
                $token = Str::random(48);
            } while (DB::table('staff')->where('mobile_login_token', $token)->exists());

            DB::table('staff')->where('id', $row->id)->update([
                'mobile_login_token' => $token,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropUnique(['mobile_login_token']);
            $table->dropColumn('mobile_login_token');
        });
    }
};
