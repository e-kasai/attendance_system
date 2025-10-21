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
        $month = $request->input('month', now()->format('Y-m'));
        $baseStyle = Carbon::createFromFormat('Y-m', str_replace('/', '-', $month));

        // 選択した月・前月・翌月
        $selectedMonth = $baseStyle->format('Y/m');
        $prevMonth = $baseStyle->copy()->subMonth()->format('Y/m');
        $nextMonth = $baseStyle->copy()->addMonth()->format('Y/m');

        $attendances = $user->attendances()
            ->whereYear('date', substr($month, 0, 4))
            ->whereMonth('date', substr($month, 5, 2))
            ->orderBy('date', 'asc')
            ->get();

        return view('staff.attendance_index', compact('attendances', 'month', 'selectedMonth', 'prevMonth', 'nextMonth'));
    }
}
