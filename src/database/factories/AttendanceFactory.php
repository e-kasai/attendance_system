<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        // fakerで日付を作る
        $date = $this->faker->dateTimeBetween('-3 months', 'now');

        // 同じ日付の中で出勤・退勤を作る
        $clockIn = Carbon::instance($date)->setTime(9, rand(0, 15));
        $clockOut = Carbon::instance($date)->setTime(18, rand(0, 30));

        return [
            'date'       => $date->format('m/d'),
            'clock_in'   => $clockIn->format('H:i'),
            'clock_out'  => $clockOut->format('H:i'),
            'break_time' => null,
            'work_time'  => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($attendance) {
            // 休日（出勤なし）の場合はスキップ
            if (is_null($attendance->clock_in) || is_null($attendance->clock_out)) {
                return;
            }

            // 休憩データを作成
            $break = BreakTime::factory()->make();
            $attendance->breakTimes()->save($break);

            // 休憩時間（分）を算出
            $breakMinutes = Carbon::parse($break->break_in)
                ->diffInMinutes(Carbon::parse($break->break_out));

            // 実働時間 = 退勤 - 出勤 - 休憩
            $workMinutes = Carbon::parse($attendance->clock_in)
                ->diffInMinutes(Carbon::parse($attendance->clock_out))
                - $breakMinutes;

            $attendance->update([
                'break_time' => $breakMinutes,
                'work_time'  => $workMinutes,
            ]);
        });
    }
}
