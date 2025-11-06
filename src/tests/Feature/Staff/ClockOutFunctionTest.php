<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class ClockOutFunctionTest extends TestCase
{
    use RefreshDatabase;

    //test:退勤ボタンが正しく機能する
    public function test_clock_out_button_is_active_and_working_correctly()
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

        //2. 画面に「退勤」ボタンが表示されていることを確認する
        $response->assertStatus(200);
        $response->assertSeeText('退勤', false);

        //3. 退勤の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'work_end',
        ]);

        //期待：処理後に画面上に表示されるステータスが「退勤済」になる
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    //test:退勤時刻が勤怠一覧画面で確認できる
    public function test_clock_out_time_is_displayed_correctly_on_attendance_index()
    {
        //1. ステータスが勤務外のユーザーにログインする
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 1,
            'date'      => now()->toDateString(),
            'clock_in' => null,
            'clock_out' => null,
        ]);
        $response = $this->actingAs($user)->get(route('attendance.create'));

        //2. 出勤と退勤の処理を行う
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'work_start',
        ]);
        $this->actingAs($user)->post(route('attendance.store'), [
            'action' => 'work_end',
        ]);

        //3. 勤怠一覧画面から退勤の日付を確認する
        // DBの出勤日時を取得(nowでは数秒のずれが生じるため)
        $attendance = Attendance::where('user_id', $user->id)
            ->latest('clock_out')
            ->first();

        $clockOut = $attendance->clock_out->format('H:i');
        $date = \Carbon\Carbon::parse($attendance->date)->translatedFormat('m/d(D)');

        $response = $this->actingAs($user)->get(route('attendances.index'));
        $response->assertStatus(200);
        $response->assertSeeText($date);

        //期待：勤怠一覧画面に退勤時刻が正確に記録されている
        $response->assertSee($clockOut);
    }
}
