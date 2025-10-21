<?php

namespace App\Models\Traits;

use Carbon\Carbon;


trait AttendanceFormatTrait
{
    // 日付（06/01(木)）
    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->translatedFormat('m/d(D)');
    }

    // 出勤（09:00）
    public function getClockInAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }

    // 退勤（18:00）
    public function getClockOutAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }


    // 休憩（分→1:00形式）
    public function getBreakTimeAttribute($value)
    {
        return $value !== null
            ? sprintf('%d:%02d', floor($value / 60), $value % 60)
            : null;
    }


    // 合計（分→8:00形式）
    public function getWorkTimeAttribute($value)
    {
        return $value !== null
            ? sprintf('%d:%02d', floor($value / 60), $value % 60)
            : null;
    }
}
