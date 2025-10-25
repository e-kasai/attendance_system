<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTimeUpdate extends Model
{
    protected $fillable = [
        'new_break_in',
        'new_break_out',
    ];

    protected $casts = [
        'new_break_in' => 'datetime:H:i',
        'new_break_out' => 'datetime:H:i',
    ];

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }

    public function updateRequest()
    {
        return $this->belongsTo(UpdateRequest::class);
    }
}
