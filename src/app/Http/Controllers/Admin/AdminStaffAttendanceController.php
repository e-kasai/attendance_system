<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;
use Carbon\Carbon;

use Illuminate\Http\Request;

class AdminStaffAttendanceController extends Controller
{

    public function showStaffList(): View
    {
        // roleがstaffのユーザーを取得
        $users = User::where('role', 'staff')->get();
        return view('admin.staff_index', compact('users'));
    }


    public function showMonthlyAttendances(Request $request, $userId): View
    {
        $user = User::findOrFail($userId);

        //リクエストから表示月を受け取る、なければ今月
        $targetYm = $request->input('target_ym', now()->format('Y-m'));
        $carbonObj = Carbon::createFromFormat('Y-m', str_replace('/', '-', $targetYm));

        // 選択した月・前月・翌月
        $selectedMonth = $carbonObj->format('Y/m');
        $prevMonth = $carbonObj->copy()->subMonth()->format('Y/m');
        $nextMonth = $carbonObj->copy()->addMonth()->format('Y/m');

        // 対象ユーザーの勤怠データ取得
        $attendances = $user->attendances()
            ->whereYear('date', $carbonObj->year)
            ->whereMonth('date', $carbonObj->month)
            ->with('breakTimes')
            ->orderBy('date', 'asc')
            ->get();

        // 共通ビューを使用
        return view('common.attendance_index', compact(
            'attendances',
            'user',
            'selectedMonth',
            'prevMonth',
            'nextMonth'
        ));
    }
}
