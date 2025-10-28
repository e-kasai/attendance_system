<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminAttendanceDetailController extends Controller
{
    public function showAttendanceDetail()
    {
        return view('common.attendance_detail');
    }
}
