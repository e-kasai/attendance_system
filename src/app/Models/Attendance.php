<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\User;
use App\Models\BreakTime;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        //user_idとis_approved = コントローラ側で自動決定&安全性考慮で$fillableに入れない
        'date',
        'clock_in',
        'clock_out',
        'work_time',
        'break_time',
        'status',
        'comment'
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i',   //出力時 "時:分" 形式に整形
        'clock_out' => 'datetime:H:i',
        'work_time' => 'integer',      //文字列混入を防止
        'break_time' => 'integer',
        'status' => 'integer',
        'is_approved' => 'boolean',    //if文で正確に条件分岐するため
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    protected static function booted()
    {
        /**
         * savingイベント
         * create()・update() の両方で呼ばれる
         */
        static::saving(function ($attendance) {
            // 出退勤が両方あれば勤務時間を自動計算
            if ($attendance->clock_in && $attendance->clock_out) {
                $workMinutes = Carbon::parse($attendance->clock_in)
                    ->diffInMinutes(Carbon::parse($attendance->clock_out));


                // 休憩時間の合計
                // 休憩が無い場合は0とする
                $breakMinutes = 0;

                $breakMinutes = $attendance->breakTimes()
                    ->whereNotNull('break_in')
                    ->whereNotNull('break_out')
                    ->get()
                    ->sum(function ($break) {
                        return Carbon::parse($break->break_in)
                            ->diffInMinutes(Carbon::parse($break->break_out));
                    });
                $attendance->break_time = $breakMinutes;
                $attendance->work_time = max($workMinutes - $breakMinutes, 0); //マイナス防止
            }
        });
    }
}
