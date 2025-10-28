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

        $dateString = $request->input('target_date') ?: now()->format('Y-m-d'); //入力がなければ今日を表示
        $date = Carbon::parse(str_replace('/', '-', $dateString));   // どちらの区切りもOK

        // 前日・翌日計算（送信用は Y-m-d で統一）
        $prevDate = $date->copy()->subDay()->format('Y-m-d');
        $nextDate = $date->copy()->addDay()->format('Y-m-d');

        // 勤怠取得
        $attendances = Attendance::with('user')
            ->whereDate('date', $date->format('Y-m-d'))
            ->whereHas('user', fn($query) => $query->where('role', 'staff')) //管理者は除外
            ->whereNotNull('clock_in')   // 出勤が記録されているもののみ
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
