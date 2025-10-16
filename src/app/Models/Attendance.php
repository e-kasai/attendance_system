<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        //user_idとapproved_id = コントローラ側で自動決定&安全性考慮で$fillableに入れない
        'clock_in',
        'clock_out',
        'comment'
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i',   //時刻計算・整形出力しやすくする為
        'clock_out' => 'datetime:H:i',
        'is_approved' => 'boolean',    //if文で正確に条件分岐するため
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function breaktimes()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }
}
