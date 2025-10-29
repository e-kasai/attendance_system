<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BreakTimeUpdatesTable extends Migration
{

    public function up(): void
    {
        Schema::create('break_time_updates', function (Blueprint $table) {
            $table->id();

            // 修正対象の休憩
            $table->foreignId('break_time_id')
                ->nullable()
                ->constrained('break_times')
                ->cascadeOnDelete();

            // 対応する修正申請（必須）
            $table->foreignId('update_request_id')
                ->constrained('update_requests')
                ->cascadeOnDelete();

            // 修正後の休憩時間
            $table->time('new_break_in')->nullable();
            $table->time('new_break_out')->nullable();

            // 登録・更新日時
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('break_time_updates');
    }
}
