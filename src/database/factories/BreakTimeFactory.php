<?php

namespace Database\Factories;

use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition(): array
    {
        $breakIn = Carbon::today()->setTime(rand(12, 13), rand(0, 30));
        $breakOut = (clone $breakIn)->addMinutes(rand(45, 75));

        return [
            'break_in'  => $breakIn->format('H:i'),
            'break_out' => $breakOut->format('H:i'),
        ];
    }
}
