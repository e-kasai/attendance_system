<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment',
        'new_clock_in',
        'new_clock_out',
    ];

    protected $casts = [
        'new_clock_in' => 'datetime:H:i',
        'new_clock_out' => 'datetime:H:i',
        'approval_status' => 'integer',
        'approved_at' => 'datetime',
    ];

    /** 勤怠レコード */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /** 対象の休憩（出退勤修正時はnull） */
    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }

    /** 申請者（ログインユーザー） */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** 複数の休憩修正 */
    public function breakTimeUpdates()
    {
        return $this->hasMany(BreakTimeUpdate::class);
    }
}
