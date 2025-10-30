<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;


class AttendanceSeeder extends Seeder
{

    public function run(): void
    {
        $patterns = [
            ['in' => '09:00', 'out' => '18:00', 'break_in' => '12:00', 'break_out' => '13:00'],
            ['in' => '09:30', 'out' => '18:30', 'break_in' => '12:30', 'break_out' => '13:30'],
            ['in' => '10:00', 'out' => '19:00', 'break_in' => '13:00', 'break_out' => '14:00'],
        ];

        $staffs = User::where('role', 'staff')->get();

        // 直近3か月分（今月・先月・先々月）
        for ($monthOffset = 0; $monthOffset < 3; $monthOffset++) {   //$monthOffset を 0(今月)〜2(先々月)
            $targetMonth = Carbon::now()->subMonths($monthOffset);  //現在の日時から $monthOffset ヶ月前を取得する
            $start = $targetMonth->copy()->startOfMonth();  //$targetMonth のコピーを作って、その月の「1日」に移動
            $end   = $targetMonth->copy()->endOfMonth();

            foreach ($staffs as $staffIndex => $staff) {
                $date = $start->copy();   //月始め１日

                while ($date <= $end) {
                    // 土日は記録なしで日付だけ表示
                    if ($date->isWeekend()) {

                        Attendance::create(
                            [
                                'user_id'    => $staff->id,
                                'date' => $date->format('Y-m-d'),
                                'clock_in'    => null,
                                'clock_out'    => null,
                                'break_time'  => null,
                                'work_time' => null,
                            ]
                        );
                        $date->addDay();
                        continue;
                    }

                    // パターンをスタッフインデックスと日付に応じてローテーション
                    $pattern = $patterns[($staffIndex + $date->day) % 3];

                    // 勤怠作成
                    $attendance = Attendance::create([
                        'user_id'  => $staff->id,
                        'date'     => $date->format('Y-m-d'),
                        'clock_in' => $date->copy()->setTimeFromTimeString($pattern['in']),
                        'clock_out' => $date->copy()->setTimeFromTimeString($pattern['out']),
                    ]);

                    // 休憩作成
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_in'  => $date->copy()->setTimeFromTimeString($pattern['break_in']),
                        'break_out' => $date->copy()->setTimeFromTimeString($pattern['break_out']),
                    ]);

                    // break_time / work_time を再計算して保存
                    $attendance->save();

                    $date->addDay();
                }
            }
        }
    }
}
