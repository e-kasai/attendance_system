<?php

namespace App\Http\Controllers\Staff;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class StaffAttendanceListController extends Controller
{
    public function showAttendances(Request $request): View
    {
        $user = auth()->user();

        //リクエストから表示月を受け取る、なければ今月
        $targetYm = $request->input('target_ym', now()->format('Y-m'));
        $carbonObj = Carbon::createFromFormat('Y-m', str_replace('/', '-', $targetYm));

        // 選択した月・前月・翌月
        $selectedMonth = $carbonObj->format('Y/m');
        $prevMonth = $carbonObj->copy()->subMonth()->format('Y/m');
        $nextMonth = $carbonObj->copy()->addMonth()->format('Y/m');

        $attendances = $user->attendances()
            ->whereYear('date', substr($targetYm, 0, 4))
            ->whereMonth('date', substr($targetYm, 5, 2))
            ->orderBy('date', 'asc')
            ->get();

        return view('staff.attendance_index', compact('attendances', 'targetYm', 'selectedMonth', 'prevMonth', 'nextMonth'));
    }
}
