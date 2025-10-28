<?php

namespace App\Http\Controllers\Staff;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffRequestListController extends Controller
{
    public function showRequests(Request $request): View
    {

        $status = $request->query('status', 'pending'); // デフォルト=承認待ち

        $updateRequests = UpdateRequest::with(['attendance', 'requester'])
            ->whereHas('attendance', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->when($status === 'pending', fn($query) => $query->where('approval_status', 1))   // 承認待ち
            ->when($status === 'approved', fn($query) => $query->where('approval_status', 2))  // 承認済み
            ->orderByDesc('created_at')
            ->get();

        return view('common.requests_index', compact('updateRequests', 'status'));
    }
}
