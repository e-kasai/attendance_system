<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceDetailDisplayTest extends TestCase
{

    use RefreshDatabase;

    //test:勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_admin_can_view_selected_attendance_detail()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // スタッフと勤怠を作成
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

        //2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        //期待：詳細画面の内容が選択した情報と一致する
        $response->assertSeeText($staff->name);        // スタッフ本人の名前
        $today = Carbon::now()->isoFormat('YYYY年M月D日');
        $response->assertSee($today);        // 日付
        $response->assertSee('09:00');             // 出勤時刻
        $response->assertSee('18:00');             // 退勤時刻
        $response->assertDontSee('他のスタッフの名前'); // 他データが混ざっていない
    }

    //test:出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_validation_error_when_clock_in_is_after_clock_out_for_admin()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // スタッフと勤怠を作成
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
        //2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        //3. 出勤時間を退勤時間より後に設定し保存
        $response = $this->actingAs($admin)->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '19:00:00',
            'clock_out' => '18:00:00',
            'comment'   => 'テスト用コメント',
        ]);

        //期待：「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //test:休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_validation_error_when_break_start_is_after_clock_out_for_admin()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // スタッフと勤怠を作成
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
        //2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        //3. 休憩開始時間を退勤時間より後に設定し保存
        $response = $this->actingAs($admin)->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'comment'   => 'テスト用コメント',
            'breaks' => [
                ['break_in' => '19:00:00', 'break_out' => null],
            ],
        ]);

        //期待：「休憩時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'breaks.0.break_in' => '休憩時間が不適切な値です',
        ]);
    }

    //test:休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_validation_error_when_break_end_is_after_clock_out_for_admin()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // スタッフと勤怠を作成
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

        //2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        //3. 休憩終了時間を退勤時間より後に設定し保存
        $response = $this->actingAs($admin)->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'comment'   => 'テスト用コメント',
            'breaks' => [
                ['break_out' => '19:00:00', 'break_in' => '13:00:00'],
            ],
        ]);
        //期待：「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'breaks.0.break_out' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //test:備考欄が未入力の場合のエラーメッセージが表示される
    public function test_validation_error_when_comment_is_empty_for_admin()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // スタッフと勤怠を作成
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

        //2. 勤怠詳細ページを開く
        $response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        //3. 備考欄を未入力のまま保存処理をする
        $response = $this->actingAs($admin)->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
            'comment'   => '',
        ]);

        //期待：「備考を記入してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }
}
