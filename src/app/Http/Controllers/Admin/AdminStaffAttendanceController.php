<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;

class AdminStaffAttendanceController extends Controller
{

    public function showStaffList(): View
    {
        // roleがstaffのユーザーを取得
        $users = User::where('role', 'staff')->get();
        return view('admin.staff_index', compact('users'));
    }


    public function showMonthlyAttendances(Request $request, $userId): View
    {
        $user = User::findOrFail($userId);

        //リクエストから表示月を受け取る、なければ今月
        $targetYm = $request->query('target_ym', now()->format('Y-m'));
        $normalizedYm = str_replace('/', '-', $targetYm);
        // $carbonObj = Carbon::createFromFormat('Y-m', str_replace('/', '-', $targetYm));
        $carbonObj = Carbon::createFromFormat('Y-m', $normalizedYm);

        // 選択した月・前月・翌月 表示用は/で
        $selectedMonth = $carbonObj->format('Y/m');
        $prevMonth = $carbonObj->copy()->subMonth()->format('Y/m');
        $nextMonth = $carbonObj->copy()->addMonth()->format('Y/m');

        // 内部用（hidden inputやURLに使う）
        $targetYmForUrl = $carbonObj->format('Y-m');

        // 対象ユーザーの勤怠データ取得
        $attendances = $user->attendances()
            ->whereYear('date', $carbonObj->year)
            ->whereMonth('date', $carbonObj->month)
            ->with('breakTimes')
            ->orderBy('date', 'asc')
            ->get();

        // 共通ビューを使用
        return view('common.attendance_index', compact(
            'attendances',
            'user',
            'selectedMonth',
            'prevMonth',
            'nextMonth',
            'targetYmForUrl'
        ));
    }


    public function exportCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 対象年月
        $targetYm = $request->query('target_ym', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($targetYm . '-01')->startOfMonth();
        $endOfMonth   = Carbon::parse($targetYm . '-01')->endOfMonth();

        //ファイル名は上書きなどファイル重複防止のためHis形式を使用
        $filename =  "{$user->name}_{$targetYm}_attendance_" . now()->format('Ymd_His') . '.csv';

        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date')
            ->get();

        //無名関数外の$attendancesをuseで持ち込みしファイルをwriteモードで開く
        $callback = function () use ($attendances) {
            $handle = fopen('php://output', 'w');

            //OSによる文字化けを防止
            fputs($handle, "\xEF\xBB\xBF");

            //csvファイルのヘッダー
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($attendances as $attendance) {
                if ($attendance->break_time === 0) {
                    $breakTime = '0:00';
                } elseif ($attendance->break_time) {
                    $breakTime = sprintf('%d:%02d', floor($attendance->break_time / 60), $attendance->break_time % 60);
                } else {
                    $breakTime = '';
                }

                if ($attendance->work_time === 0) {
                    $workTime = '0:00';
                } elseif ($attendance->work_time) {
                    $workTime = sprintf('%d:%02d', floor($attendance->work_time / 60), $attendance->work_time % 60);
                } else {
                    $workTime = '';
                }


                //出力用の１行データ
                fputcsv($handle, [
                    Carbon::parse($attendance->date)->format('Y/m/d'),
                    $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $breakTime,
                    $workTime,
                ]);
            }
            //php://output(ブラウザに直接出力する仮想ファイル)を閉じる
            fclose($handle);
        };
        // ResponseFactoryを呼び出してその中のインスタンスメソッドstreamDownloadを呼び出す
        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
