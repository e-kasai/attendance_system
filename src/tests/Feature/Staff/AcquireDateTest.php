<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;

class AcquireDateTest extends TestCase
{

    use RefreshDatabase;

    public function test_acquire_current_date()
    {
        // スタッフユーザーを準備
        $user = User::factory()->create([
            'role' => 'staff',
        ]);
        //仮の時刻を固定（テスト実行の瞬間とBladeのnow()のズレ対策）
        Carbon::setTestNow(Carbon::parse('2025-11-05 15:45'));

        // ログインして勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(
            route('attendance.create')
        );

        //期待1:勤怠打刻画面にアクセスできる
        $response->assertOk();

        // Bladeで表示されるフォーマットに合わせて現在日時を生成
        $today = Carbon::now()->isoFormat('YYYY年M月D日(ddd)');
        $time = Carbon::now()->format('H:i');

        // 期待2: 現在日時が画面に表示されている
        $response->assertSeeText($today);
        $response->assertSeeText($time);
    }
}
