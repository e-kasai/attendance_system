<?php

namespace App\Http\Controllers\Staff;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffRequestListController extends Controller
{
    public function showRequests(): View
    {
        $updateRequests = UpdateRequest::with(['attendance', 'requester'])
            ->whereHas('attendance', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->orderByDesc('created_at')
            ->get();

        return view('common.requests_index', compact('updateRequests'));
    }
}
