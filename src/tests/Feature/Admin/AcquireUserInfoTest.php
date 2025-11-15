<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AcquireUserInfoTest extends TestCase
{
    use RefreshDatabase;
    //test:管理者は全ての一般ユーザーの氏名とメールアドレスを確認できる
    public function test_admin_can_view_all_general_users_name_and_email()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);

        // スタッフを複数作成
        $staffs = User::factory()->count(3)->create(['role' => 'staff']);

        //1. 管理者でログインする
        $this->actingAs($admin);

        //2. スタッフ一覧ページを開く
        $response = $this->get(route('admin.staff.index'));
        //期待：全ての一般ユーザーの氏名とメールアドレスが正しく表示されている

        // 全スタッフ名
        foreach ($staffs as $staff) {
            $response->assertSeeText($staff->name);
        }
        // メールアドレス
        foreach ($staffs as $staff) {
            $response->assertSeeText($staff->email);
        }
    }

    //test:管理者は選択したユーザーの勤怠一覧を正確に確認できる
    public function test_admin_can_view_selected_users_attendance_list_correctly()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // スタッフと勤怠を作成
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);
        $attendances = Attendance::factory()->count(3)->sequence(
            ['date' => now()->startOfMonth()->addDays(0)->toDateString()],
            ['date' => now()->startOfMonth()->addDays(1)->toDateString()],
            ['date' => now()->startOfMonth()->addDays(2)->toDateString()],
        )->create([
            'user_id' => $staff->id,
        ]);

        //1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        //2. 選択したユーザーの勤怠一覧ページを開く
        $response = $this->actingAs($admin)->get(route('admin.staff.attendance.index', ['id' => $staff->id]));

        //期待：勤怠情報が正確に表示される
        $response->assertSeeText("{$staff->name}さんの勤怠"); //ユーザーの名前
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

    //test:「前月」ボタン押下で前月の勤怠一覧が表示される
    public function test_prev_month_button_shows_previous_month_attendances()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        //1. 勤怠情報が登録されたスタッフを作成
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date'    => '2025-09-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        //1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        //2. 勤怠一覧ページを開く
        $response = $this->actingAs($admin)->get(route('admin.staff.attendance.index', ['id' => $staff->id]));

        //3. 「前月」ボタンを押す
        $response = $this->actingAs($admin)->get(route('admin.staff.attendance.index', ['target_ym' => '2025-09', 'id' => $staff->id]));

        //期待：前月の情報が表示されている
        $response->assertStatus(200);
        $response->assertSeeText("{$staff->name}さんの勤怠"); //ユーザーの名前
        $response->assertSeeText('2025/09'); //前月である

        if ($attendance->clock_in) {
            $response->assertSee(\Carbon\Carbon::parse($attendance->clock_in)->format('H:i'));
        }
        if ($attendance->clock_out) {
            $response->assertSee(\Carbon\Carbon::parse($attendance->clock_out)->format('H:i'));
        }
    }


    //test:「翌月」ボタン押下で翌月の勤怠一覧が表示される
    public function test_next_month_button_shows_next_month_attendances()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        //1. 勤怠情報が登録されたスタッフを作成
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date'    => '2025-11-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        //2. 勤怠一覧ページを開く
        $response = $this->actingAs($admin)->get(route('admin.staff.attendance.index', ['id' => $staff->id]));
        //3. 「翌月」ボタンを押す
        $response = $this->get(route('admin.staff.attendance.index', ['target_ym' => '2025-11', 'id' => $staff->id]));
        //期待：翌月の情報が表示されている
        $response->assertSeeText("{$staff->name}さんの勤怠"); //ユーザーの名前
        $response->assertSeeText('2025/11'); //翌月である

        if ($attendance->clock_in) {
            $response->assertSee(\Carbon\Carbon::parse($attendance->clock_in)->format('H:i'));
        }
        if ($attendance->clock_out) {
            $response->assertSee(\Carbon\Carbon::parse($attendance->clock_out)->format('H:i'));
        }
    }

    //test:「詳細」ボタン押下で該当日の勤怠詳細画面に遷移できる
    public function test_detail_button_navigates_to_selected_days_attendance_detail()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        //1. 勤怠情報が登録されたスタッフを作成
        $staff = User::factory()->create([
            'role' => 'staff',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        //1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        //2. 勤怠一覧ページを開く
        $response = $this->actingAs($admin)->get(route('admin.staff.attendance.index', ['id' => $staff->id]));
        //3. 「詳細」ボタンを押下する
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        //期待：その日の勤怠詳細画面に遷移する
        $response->assertStatus(200);
        $today = Carbon::now()->isoFormat('YYYY年M月D日');
        $response->assertSee($today);
    }
}
