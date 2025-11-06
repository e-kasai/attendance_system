<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceIndexDisplayTest extends TestCase
{

    use RefreshDatabase;

    //test:その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function test_admin_can_view_all_users_attendance_for_today()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);
        // スタッフを複数作成
        $staffs = User::factory()->count(3)->create(['role' => 'staff']);

        // 各スタッフの勤怠データ（当日分）を作成
        $attendances = collect();
        foreach ($staffs as $staff) {
            $attendances->push(
                Attendance::factory()->create([
                    'user_id'   => $staff->id,
                    'date'      => now()->toDateString(),
                    'clock_in'  => '09:00:00',
                    'clock_out' => '18:00:00',
                ])
            );
        }

        //1. 管理者ユーザーにログインする
        $this->actingAs($admin);

        //2. 勤怠一覧画面を開く
        $response = $this->get(route('admin.attendances.index'));

        //期待：その日の全ユーザーの勤怠情報が正確な値になっている
        $today = now()->format('Y/m/d');
        $response->assertSee($today);

        // 全スタッフ名を確認
        foreach ($staffs as $staff) {
            $response->assertSeeText($staff->name);
        }
        // 勤怠時刻を確認
        foreach ($attendances as $attendance) {
            if ($attendance->clock_in) {
                $response->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
            }
            if ($attendance->clock_out) {
                $response->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'));
            }
        }
    }


    //test:遷移した際に現在の日付が表示される
    public function test_current_date_is_displayed_on_admin_attendance_page()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);

        //1. 管理者ユーザーにログインする
        $this->actingAs($admin);

        //2. 勤怠一覧画面を開く
        $response = $this->get(route('admin.attendances.index'));

        //期待：勤怠一覧画面にその日の日付が表示されている
        $today = now()->format('Y/m/d');
        $response->assertSee($today);
    }

    //test:「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_previous_day_button_displays_yesterdays_attendance()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);

        // スタッフを複数作成
        $staffs = User::factory()->count(3)->create(['role' => 'staff']);

        // 各スタッフの勤怠データ(前日)を作成
        $attendances = collect();
        foreach ($staffs as $staff) {
            $attendances->push(
                Attendance::factory()->create([
                    'user_id'   => $staff->id,
                    'date'      => '2025-10-14',
                    'clock_in'  => '09:00:00',
                    'clock_out' => '18:00:00',
                ])
            );
        }
        //1. 管理者ユーザーにログインする
        $this->actingAs($admin);

        //2. 勤怠一覧画面を開く
        $response = $this->get(route('admin.attendances.index'));

        //3. 「前日」ボタンを押す
        $response = $this->get(route('admin.attendances.index', ['target_date' => '2025-10-14']));

        //期待：前日の日付の勤怠情報が表示される
        //前日の日付
        $response->assertSee('2025/10/14');

        // 全スタッフ名
        foreach ($staffs as $staff) {
            $response->assertSeeText($staff->name);
        }
        // 勤怠時刻
        foreach ($attendances as $attendance) {
            if ($attendance->clock_in) {
                $response->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
            }
            if ($attendance->clock_out) {
                $response->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'));
            }
        }
    }

    //test:「翌日」を押下した時に次の日の勤怠情報が表示される
    public function test_next_day_button_displays_tomorrows_attendance()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);

        // スタッフを複数作成
        $staffs = User::factory()->count(3)->create(['role' => 'staff']);

        // 各スタッフの勤怠データ(翌日)を作成
        $attendances = collect();
        foreach ($staffs as $staff) {
            $attendances->push(
                Attendance::factory()->create([
                    'user_id'   => $staff->id,
                    'date'      => '2025-10-16',
                    'clock_in'  => '09:00:00',
                    'clock_out' => '18:00:00',
                ])
            );
        }

        //1. 管理者ユーザーにログインする
        $this->actingAs($admin);

        //2. 勤怠一覧画面を開く
        $response = $this->get(route('admin.attendances.index'));

        //3. 「翌日」ボタンを押す
        $response = $this->get(route('admin.attendances.index', ['target_date' => '2025-10-16']));

        //期待：翌日の日付の勤怠情報が表示される

        //翌日の日付
        $response->assertSee('2025/10/16');

        // 全スタッフ名
        foreach ($staffs as $staff) {
            $response->assertSeeText($staff->name);
        }
        // 勤怠時刻
        foreach ($attendances as $attendance) {
            if ($attendance->clock_in) {
                $response->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
            }
            if ($attendance->clock_out) {
                $response->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'));
            }
        }
    }
}
