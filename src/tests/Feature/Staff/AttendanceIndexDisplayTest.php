<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;


class AttendanceIndexDisplayTest extends TestCase
{
    use RefreshDatabase;

    //test:自分が行った勤怠情報が全て表示されている
    public function test_all_attendance_records_are_displayed_for_logged_in_user()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        //1. 勤怠情報が登録されたユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendances = Attendance::factory()->count(3)->sequence(
            ['date' => now()->startOfMonth()->addDays(0)->toDateString()],
            ['date' => now()->startOfMonth()->addDays(1)->toDateString()],
            ['date' => now()->startOfMonth()->addDays(2)->toDateString()],
        )->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        //2. 勤怠一覧ページを開く
        $response = $this->get(route('attendances.index'));

        //期待：自分の勤怠情報が全て表示されている
        foreach ($attendances as $attendance) {
            $formattedDate = \Carbon\Carbon::parse($attendance->date)->translatedFormat('m/d(D)');
            $response->assertSee($formattedDate);
            if ($attendance->clock_in) {
                $response->assertSee(\Carbon\Carbon::parse($attendance->clock_in)->format('H:i'));
            }
            if ($attendance->clock_out) {
                $response->assertSee(\Carbon\Carbon::parse($attendance->clock_out)->format('H:i'));
            }
        }
    }

    //test:勤怠一覧画面に遷移した際に現在の月が表示される
    public function test_current_month_is_displayed_on_attendance_index()
    {

        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        //1. ユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $this->actingAs($user);

        //2. 勤怠一覧ページを開く
        $response = $this->get(route('attendances.index'));

        //期待：現在の月が表示されている
        $expectedMonth = now()->format('Y/m');
        $response->assertSee($expectedMonth);
    }

    //test:「前月」を押下した時に表示月の前月の情報が表示される
    public function test_previous_month_button_displays_previous_month_records()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendances = Attendance::factory()->count(3)->sequence(
            ['date' => '2025-09-01'],
            ['date' => '2025-09-02'],
            ['date' => '2025-09-03'],
        )->create(['user_id' => $user->id]);

        $this->actingAs($user);

        //2. 勤怠一覧ページを開く
        $response = $this->get(route('attendances.index'));

        //3. 「前月」ボタンを押す
        $this->actingAs($user);
        $response = $this->get(route('attendances.index', ['target_ym' => '2025-09']));

        //期待：前月の情報が表示されている
        $response->assertSee('2025/09');
        foreach ($attendances as $attendance) {
            $formattedDate = Carbon::parse($attendance->date)->translatedFormat('m/d(D)');
            $response->assertSee($formattedDate);
            if ($attendance->clock_in) {
                $response->assertSee(\Carbon\Carbon::parse($attendance->clock_in)->format('H:i'));
            }
            if ($attendance->clock_out) {
                $response->assertSee(\Carbon\Carbon::parse($attendance->clock_out)->format('H:i'));
            }
        }
    }


    //test:「翌月」を押下した時に表示月の前月の情報が表示される
    public function test_next_month_button_displays_next_month_records()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendances = Attendance::factory()->count(3)->sequence(
            ['date' => '2025-11-01'],
            ['date' => '2025-11-02'],
            ['date' => '2025-11-03'],
        )->create(['user_id' => $user->id]);

        $this->actingAs($user);

        //2. 勤怠一覧ページを開く
        $response = $this->get(route('attendances.index'));

        //3. 「翌月」ボタンを押す
        $this->actingAs($user);
        $response = $this->get(route('attendances.index', ['target_ym' => '2025-11']));

        //翌月の情報が表示されている
        $response->assertSee('2025/11');
        foreach ($attendances as $attendance) {
            $formattedDate = Carbon::parse($attendance->date)->translatedFormat('m/d(D)');
            $response->assertSee($formattedDate);
            if ($attendance->clock_in) {
                $response->assertSee(\Carbon\Carbon::parse($attendance->clock_in)->format('H:i'));
            }
            if ($attendance->clock_out) {
                $response->assertSee(\Carbon\Carbon::parse($attendance->clock_out)->format('H:i'));
            }
        }
    }

    //test:「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_clicking_detail_button_redirects_to_attendance_detail_page()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendances = Attendance::factory()->count(3)->sequence(
            ['date' => '2025-10-01'],
            ['date' => '2025-10-02'],
            ['date' => '2025-10-03'],
        )->create(['user_id' => $user->id]);

        //2. 勤怠一覧ページを開く
        $response = $this->get(route('attendances.index'));

        //3. 「詳細」ボタンを押下する
        $targetAttendance = $attendances->first();
        $this->actingAs($user);
        $detailUrl = route('attendance.detail', $targetAttendance->id);
        $response = $this->get($detailUrl);

        //期待：その日の勤怠詳細画面に遷移する
        $response->assertStatus(200);
        $formattedDate = Carbon::parse($targetAttendance->date)->format('Y年n月j日');
        $response->assertSee($formattedDate);
    }
}
