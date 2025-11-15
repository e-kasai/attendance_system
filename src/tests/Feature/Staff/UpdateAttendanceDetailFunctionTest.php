<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\UpdateRequest;

class UpdateAttendanceDetailFunctionTest extends TestCase
{
    use RefreshDatabase;

    //test:出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_validation_error_when_clock_in_is_after_clock_out()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        //2. 勤怠詳細ページを開く
        $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));

        //3. 出勤時間を退勤時間より後に設定し、保存
        $response = $this->actingAs($user)->patch(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '19:00:00',
            'clock_out' => '18:00:00',
            'comment'   => 'テスト用コメント',
        ]);

        //期待：「出勤時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //test:休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_validation_error_when_break_start_is_after_clock_out()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        //2. 勤怠詳細ページを開く
        $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));

        //3. 休憩開始時間を退勤時間より後に設定し保存
        $response = $this->actingAs($user)->patch(route('attendance.update', ['id' => $attendance->id]), [
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
    public function test_validation_error_when_break_end_is_after_clock_out()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        //2. 勤怠詳細ページを開く
        $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));
        //3. 休憩終了時間を退勤時間より後に設定し保存
        $response = $this->actingAs($user)->patch(route('attendance.update', ['id' => $attendance->id]), [
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
    public function test_validation_error_when_comment_is_empty()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        //2. 勤怠詳細ページを開く
        $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));
        //3. 備考欄を未入力のまま保存処理をする
        $response = $this->actingAs($user)->patch(route('attendance.update', ['id' => $attendance->id]), [
            'comment'   => '',
        ]);
        //期待：「備考を記入してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    //test:修正申請処理が実行される
    public function test_update_request_is_created_successfully()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        // 管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);
        // スタッフを作成
        $staff = User::factory()->create(['role' => 'staff']);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        //1. スタッフが勤怠詳細を修正して保存処理を行う
        $this->actingAs($staff)->patch(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'comment'   => 'comment',
        ]);
        // $response->dump();
        // $response->dumpSession();

        //2. 修正申請がDBに作成されていることを確認
        $this->assertDatabaseHas('update_requests', [
            'attendance_id' => $attendance->id,
            'requested_by'  => $staff->id,
            'comment'       => 'comment',
        ]);

        //3. 管理者で申請一覧を確認
        $responseIndex = $this->actingAs($admin)->get(route('requests.index'));

        //期待：修正申請が実行され、管理者の申請一覧画面に表示される
        $responseIndex->assertStatus(200);
        $responseIndex->assertSeeText('comment');

        //4. 管理者で承認画面を確認
        $updateRequest = UpdateRequest::first();
        $responseApprove = $this->actingAs($admin)->get(
            route('admin.request.approve.show', ['attendance_correct_request_id' => $updateRequest->id])
        );

        //期待：承認画面にも申請内容が表示されている
        $responseApprove->assertStatus(200);
        $responseApprove->assertSee('comment');
    }

    //test:「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_pending_tab_displays_all_user_submitted_requests()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        //2. 勤怠詳細を修正し保存処理をする
        $response = $this->actingAs($user)->patch(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '10:00',
            'clock_out' => '17:00',
            'comment'   => 'comment',
        ]);

        //3. 申請一覧画面を確認する
        $response = $this->actingAs($user)->get(
            route('requests.index')
        );
        //期待：申請一覧に自分の申請が全て表示されている
        $response->assertStatus(200);
        $response->assertSee('2025/10/15');
        $response->assertSeeText('comment');
    }

    //test:「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_approved_tab_displays_all_admin_approved_requests()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        //1. スタッフ・管理者を作成
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        //2. 勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        //3. スタッフが修正申請を送信
        $this->actingAs($staff)->patch(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '10:00',
            'clock_out' => '17:00',
            'comment'   => 'comment',
        ]);

        //4. 管理者が承認したと想定（DB操作でステータス変更）
        $updateRequest = UpdateRequest::first();
        $updateRequest->approval_status = 2;
        $updateRequest->approved_at = now();
        $updateRequest->save();

        $updateRequest->refresh();

        //期待：承認済みに管理者が承認した申請が全て表示されている
        $updateRequest = $this->actingAs($staff)->get(route('requests.index', ['status' => 'approved']));
        $updateRequest->assertSeeText('comment');
    }

    //test:各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    public function test_clicking_detail_button_navigates_to_attendance_detail_page()
    {
        // 仮想的に「2025-10-15」を現在日時として固定
        Carbon::setTestNow('2025-10-15 09:00:00');
        //1. 勤怠情報が登録されたユーザーにログインをする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date'    => '2025-10-15',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        //2. 勤怠詳細ページを開く
        $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));
        //2. 勤怠詳細を修正し保存処理をする
        $response = $this->actingAs($user)->patch(route('attendance.update', ['id' => $attendance->id]), [
            'clock_in'  => '10:00',
            'clock_out' => '17:00',
            'comment'   => 'comment',
        ]);

        $updateRequest = UpdateRequest::first();

        //3. 申請一覧画面を開く
        $this->actingAs($user)->get(route('requests.index'))->assertStatus(200);

        //4. 「詳細」ボタンを押す
        $response = $this->actingAs($user)->get(
            route('attendance.detail', [
                'id' => $attendance->id,
                'from' => 'request',
                'update_id' => $updateRequest->id,
            ])
        );
        //期待：勤怠詳細画面に遷移する
        $response->assertStatus(200);
        $response->assertSeeText('勤怠詳細');
    }
}
