<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING  = 1;
    const STATUS_APPROVED = 2;

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


    //approval_statusが承認済みになったらis_approvedをtrueにする
    protected static function booted()
    {
        static::updated(function ($updateRequest) {
            if ($updateRequest->approval_status === 2) {
                $attendance = $updateRequest->attendance;
                if ($attendance && $attendance->is_approved === false) {
                    $attendance->update(['is_approved' => true]);
                }
            }
        });
    }


    /** 勤怠レコード */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
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
