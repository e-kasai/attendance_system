<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\UpdateRequest;
use Illuminate\View\View;
use App\Http\Requests\UpdateAttendanceRequest;


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
        // $isEditable = true;

        // 承認済みの勤怠のみ編集可能（未承認はロック）
        $isEditable = $attendance->is_approved === true;
        // $message = null;

        $message = $isEditable
            ? null
            : '*承認待ちの為修正はできません。';

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
                $breakTimeId = $input['id'] ?? null;
                // dd($breakTimeId);

                // 既存の休憩を更新
                if ($breakTimeId) {
                    // 既存休憩を更新
                    $break = $attendance->breakTimes()->find($breakTimeId);
                    // dd($break);
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
                    // dd('here');
                    $newBreak = $attendance->breakTimes()->create([
                        'break_in'  => $input['break_in'],
                        'break_out' => $input['break_out'],
                    ]);
                    // 追加休憩にidを付与
                    $input['id'] = $newBreak->id;
                    // dd($newBreak);
                }
            }
            $attendance->save();
            // dd($attendance);
            $attendance->load('breakTimes');
            // dd($attendance);
        });

        // $attendance->refresh();
        return redirect()
            ->route('admin.attendance.update', $attendance->id)
            ->with('message', '勤怠を更新しました。');
    }
}
