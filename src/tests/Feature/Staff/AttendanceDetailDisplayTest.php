<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceDetailDisplayTest extends TestCase
{
    use RefreshDatabase;

    //test:勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function test_attendance_detail_displays_logged_in_user_name()
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
        ]);

        //2. 勤怠詳細ページを開く
        $this->actingAs($user);
        $response = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));

        //3. 名前欄を確認する
        //期待：名前がログインユーザーの名前になっている
        $response->assertSee($user->name);
    }

    //test:勤怠詳細画面の「日付」が選択した日付になっている
    public function test_attendance_detail_displays_selected_date()
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
        ]);

        //2. 勤怠詳細ページを開く
        $this->actingAs($user);
        $response = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));

        //3. 日付欄を確認する
        //期待：日付が選択した日付になっている
        $expected = Carbon::parse($attendance->date)->isoFormat('YYYY年M月D日');
        $response->assertSee($expected);
    }

    //test:「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function test_attendance_detail_displays_correct_clock_in_out_times()
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
        $this->actingAs($user);
        $response = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));

        //3. 出勤・退勤欄を確認する
        //期待：「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    //test:「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_attendance_detail_displays_correct_break_times()
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
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_in'      => '11:00:00',
            'break_out'     => '12:00:00',
        ]);

        //2. 勤怠詳細ページを開く
        $this->actingAs($user);
        $response = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));

        //3. 休憩欄を確認する
        //期待：「休憩」にて記されている時間がログインユーザーの打刻と一致している
        $response->assertSee('11:00');
        $response->assertSee('12:00');
    }
}
