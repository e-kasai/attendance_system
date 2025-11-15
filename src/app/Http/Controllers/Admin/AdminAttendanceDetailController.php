<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\UpdateRequest;
use Illuminate\View\View;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Services\AttendanceService;


class AdminAttendanceDetailController extends Controller
{
    //サービスクラスをDI
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }


    public function showAttendanceDetail(Request $request, $id): View
    {
        // 管理者は全ユーザーの勤怠を参照できる
        $attendance = Attendance::with(['user', 'breakTimes', 'updateRequests.breakTimeUpdates'])
            ->findOrFail($id);

        $updateId = $request->query('update_id');
        $updateRequest = null;

        // 申請一覧から開いた場合のみ、修正申請データを読み込む
        if ($request->query('from') === 'request' && $updateId) {
            $updateRequest = UpdateRequest::find($updateId);
        }

        if ($updateRequest) {
            // 出退勤プレビュー
            $attendance->clock_in  = $updateRequest->new_clock_in  ?? $attendance->clock_in;
            $attendance->clock_out = $updateRequest->new_clock_out ?? $attendance->clock_out;

            // 既存休憩プレビュー
            foreach ($attendance->breakTimes as $break) {
                $breakUpdate = $updateRequest->breakTimeUpdates
                    ->firstWhere('break_time_id', $break->id);

                if ($breakUpdate) {
                    $break->break_in  = $breakUpdate->new_break_in  ?? $break->break_in;
                    $break->break_out = $breakUpdate->new_break_out ?? $break->break_out;
                }
            }
            // 新規追加された休憩のプレビュー
            $newBreaks = $updateRequest->breakTimeUpdates->whereNull('break_time_id');
            foreach ($newBreaks as $new) {
                $attendance->breakTimes->push(
                    (object) [
                        'id' => null,
                        'break_in' => $new->new_break_in,
                        'break_out' => $new->new_break_out,
                    ]
                );
            }
        }

        // デフォルト：編集可能（例外条件でのみ編集不可になる）
        $isEditable = true;
        $message = null;

        //条件分岐フラグ
        $isFromRequestList   = $updateRequest !== null;       // 「申請一覧 → 詳細画面」のパターンか？
        $hasPendingUpdate = $attendance->updateRequests()     // この勤怠に「未承認の修正申請」が残っているか？
            ->where('approval_status', UpdateRequest::STATUS_PENDING)
            ->exists();


        // 条件分岐
        // 1: 申請一覧経由 ＆ 承認待ち → 編集不可（メッセージ表示なし）
        if ($isFromRequestList && $updateRequest->approval_status === UpdateRequest::STATUS_PENDING) {
            $isEditable = false;
        }

        // 2: 勤怠一覧経由 ＆ 未承認申請が存在 → 編集不可＋メッセージ
        elseif (!$isFromRequestList && $hasPendingUpdate) {
            $isEditable = false;
            $message    = '*承認待ちのため修正はできません。';
        }

        return view('common.attendance_detail', compact('attendance', 'updateRequest', 'isEditable', 'message'));
    }



    //勤怠修正処理
    public function updateAttendanceStatus(UpdateAttendanceRequest $request, $id)
    {
        // 管理者は即時反映
        $validated = $request->validated();

        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        DB::transaction(function () use ($validated, $attendance) {
            // 出退勤の即時更新
            $attendance->update([
                'clock_in'  => $validated['clock_in'] !== null && $validated['clock_in'] !== ''
                    ? $validated['clock_in']
                    : $attendance->clock_in,
                'clock_out' => $validated['clock_out'] !== null && $validated['clock_out'] !== ''
                    ? $validated['clock_out']
                    : $attendance->clock_out,
                'comment'   => $validated['comment'] ?? $attendance->comment,
            ]);

            // サービスクラスで休憩の配列結合処理
            $breaks = $this->attendanceService->mergeBreakRows($validated['breaks'] ?? []);

            foreach ($breaks as $input) {
                $breakTimeId = $input['id'] ?? null;

                // 既存休憩更新
                if ($breakTimeId) {
                    $break = $attendance->breakTimes()->find($breakTimeId);
                    if ($break) {
                        $break->update([
                            'break_in'  => $input['break_in'] !== null && $input['break_in'] !== ''
                                ? $input['break_in']
                                : $break->break_in,
                            'break_out' => $input['break_out'] !== null && $input['break_out'] !== ''
                                ? $input['break_out']
                                : $break->break_out,
                        ]);
                    }
                }
                // 新規休憩追加
                elseif (!empty($input['break_in']) && !empty($input['break_out'])) {
                    // 予備の空行に入力があれば新規休憩レコードを追加
                    $newBreak = $attendance->breakTimes()->create([
                        'break_in'  => $input['break_in'],
                        'break_out' => $input['break_out'],
                    ]);
                    // 追加休憩にidを付与
                    $input['id'] = $newBreak->id;
                }
            }
            $attendance->save();
            $attendance->load('breakTimes');
        });

        return redirect()
            ->route('admin.attendance.update', $attendance->id)
            ->with('message', '勤怠を更新しました。');
    }
}
