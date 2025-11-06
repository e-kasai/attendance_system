<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class BreakFunctionTest extends TestCase
{
    use RefreshDatabase;

    //test: 休憩ボタンが正しく機能する
    public function test_break_in_button_functions_correctly()
    {
        //1. ステータスが出勤中のユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 2, //出勤中
            'date'      => now()->toDateString(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        //2. 画面に「休憩入」ボタンが表示されていることを確認する
        $response->assertStatus(200);
        $response->assertSeeText('休憩入', false);

        //3. 休憩の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_in',
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 3, // 休憩中
        ]);

        //期待：処理後に表示されるステータスが「休憩中」になる
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    //test: 休憩は一日に何回でもできる
    public function test_user_can_take_multiple_breaks_per_day()
    {
        //1. ステータスが出勤中であるユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 2, //出勤中
            'date'      => now()->toDateString(),
            'clock_out' => null,
        ]);
        $response = $this->actingAs($user)->get(route('attendance.create'));

        //2. 休憩入と休憩戻の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_in',
        ]);
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_out',
        ]);

        //期待：画面上に「休憩入」ボタンが表示される
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertSeeText('休憩入', false);
    }


    //test: 休憩戻ボタンが正しく機能する
    public function test_break_out_button_functions_correctly()
    {

        //1. ステータスが出勤中であるユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 2, //出勤中
            'date'      => now()->toDateString(),
            'clock_out' => null,
        ]);
        $response = $this->actingAs($user)->get(route('attendance.create'));

        //2. 休憩入の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_in',
        ]);

        //3. 休憩戻の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_out',
        ]);

        //期待：処理後にステータスが「出勤中」に変更される
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertSee('出勤中');
    }


    //test:休憩戻は一日に何回でもできる
    public function test_user_can_resume_work_multiple_times_per_day()
    {
        //1. ステータスが出勤中であるユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 2, //出勤中
            'date'      => now()->toDateString(),
            'clock_out' => null,
        ]);
        $response = $this->actingAs($user)->get(route('attendance.create'));

        //2. 休憩入と休憩戻の処理を行い、再度休憩入の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_in',
        ]);
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_out',
        ]);
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_in',
        ]);

        //期待：画面上に「休憩戻」ボタンが表示される
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertSeeText('休憩戻', false);
    }


    //test:休憩時刻が勤怠一覧画面で確認できる
    public function test_break_time_is_displayed_correctly_on_attendance_index()
    {
        //1. ステータスが出勤中のユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 2, //出勤中
            'date'      => now()->toDateString(),
            'clock_out' => null,
        ]);
        $response = $this->actingAs($user)->get(route('attendance.create'));

        //2. 休憩入と休憩戻の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_in',
        ]);
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'break_out',
        ]);

        //3. 退勤時に休憩時間が計算されるため退勤処理を行う
        $this->actingAs($user)->post(route('attendance.store'), ['action' => 'work_end']);

        //4. 勤怠一覧を開く
        $response = $this->actingAs($user)->get(route('attendances.index'));

        //5. 日付を取得
        $date = \Carbon\Carbon::parse($attendance->date)->translatedFormat('m/d(D)');

        //6. DBの合計休憩時間を取得
        $formattedBreak = sprintf('%d:%02d', floor($attendance->break_time / 60), $attendance->break_time % 60);

        //期待：勤怠一覧画面に休憩（合計時間）が正確に記録されている
        $response->assertStatus(200);
        $response->assertSeeText($date);
        $response->assertSeeText($formattedBreak);
    }
}
