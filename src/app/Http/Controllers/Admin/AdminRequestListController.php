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
        $status = $request->query('status', 'pending'); // クエリパラメータなし = 承認待ちタブ（pending）を表示

        $updateRequests = UpdateRequest::with(['attendance.user', 'requester'])
            ->whereHas('attendance.user') // 勤怠(attendance)とユーザー(user)の両方が存在する申請のみ取得
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
