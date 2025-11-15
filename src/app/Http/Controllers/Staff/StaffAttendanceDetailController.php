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
use App\Services\AttendanceService;

class StaffAttendanceDetailController extends Controller
{

    //サービスクラスをDI
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    //勤怠詳細ページを表示
    public function showAttendanceDetail(Request $request, $id): View
    {
        $user = auth()->user();

        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['breakTimes', 'updateRequests.breakTimeUpdates'])
            ->firstOrFail();

        $updateId = $request->query('update_id');
        $updateRequest = null;

        // Blade側のリンクで渡されたクエリパラメータ（from=request & update_id）を受け取る
        // 申請一覧から勤怠詳細を開いたときだけ、該当のUpdateRequestを取得する
        if ($request->query('from') === 'request' && $updateId) {
            $updateRequest = UpdateRequest::find($updateId);
        }

        if ($updateRequest) {
            // 出退勤の修正をプレビュー表示（DB保存はしない）
            $attendance->clock_in  = $updateRequest->new_clock_in  ?? $attendance->clock_in;
            $attendance->clock_out = $updateRequest->new_clock_out ?? $attendance->clock_out;

            // 休憩の修正をプレビュー表示（DB保存はしない）
            foreach ($attendance->breakTimes as $break) {
                $breakUpdate = $updateRequest->breakTimeUpdates
                    ->firstWhere('break_time_id', $break->id);

                if ($breakUpdate) {
                    $break->break_in  = $breakUpdate->new_break_in  ?? $break->break_in;
                    $break->break_out = $breakUpdate->new_break_out ?? $break->break_out;
                }
            }
            // break_time_id が null の休憩（新規追加）もプレビュー表示
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
        $isFromRequestList   = $updateRequest !== null;               //「申請一覧 → 詳細画面」のパターンか？
        $hasPendingUpdate = $attendance->updateRequests()
            ->where('approval_status', UpdateRequest::STATUS_PENDING) // この勤怠に「未承認の修正申請」が残っているか？
            ->exists();

        //条件分岐
        // 1: 申請一覧経由 ＆ その申請が未承認 → 編集不可（メッセージ表示有）
        if ($isFromRequestList && $updateRequest->approval_status === UpdateRequest::STATUS_PENDING) {
            $isEditable = false;
            $message = '*承認待ちのため修正はできません。';
        }
        // 2: 勤怠一覧経由 ＆ 未承認申請が存在 → 編集不可
        elseif (!$isFromRequestList && $hasPendingUpdate) {
            $isEditable = false;
            $message = '*承認待ちのため修正はできません。';
        }

        return view('common.attendance_detail', compact('attendance', 'user', 'updateRequest', 'isEditable', 'message'));
    }


    //勤怠修正（出退勤・休憩）を申請
    public function updateAttendanceStatus(UpdateAttendanceRequest $request, $id)
    {
        $user = Auth::user();
        $validated = $request->validated();

        // 自分の対象勤怠を取得
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        DB::transaction(function () use ($validated, $user, $attendance) {
            // 新しい修正申請を登録（出退勤と備考の更新）
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


            // サービスクラスで休憩の配列結合処理
            $breaks = $this->attendanceService->mergeBreakRows($validated['breaks'] ?? []);

            foreach ($breaks as $input) {
                if ($input['id']) {
                    // 既存休憩の修正
                    $breakUpdate = new BreakTimeUpdate();
                    $breakUpdate->break_time_id     = $input['id'] ?? null;
                    $breakUpdate->update_request_id = $updateRequest->id;
                    $breakUpdate->new_break_in      = $input['break_in'];
                    $breakUpdate->new_break_out     = $input['break_out'];
                    $breakUpdate->save();
                } elseif (!empty($input['break_in']) && !empty($input['break_out'])) {
                    // 新規休憩追加
                    $breakUpdate = new BreakTimeUpdate();
                    $breakUpdate->break_time_id     = null;
                    $breakUpdate->update_request_id = $updateRequest->id;
                    $breakUpdate->new_break_in      = $input['break_in'];
                    $breakUpdate->new_break_out     = $input['break_out'];
                    $breakUpdate->save();
                }
            }
            $attendance->update(['is_approved' => false]);
        });

        return redirect()
            ->route('requests.index')
            ->with('message', '修正申請を送信しました。');
    }
}
