<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    protected $table = 'kunjungans';
    
    protected $fillable = [
        'pic_id',
        'member_id',
        'nama_anggota_snapshot',
        'tanggal',
        'jenis_kunjungan',
        'catatan',
        'photo_path', // [BARU]
        'status'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }
}