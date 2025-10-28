<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminAttendanceListController extends Controller
{
    public function showDailyAttendances(): View
    {
        return view('admin.daily_attendances');
    }
}
