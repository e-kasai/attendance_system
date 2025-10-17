<?php

namespace App\Http\Controllers\Staff;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class StaffAttendanceController extends Controller
{
    public function showAttendanceStatus(): View
    {
        Carbon::setLocale('ja');
        $today = Carbon::now()->isoFormat('YYYY年M月D日(ddd)');
        $time = Carbon::now()->format('H:i');
        return view('staff.attendance_create', compact('today', 'time'));
    }
}
