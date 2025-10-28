<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\UpdateRequest;
use Illuminate\View\View;


class AdminRequestApprovalController extends Controller
{
    // 修正申請承認画面の表示
    public function showApprovalPage($updateId): View
    {
        // $update（修正申請レコード）と関連情報の取得
        $update = UpdateRequest::with(['attendance.user', 'attendance.breakTimes', 'breakTimeUpdates'])
            ->findOrFail($updateId);

        // 勤怠内容に申請内容を反映(DBに保存はしない)
        $attendance = $update->attendance;
        $attendance->clock_in  = $update->new_clock_in  ?? $attendance->clock_in;
        $attendance->clock_out = $update->new_clock_out ?? $attendance->clock_out;


        // 休憩データの申請内容を反映（DBに保存はしない）
        foreach ($attendance->breakTimes as $break) {
            $breakUpdate = $update->breakTimeUpdates->firstWhere('break_time_id', $break->id);
            if ($breakUpdate) {
                $break->break_in  = $breakUpdate->new_break_in  ?? $break->break_in;
                $break->break_out = $breakUpdate->new_break_out ?? $break->break_out;
            }
        }

        //修正申請を反映して修正申請承認画面を表示
        return view('admin.request_approve', [
            'attendance' => $attendance,
            'user' => $attendance->user,
            'update' => $update,
        ]);
    }

    public function approveUpdatedRequest() {}
}
