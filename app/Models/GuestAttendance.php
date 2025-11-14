<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'name',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
