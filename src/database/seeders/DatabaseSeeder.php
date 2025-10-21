<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        $user = User::factory()->create([
            'name'  => 'testuser1',
            'email' => 'testuser1@example.com',
        ]);

        $startDate = Carbon::create(2025, 9, 1);

        for ($i = 0; $i < 90; $i++) {
            $date = $startDate->copy()->addDays($i);

            if ($date->isWeekend()) {
                Attendance::factory()
                    ->for($user)
                    ->create(
                        [
                            'date'        => $date->format('Y-m-d'),
                            'clock_in'    => null,
                            'clock_out'   => null,
                            'break_time'  => null,
                            'work_time'   => null,
                        ]
                    );
                continue;
            }

            Attendance::factory()
                ->for($user)
                ->create([
                    'date' => $date->format('Y-m-d'),
                ]);
        }
    }
}
