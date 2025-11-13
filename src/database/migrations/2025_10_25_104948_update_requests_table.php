<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRequestsTable extends Migration
{

    public function up(): void
    {
        Schema::create('update_requests', function (Blueprint $table) {
            $table->id();

            // 勤怠レコード（必須）
            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->cascadeOnDelete();

            // 申請者
            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // 修正理由
            $table->string('comment', 255);

            // 修正後の時刻（出退勤修正の場合）
            $table->time('new_clock_in')->nullable();
            $table->time('new_clock_out')->nullable();

            // 承認状態：1=承認待ち, 2=承認済み
            $table->tinyInteger('approval_status')->default(1);

            // 承認日時
            $table->timestamp('approved_at')->nullable();

            // 申請日時・更新日時
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('update_requests');
    }
}
