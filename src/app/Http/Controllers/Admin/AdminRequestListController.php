<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UpdateRequest;
use Illuminate\View\View;

class AdminRequestListController extends Controller
{
    public function showRequests(Request $request): View
    {
        $status = $request->query('status', 'pending'); // デフォルト=承認待ち

        $updateRequests = UpdateRequest::with(['attendance.user', 'requester'])
            ->whereHas('attendance.user') // ←「勤怠に紐づくユーザーが存在するもの」に限定
            ->when($status === 'pending', fn($query) => $query->where('approval_status', 1))
            ->when($status === 'approved', fn($query) => $query->where('approval_status', 2))
            ->orderByDesc('created_at')
            ->get();

        return view('common.requests_index', [
            'updateRequests' => $updateRequests,
            'status' => $status,
            'isAdmin' => true,
        ]);
    }
}
