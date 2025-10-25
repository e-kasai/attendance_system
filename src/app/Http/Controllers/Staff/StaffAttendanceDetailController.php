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

class StaffAttendanceDetailController extends Controller
{
    public function showAttendanceDetail($id): View
    {
        $user = auth()->user();

        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->with('breakTimes')
            ->firstOrFail();

        return view('common.attendance_detail', compact('attendance', 'user'));
    }

    public function updateAttendanceStatus(Request $request, $id)
    {
        // ログイン中ユーザーを取得
        $user = Auth::user();

        // 対象勤怠を取得（自分以外はエラー）
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        DB::transaction(function () use ($request, $user, $attendance) {
            // 新しい修正申請を登録
            $updateRequest = new UpdateRequest();
            $updateRequest->attendance_id   = $attendance->id;
            $updateRequest->requested_by    = $user->id;
            $updateRequest->approval_status = 0; // 未承認
            $updateRequest->new_clock_in    = $request->input('clock_in');
            $updateRequest->new_clock_out   = $request->input('clock_out');
            $updateRequest->comment         = $request->input('comment');
            $updateRequest->save();

            // 休憩修正申請（複数対応）
            $breakInputs = $request->input('breaks', []);

            foreach ($breakInputs as $breakInput) {
                $breakTimeId = $breakInput['id'] ?? null;
                if (!$breakTimeId) {
                    continue; // idがなければスキップ
                }

                // 勤怠に紐づく休憩の中から対象を取得
                $originalBreak = $attendance->breakTimes()->find($breakTimeId);
                if (!$originalBreak) {
                    continue; // 自分の勤怠に存在しない場合もスキップ
                }

                $breakUpdate = new BreakTimeUpdate();
                $breakUpdate->break_time_id     = $originalBreak->id;
                $breakUpdate->update_request_id = $updateRequest->id;
                $breakUpdate->new_break_in      = $breakInput['break_in'] ?? null;
                $breakUpdate->new_break_out     = $breakInput['break_out'] ?? null;
                $breakUpdate->save();
            }
        });

        // リダイレクト
        return redirect()
            ->route('requests.index', ['status' => 'pending'])
            ->with('message', '修正申請を送信しました。');
    }
}
