<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'break_in',
        'break_out',
    ];

    protected $casts = [
        'break_in' => 'datetime:H:i',   //時刻計算・整形出力しやすくする為
        'break_out' => 'datetime:H:i',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
}
