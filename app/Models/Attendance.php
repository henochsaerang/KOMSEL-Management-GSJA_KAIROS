<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    /**
     * [FIX 1] Ubah 'user_kairos_id' menjadi 'user_id'
     * Ini adalah kolom yang Anda isi dari KomselController
     */
    protected $fillable = [
        'schedule_id',
        'user_id', 
    ];

    /**
     * Relasi ini masih benar (Schedule -> Attendance)
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * [FIX 2] Hapus relasi 'userKairos'
     * Karena user_id adalah ID dari API, bukan model lokal.
     */
    // public function userKairos()
    // {
    //     ...
    // }
}