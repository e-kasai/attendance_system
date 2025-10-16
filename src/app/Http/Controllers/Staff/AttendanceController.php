<?php

namespace App\Http\Controllers\Staff;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class AttendanceController extends Controller
{
    public function showAttendanceStatus(): View
    {
        return view('staff.attendance_create');
    }
}
