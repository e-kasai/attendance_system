<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\BreakTimeUpdate;
use App\Models\UpdateRequest;
use Illuminate\View\View;
use App\Http\Requests\UpdateAttendanceRequest;

class StaffAttendanceDetailController extends Controller
{
    //勤怠詳細ページを表示
    public function showAttendanceDetail(Request $request, $id): View
    {
        $user = auth()->user();

        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['breakTimes', 'updateRequests.breakTimeUpdates'])
            ->firstOrFail();

        // 申請一覧から開かれた場合（?from=request&update_id=◯）
        // if ($request->query('from') === 'request' && $updateId = $request->query('update_id')) {
        //     $update = $attendance->updateRequests->firstWhere('id', $updateId);

        // URLクエリから申請情報を取得（ある場合のみ）
        $updateId = $request->query('update_id');
        $update = null;

        if ($request->query('from') === 'request' && $updateId) {
            $update = UpdateRequest::find($updateId);
        }

        if ($update) {
            // 出退勤を上書き
            $attendance->clock_in  = $update->new_clock_in  ?? $attendance->clock_in;
            $attendance->clock_out = $update->new_clock_out ?? $attendance->clock_out;

            // 休憩修正版を上書き
            foreach ($attendance->breakTimes as $break) {
                $breakUpdate = $update->breakTimeUpdates
                    ->firstWhere('break_time_id', $break->id);

                if ($breakUpdate) {
                    $break->break_in  = $breakUpdate->new_break_in  ?? $break->break_in;
                    $break->break_out = $breakUpdate->new_break_out ?? $break->break_out;
                }
            }
        }

        return view('common.attendance_detail', compact('attendance', 'user', 'update'));
    }

    public function updateAttendanceStatus(UpdateAttendanceRequest $request, $id)
    {
        //勤怠修正（出退勤・休憩）を申請
        $user = Auth::user();

        $validated = $request->validated();

        // 対象勤怠を取得（自分以外はエラー）
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();


        DB::transaction(function () use ($validated, $user, $attendance) {
            // 新しい修正申請を登録
            $updateRequest = new UpdateRequest();
            $updateRequest->attendance_id   = $attendance->id;
            $updateRequest->requested_by    = $user->id;
            $updateRequest->approval_status = UpdateRequest::STATUS_PENDING;
            $updateRequest->new_clock_in = $validated['clock_in'] !== null && $validated['clock_in'] !== ''
                ? $validated['clock_in']
                : $attendance->clock_in; //入力が空なら元を残す（null や空文字 ""も空として扱う）
            $updateRequest->new_clock_out = $validated['clock_out'] !== null && $validated['clock_out'] !== ''
                ? $validated['clock_out']
                : $attendance->clock_out;
            $updateRequest->comment         = $validated['comment'];
            $updateRequest->save();

            // 休憩修正申請（複数対応）
            $breaks = $validated['breaks'] ?? [];

            foreach ($breaks as $input) {
                $breakTimeId = $input['id'] ?? null;
                if (!$breakTimeId) {
                    continue; // idがなければスキップ
                }

                // 勤怠に紐づく休憩の中から対象を取得
                $originalBreak = $attendance->breakTimes()->find($breakTimeId);
                if (!$originalBreak) {
                    continue;
                }

                $breakUpdate = new BreakTimeUpdate();
                $breakUpdate->break_time_id     = $originalBreak->id;
                $breakUpdate->update_request_id = $updateRequest->id;
                $breakUpdate->new_break_in = $input['break_in'] !== null && $input['break_in'] !== ''
                    ? $input['break_in']
                    : $originalBreak->break_in;
                $breakUpdate->new_break_out = $input['break_out'] !== null && $input['break_out'] !== ''
                    ? $input['break_out']
                    : $originalBreak->break_out;
                $breakUpdate->save();
            }
        });

        // リダイレクト
        return redirect()
            ->route('requests.index')
            ->with('message', '修正申請を送信しました。');

        // return redirect()->route('attendance.detail', ['id' => $id])
        //     ->with('message', '修正申請を送信しました。');
    }
}
