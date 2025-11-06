<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AttendanceFunctionTest extends TestCase
{
    use RefreshDatabase;

    //test:出勤ボタンが正しく機能する
    public function test_clock_in_button_is_active_and_working_correctly()
    {
        // 1. ステータスが勤務外のユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 1,
        ]);
        $response = $this->actingAs($user)->get(route('attendance.create'));

        // 2. 画面に「出勤」ボタンが表示されていることを確認する
        $response->assertStatus(200);
        $response->assertSeeText('出勤');

        // 3. 出勤の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'work_start',
        ]);

        // 期待：画面上に「出勤」ボタンが表示され、処理後に表示されるステータスが「出勤中」になる
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    //test: 出勤は一日一回のみできる
    public function test_clock_in_is_restricted_to_once_per_day()
    {
        // 1. ステータスが退勤済であるユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 4, // 4 = 退勤済
            'date'    => now()->toDateString(), //今日の日付に固定
        ]);
        $response = $this->actingAs($user)->get(route('attendance.create'));

        //期待：画面上に「出勤」ボタンが表示されない
        $response->assertStatus(200);
        $response->assertDontSeeText('出勤');
    }


    //test:出勤時刻が勤怠一覧画面で確認できる
    public function test_clock_in_time_is_displayed_correctly_on_attendance_index()
    {
        // 1. ステータスが勤務外のユーザーにログインする
        $user = User::factory()->create(['role' => 'staff']);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 1, // 勤務外
            'date'    => now()->toDateString(),
        ]);
        $response = $this->actingAs($user)->get(route('attendance.create'));

        // 2. 出勤の処理を行う
        $this->followingRedirects()
            ->actingAs($user)
            ->post(route('attendance.store'), ['action' => 'work_start']);

        // DBの出勤日時を取得(nowでは数秒のずれが生じるため)
        $attendance = Attendance::where('user_id', $user->id)
            ->latest('clock_in')
            ->first();

        $clockIn = $attendance->clock_in->format('H:i');
        $date = \Carbon\Carbon::parse($attendance->date)->translatedFormat('m/d(D)');

        //3. 勤怠一覧画面から出勤の日付を確認する
        $response = $this->actingAs($user)->get(route('attendances.index'));
        $response->assertStatus(200);
        $response->assertSeeText($date);

        //期待：勤怠一覧画面に出勤時刻が正確に記録されている
        $response->assertSee($clockIn);
    }
}
