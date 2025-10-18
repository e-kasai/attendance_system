<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Attendance;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // User(Parent)は複数のAttendance(Child)を持つ = hasMany
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    //今日のログイン中ユーザーの勤怠1件
    public function todayAttendance()
    {
        return $this->attendances()
            ->whereDate('date', now()->toDateString())
            ->first();
    }

    // 今日すでに出勤しているかチェック
    public function hasClockedInToday()
    {
        return $this->attendances()
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->exists();
    }

    // 今日すでに退勤しているかチェック
    public function hasClockedOutToday()
    {
        return $this->attendances()
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_out')
            ->exists();
    }

    //出勤処理
    public function startWork()
    {
        $attendance = $this->todayAttendance();
        //今日既に出勤済みならスキップ
        if ($this->hasClockedInToday()) {
            return $attendance;
        }

        $attendance = $this->attendances()->firstOrCreate(
            ['date' => now()->toDateString()],
            ['clock_in' => now(), 'status' => 2]
        );

        return $attendance;
    }

    // 退勤処理
    public function endWork()
    {
        $attendance = $this->todayAttendance();

        //今日既に退勤済みならスキップ
        if ($this->hasClockedOutToday()) {
            return $attendance;
        }

        if ($attendance && !$attendance->clock_out) {
            $attendance->update([
                'clock_out' => now(),
                'status' => '4',
            ]);
        }

        return $attendance;
    }

    //休憩開始
    public function startBreak()
    {
        $attendance = $this->todayAttendance();
        //今日既に退勤済みならスキップ
        if ($this->hasClockedOutToday()) {
            return $attendance;
        }

        if ($attendance && !$attendance->clock_out) {
            $attendance->breakTimes()->create([
                'break_in' => now(),
            ]);
            $attendance->update(['status' => 3]);
        }
        return $attendance;
    }

    //休憩終了
    public function endBreak()
    {

        $attendance = $this->todayAttendance();

        if ($attendance && !$attendance->clock_out) {
            // 未終了の休憩を取得
            $activeBreak = $attendance->breakTimes()
                ->whereNull('break_out')
                ->latest()
                ->first();
        }
        if ($activeBreak) {
            $activeBreak->update(['break_out' => now()]);
            $attendance->update(['status' => 2]);
            return $activeBreak;
        }

        // 未終了の休憩がない場合
        return null;
    }
}
