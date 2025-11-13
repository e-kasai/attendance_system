<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UpdateRequest;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;


class AdminRequestApprovalController extends Controller
{
    // 修正申請承認画面の表示
    public function showApprovalPage($updateId): View
    {
        // $update（修正申請レコード）と関連情報の取得
        $update = UpdateRequest::with(['attendance.user', 'attendance.breakTimes', 'breakTimeUpdates'])
            ->findOrFail($updateId);


        // 勤怠内容に申請内容を反映(DBに保存はしない)
        $attendance = $update->attendance;
        $attendance->clock_in  = $update->new_clock_in  ?? $attendance->clock_in;
        $attendance->clock_out = $update->new_clock_out ?? $attendance->clock_out;


        // 既存の休憩
        $mergedBreaks = $attendance->breakTimes->map(function ($break) use ($update) {
            $breakUpdate = $update->breakTimeUpdates->firstWhere('break_time_id', $break->id);
            if ($breakUpdate) {
                $break->break_in  = $breakUpdate->new_break_in  ?? $break->break_in;
                $break->break_out = $breakUpdate->new_break_out ?? $break->break_out;
            }
            return $break;
        });

        // 新規追加の休憩（break_time_idがnull）
        $newBreaks = $update->breakTimeUpdates->whereNull('break_time_id')->map(function ($breakUpdate) {
            return (object) [
                'id'        => null,
                'break_in'  => $breakUpdate->new_break_in,
                'break_out' => $breakUpdate->new_break_out,
                'break_in'  => \Carbon\Carbon::parse($breakUpdate->new_break_in)->format('H:i'),
                'break_out' => \Carbon\Carbon::parse($breakUpdate->new_break_out)->format('H:i'),
            ];
        });

        //新規、既存休憩両方
        $allBreaks = $mergedBreaks->concat($newBreaks);

        // フォーム入力は常に編集不可（修正申請承認画面ではフォームの編集は不可の仕様）
        $isEditable = false;
        //承認ボタン切り替えフラグ
        if ($update->approval_status === UpdateRequest::STATUS_PENDING) {
            $btnActivate = true;
            $message = '*承認待ちのため修正はできません。';
        } else {
            $btnActivate = false;
            $message = null;
        }

        //修正申請を反映して修正申請承認画面を表示
        return view('admin.request_approve', [
            'attendance' => $attendance,
            'user' => $attendance->user,
            'update' => $update,
            'isEditable' => $isEditable,
            'allBreaks' => $allBreaks,
            'btnActivate' => $btnActivate,
            'message' => $message
        ]);
    }

    public function approveUpdatedRequest(Request $request, $updateId)
    {
        $update = UpdateRequest::with(['attendance.breakTimes', 'breakTimeUpdates'])
            ->findOrFail($updateId);
        $attendance = $update->attendance;

        try {
            DB::transaction(function () use ($update, $attendance) {
                // 修正申請を承認済みに更新
                $update->approval_status = UpdateRequest::STATUS_APPROVED;
                $update->approved_at = now();
                $update->save();

                // 出退勤のupdate
                $updateData = [];
                if ($update->new_clock_in) {
                    $updateData['clock_in'] = $update->new_clock_in;
                }
                if ($update->new_clock_out) {
                    $updateData['clock_out'] = $update->new_clock_out;
                }
                if (!empty($updateData)) {
                    $attendance->update($updateData);
                }

                // 休憩のupdate
                foreach ($update->breakTimeUpdates as $breakUpdate) {
                    if ($breakUpdate->break_time_id) {
                        // 既存休憩の更新
                        $break = $attendance->breakTimes()->find($breakUpdate->break_time_id);
                        if ($break) {
                            $break->update([
                                'break_in'  => $breakUpdate->new_break_in ?? $break->break_in,
                                'break_out' => $breakUpdate->new_break_out ?? $break->break_out,
                            ]);
                        }
                    } else {
                        // 新しい休憩の追加
                        $newBreak = $attendance->breakTimes()->create([
                            'break_in'  => $breakUpdate->new_break_in,
                            'break_out' => $breakUpdate->new_break_out,
                        ]);

                        // 作成された休憩IDを申請レコードに反映
                        $breakUpdate->break_time_id = $newBreak->id;
                        $breakUpdate->save();
                    }
                }

                // 全ての反映後に承認フラグと再計算を実行
                $attendance->is_approved = true;
                $attendance->save(); // savingイベント発火で work_time / break_time 再計算
            });

            return redirect()
                ->route('admin.request.approve.show', $update->id)
                ->with('message', '申請を承認しました。');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors('承認処理中にエラーが発生しました。');
        }
    }
}
