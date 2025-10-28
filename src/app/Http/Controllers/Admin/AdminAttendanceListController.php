<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;


class AdminAttendanceListController extends Controller
{
    public function showDailyAttendances(Request $request): View
    {

        // 1) 日付決定
        $dateString = $request->input('target_date') ?: now()->format('Y-m-d');
        $date = Carbon::parse(str_replace('/', '-', $dateString));   // どちらの区切りもOK

        // 2) 前日・翌日計算（送信用は Y-m-d で統一）
        $prevDate = $date->copy()->subDay()->format('Y-m-d');
        $nextDate = $date->copy()->addDay()->format('Y-m-d');

        // 3) 勤怠取得（ユーザー名付き）
        $attendances = Attendance::with('user')
            ->whereDate('date', $date->format('Y-m-d'))
            ->whereHas('user', fn($query) => $query->where('role', 'staff'))
            ->orderBy('user_id')
            ->get();

        return view('admin.daily_attendances', [
            'selectedDate' => $date->format('Y-m-d'),
            'prevDate'     => $prevDate,
            'nextDate'     => $nextDate,
            'attendances'  => $attendances,
        ]);
    }
}
