<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\UpdateRequest;

class UpdateAttendanceInfoTest extends TestCase
{
    use RefreshDatabase;

    //test:管理者は全ユーザーの承認待ち修正申請を確認できる
    public function test_admin_can_view_all_pending_update_requests()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);
        // スタッフを複数作成
        $staffs = User::factory()->count(3)->create(['role' => 'staff']);

        // 各スタッフの勤怠データ作成
        $attendances = collect();
        foreach ($staffs as $staff) {
            // 勤怠データを作成
            $attendance = new Attendance([
                'date'      => '2025-10-15',
                'clock_in'  => '09:00:00',
                'clock_out' => '18:00:00',
            ]);
            $attendance->user_id = $staff->id; // fillable外なので個別代入
            $attendance->save();

            // 承認待ちの修正申請を作成
            $update = new UpdateRequest();
            $update->attendance_id   = $attendance->id;
            $update->requested_by    = $attendance->user_id;
            $update->approval_status = 1;
            $update->comment         = '修正申請テスト';
            $update->save();

            $attendances->push($attendance);
        }

        // DBに「承認待ちの修正申請」があることを確認
        foreach ($attendances as $attendance) {
            $this->assertDatabaseHas('update_requests', [
                'attendance_id'   => $attendance->id,
                'approval_status' => 1,
            ]);
        }

        //1. 管理者でログインする
        $this->actingAs($admin);

        //2. 修正申請一覧ページを開き、承認待ちのタブを開く
        $response = $this->get(route('requests.index', ['status' => 'pending']));
        //期待：全ユーザーの未承認の修正申請が表示される
        foreach ($staffs as $staff) {
            $response->assertSeeText($staff->name);
        }
        $response->assertSeeText('承認待ち');
    }

    //test:管理者は全ユーザーの承認済み修正申請を確認できる
    public function test_admin_can_view_all_approved_update_requests()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);
        // スタッフを複数作成
        $staffs = User::factory()->count(3)->create(['role' => 'staff']);

        // 各スタッフの勤怠データ作成
        $attendances = collect();
        foreach ($staffs as $staff) {
            // 勤怠データを作成
            $attendance = new Attendance([
                'date'      => '2025-10-15',
                'clock_in'  => '09:00:00',
                'clock_out' => '18:00:00',
            ]);
            $attendance->user_id = $staff->id; // fillable外なので個別代入
            $attendance->save();

            // 承認済みの修正申請を作成
            $update = new UpdateRequest();
            $update->attendance_id   = $attendance->id;
            $update->requested_by    = $attendance->user_id;
            $update->approval_status = 2;
            $update->comment         = '修正申請テスト';
            $update->save();

            $attendances->push($attendance);
        }

        // DBに「承認待ちの修正申請」があることを確認
        foreach ($attendances as $attendance) {
            $this->assertDatabaseHas('update_requests', [
                'attendance_id'   => $attendance->id,
                'approval_status' => 2,
            ]);
        }
        //1. 管理者ユーザーにログインをする
        $this->actingAs($admin);
        //2. 修正申請一覧ページを開き、承認済みのタブを開く
        $response = $this->get(route('requests.index', ['status' => 'approved']));

        //期待：全ユーザーの承認済みの修正申請が表示される
        foreach ($staffs as $staff) {
            $response->assertSeeText($staff->name);
        }
        $response->assertSeeText('承認済み');
    }

    //test:管理者は修正申請の詳細内容を正確に確認できる
    public function test_admin_can_view_update_request_detail_correctly()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');

        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);
        // スタッフを複数作成
        $staffs = User::factory()->count(3)->create(['role' => 'staff']);

        // 各スタッフの勤怠データ作成
        $attendances = collect();
        $updates = collect();
        foreach ($staffs as $staff) {
            // 勤怠データを作成
            $attendance = new Attendance([
                'date'      => '2025-10-15',
                'clock_in'  => '09:00:00',
                'clock_out' => '18:00:00',
            ]);
            $attendance->user_id = $staff->id; // fillable外なので個別代入
            $attendance->save();

            // 承認待ちの修正申請を作成
            $update = new UpdateRequest();
            $update->attendance_id   = $attendance->id;
            $update->requested_by    = $attendance->user_id;
            $update->approval_status = 1;
            $update->comment         = '修正申請テスト';
            $update->save();

            $attendances->push($attendance);
            $updates->push($update);
        }
        //1. 管理者ユーザーにログインをする
        $this->actingAs($admin);
        //2. 修正申請の詳細画面を開く
        $response = $this->actingAs($admin)
            ->get(route('admin.request.approve.show', ['attendance_correct_request_id' => $updates->first()->id]));

        //期待：申請内容が正しく表示されている
        $response->assertStatus(200);
        $response->assertSee('修正申請テスト');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    //test:管理者は修正申請を承認し勤怠情報を更新できる
    public function test_admin_can_approve_update_request_and_update_attendance()
    {
        Carbon::setTestNow('2025-10-15 09:00:00');

        // 管理者とスタッフを作成
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        // 勤怠を作成
        $attendance = new Attendance([
            'date'      => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => 4, // 退勤済み
        ]);
        $attendance->user_id = $staff->id;
        $attendance->save();

        // 修正申請を作成
        $update = new UpdateRequest();
        $update->attendance_id   = $attendance->id;
        $update->requested_by    = $staff->id;
        $update->approval_status = 1; // 承認待ち
        $update->new_clock_in    = '10:00:00';
        $update->new_clock_out   = '17:00:00';
        $update->comment         = '時間修正';
        $update->save();

        //1. 管理者ユーザーにログインをする
        $this->actingAs($admin);

        //2. 修正申請の詳細画面で「承認」ボタンを押す
        $response = $this->patch(route('admin.request.approve.update', [
            'attendance_correct_request_id' => $update->id,
        ]));

        // 修正申請が承認済みになっている
        $this->assertDatabaseHas('update_requests', [
            'id'              => $update->id,
            'approval_status' => 2,
        ]);
        //期待：修正申請が承認され、勤怠情報が更新される
        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'clock_in'  => '10:00:00',
            'clock_out' => '17:00:00',
            'is_approved' => true,
        ]);
    }
}
