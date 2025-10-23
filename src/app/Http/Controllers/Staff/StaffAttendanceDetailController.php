<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\View\View;

class StaffAttendanceDetailController extends Controller
{
    public function showAttendanceDetail($id): View
    {
        $user = auth()->user();

        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->with('breakTimes')
            ->firstOrFail();

        return view('common.attendance_detail', compact('attendance', 'user'));
    }
}
