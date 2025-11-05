<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class AttendanceStatusTest extends TestCase
{

    use RefreshDatabase;

    public function test_status_displays_as_off_duty()
    {
        // スタッフユーザーを準備
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        //勤怠ステータス
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 1,
            'date'    => now()->toDateString(), //今日の日付に固定
        ]);

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(
            route('attendance.create')
        );
        //期待：勤務外のステータスが表示されている
        $response->assertStatus(200);
        $response->assertSeeText('勤務外', false);
    }

    //ステータスが「出勤中」のユーザーでログインと出勤中と表示される
    public function test_status_displays_as_working()
    {
        // スタッフユーザーを準備
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        //勤怠ステータス
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 2,
            'date'    => now()->toDateString(),
        ]);

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(
            route('attendance.create')
        );
        //期待：出勤中のステータスが表示されている
        $response->assertStatus(200);
        $response->assertSeeText('出勤中');
    }

    //ステータスが「休憩中」のユーザーでログインすると休憩中と表示される
    public function test_status_displays_as_on_break()
    {
        // スタッフユーザーを準備
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        //勤怠ステータス
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 3,
            'date'    => now()->toDateString(),
        ]);

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(
            route('attendance.create')
        );
        //期待：休憩中のステータスが表示されている
        $response->assertStatus(200);
        $response->assertSeeText('休憩中');
    }

    //ステータスが「退勤済」のユーザーでログインすると退勤済と表示される
    public function test_status_displays_as_finished()
    {
        // スタッフユーザーを準備
        $user = User::factory()->create([
            'role' => 'staff',
        ]);

        //勤怠ステータス
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status'  => 4,
            'date'    => now()->toDateString(),
        ]);

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(
            route('attendance.create')
        );
        //期待：退勤済のステータスが表示されている
        $response->assertStatus(200);
        $response->assertSeeText('退勤済');
    }
}
