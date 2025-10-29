<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\UpdateRequest;
use Illuminate\View\View;


class AdminAttendanceDetailController extends Controller
{
    public function showAttendanceDetail(Request $request, $id): View
    {
        // 管理者は全ユーザーの勤怠を参照できる
        $attendance = Attendance::with(['user', 'breakTimes', 'updateRequests.breakTimeUpdates'])
            ->findOrFail($id);

        $updateId = $request->query('update_id');
        $update = null;

        // 申請一覧から開いた場合のみ、修正申請データを読み込む
        if ($request->query('from') === 'request' && $updateId) {
            $update = UpdateRequest::find($updateId);
        }

        if ($update) {
            // 出退勤プレビュー
            $attendance->clock_in  = $update->new_clock_in  ?? $attendance->clock_in;
            $attendance->clock_out = $update->new_clock_out ?? $attendance->clock_out;

            // 既存休憩プレビュー
            foreach ($attendance->breakTimes as $break) {
                $breakUpdate = $update->breakTimeUpdates
                    ->firstWhere('break_time_id', $break->id);

                if ($breakUpdate) {
                    $break->break_in  = $breakUpdate->new_break_in  ?? $break->break_in;
                    $break->break_out = $breakUpdate->new_break_out ?? $break->break_out;
                }
            }
            // 新規追加された休憩のプレビュー
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
        $isEditable = true;
        $message = null;

        // スタッフと同じビューを使う
        return view('common.attendance_detail', compact('attendance', 'update', 'isEditable', 'message'));
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

            // 休憩更新（複数対応）
            $breaks = $validated['breaks'] ?? [];

            foreach ($breaks as $input) {
                $breakTimeId = $input['id'] ?? null;

                // 既存の休憩を更新 or 空欄なら新規追加
                if ($breakTimeId) {
                    $break = $attendance->breakTimes()->find($breakTimeId);
                    if (!$break) {
                        continue;
                    }

                    $break->update([
                        'break_in'  => $input['break_in'] !== null && $input['break_in'] !== ''
                            ? $input['break_in']
                            : $break->break_in,
                        'break_out' => $input['break_out'] !== null && $input['break_out'] !== ''
                            ? $input['break_out']
                            : $break->break_out,
                    ]);
                } elseif (!empty($input['break_in']) || !empty($input['break_out'])) {
                    // 予備の空行に入力があれば新規休憩レコードを追加
                    $attendance->breakTimes()->create([
                        'break_in'  => $input['break_in'],
                        'break_out' => $input['break_out'],
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.attendances.index')
            ->with('message', '勤怠を更新しました。');
    }
}
