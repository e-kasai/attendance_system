<?php

namespace App\Services;

use App\Models\User;

class AttendanceService
{
    // 今日すでに出勤しているか?
    public function hasClockedInToday(User $user)
    {
        return $user->attendances()
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->exists();
    }

    // 今日すでに退勤しているか?
    public function hasClockedOutToday(User $user)
    {
        return $user->attendances()
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_out')
            ->exists();
    }

    //勤怠処理の分岐
    public function handleAction($user, string $action)
    {
        return match ($action) {
            'work_start' => $this->startWork($user),
            'work_end'   => $this->endWork($user),
            'break_in'   => $this->startBreak($user),
            'break_out'  => $this->endBreak($user),
            default      => null,
        };
    }

    // 出勤処理
    public function startWork(User $user)
    {
        $attendance = $user->todayAttendance();

        if ($this->hasClockedInToday($user)) {
            return $attendance;
        }

        return $user->attendances()->firstOrCreate(
            ['date' => now()->toDateString()],
            ['clock_in' => now(), 'status' => 2]
        );
    }

    // 退勤処理
    public function endWork(User $user)
    {
        $attendance = $user->todayAttendance();

        if ($this->hasClockedOutToday($user)) {
            return $attendance;
        }

        if ($attendance && !$attendance->clock_out) {
            $attendance->update([
                'clock_out' => now(),
                'status' => 4,
            ]);
        }

        return $attendance;
    }

    // 休憩開始
    public function startBreak(User $user)
    {
        $attendance = $user->todayAttendance();

        //すでに休憩中なら新しい休憩を作らない
        if ($attendance->breakTimes()->whereNull('break_out')->exists()) {
            return $attendance;
        }
        //休憩中でも退勤済みでもない = 新しい休憩を開始
        if ($attendance && !$attendance->clock_out) {
            $attendance->breakTimes()->create([
                'break_in' => now(),
            ]);
            $attendance->update(['status' => 3]);
        }
        return $attendance;
    }


    // 休憩終了
    public function endBreak(User $user)
    {
        $attendance = $user->todayAttendance();
        $activeBreak = null;

        if ($attendance && !$attendance->clock_out) {
            $activeBreak = $attendance->breakTimes()
                ->whereNull('break_out')
                ->latest()
                ->first();
        }

        if (!empty($activeBreak)) {
            $activeBreak->update(['break_out' => now()]);
            $attendance->status = 2;
            $attendance->save();
            return $activeBreak;
        }
        return null;
    }

    //休憩の配列を1つにまとめる（idだけ配列が分かれてしまう為）
    public function mergeBreakRows(array $rawBreaks): array
    {
        // idだけの要素と、break_in/outだけの要素を分ける
        $ids = array_values(array_filter($rawBreaks, fn($row) => isset($row['id'])));
        $breaks = array_values(array_filter($rawBreaks, fn($row) => isset($row['break_in'])));

        // 既存休憩idをbreak順に結びつける
        foreach ($breaks as $index => &$breakRow) {
            $breakRow['id'] = $ids[$index]['id'] ?? null;
        }

        return $breaks;
    }
}
