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

        // "update_id" => $updateRequest->id
        $updateId = $request->query('update_id');
        $update = null;

        // Blade側のリンクで渡されたクエリパラメータ（from=request & update_id）を受け取る
        // 申請一覧から勤怠詳細を開いたときだけ、該当のUpdateRequestを取得する
        if ($request->query('from') === 'request' && $updateId) {
            $update = UpdateRequest::find($updateId);
        }

        if ($update) {
            // 出退勤の修正をプレビュー表示（DB保存はしない）
            $attendance->clock_in  = $update->new_clock_in  ?? $attendance->clock_in;
            $attendance->clock_out = $update->new_clock_out ?? $attendance->clock_out;

            // 休憩の修正をプレビュー表示（DB保存はしない）
            foreach ($attendance->breakTimes as $break) {
                $breakUpdate = $update->breakTimeUpdates
                    ->firstWhere('break_time_id', $break->id);

                if ($breakUpdate) {
                    $break->break_in  = $breakUpdate->new_break_in  ?? $break->break_in;
                    $break->break_out = $breakUpdate->new_break_out ?? $break->break_out;
                }
            }
            // break_time_id が null の休憩（新規追加）もプレビュー表示
            $newBreaks = $update->breakTimeUpdates->whereNull('break_time_id');
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

        //詳細画面（勤怠一覧経由）：修正申請未→編集可&メッセージなし
        //詳細画面（勤怠一覧経由）：修正申請済→承認済みの場合：編集可&メッセージなし
        $isEditable = true;
        $message = null;

        //詳細画面（申請一覧経由）：未承認の場合 = 編集不可&メッセージ表示
        if ($update && $update->approval_status === UpdateRequest::STATUS_PENDING) {
            $isEditable = false;
            $message = '*承認待ちのため修正はできません。';
        }
        //詳細画面（勤怠一覧経由）：修正申請済→未承認の場合：編集不可&メッセージ表示
        elseif (
            ! $update && $attendance->updateRequests()
            ->where('approval_status', UpdateRequest::STATUS_PENDING)
            ->exists()
        ) {
            $isEditable = false;
            $message = '*承認待ちのため修正はできません。';
        }

        return view('common.attendance_detail', compact('attendance', 'user', 'update', 'isEditable', 'message'));
    }

    public function updateAttendanceStatus(UpdateAttendanceRequest $request, $id)
    {
        //勤怠修正（出退勤・休憩）を申請
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


            //別々の配列を１つにまとめる（type=hiddenのbreak_time_idだけ配列が分かれてしまう為）
            $rawBreaks = $validated['breaks'] ?? [];
            // dd($rawBreaks);

            // idだけの要素と、break_in/outだけの要素を分ける
            $ids = array_values(array_filter($rawBreaks, fn($row) => isset($row['id'])));
            $breaks = array_values(array_filter($rawBreaks, fn($row) => isset($row['break_in'])));
            $mergedBreaks = [];

            // 既存休憩idをbreak順に結びつける
            foreach ($breaks as $i => $break) {
                $break['id'] = $ids[$i]['id'] ?? null;
                $mergedBreaks[] = $break;
            }

            // dd($mergedBreaks);

            // 休憩更新（複数対応）
            $breaks = $mergedBreaks;

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
            // $breakUpdate->save();
            $attendance->update(['is_approved' => false]);
        });

        // リダイレクト
        return redirect()
            ->route('requests.index')
            ->with('message', '修正申請を送信しました。');
    }
}
