<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{

    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->integer('work_time')->nullable();  //実働時間＝分単位
            $table->integer('break_time')->nullable(); //休憩時間、DBには分単位保存
            $table->text('comment')->nullable();

            $table->tinyInteger('status')->default(1); //1:勤務外, 2:出勤中, 3:休憩中, 4:退勤済
            $table->boolean('is_approved')->default(false); //false = 承認待ち（修正不可）
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'date']);   // 同一ユーザー・同一日付の重複登録を防ぐ
            $table->index(['user_id', 'date']);    // 検索高速化（複合index）
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
}
