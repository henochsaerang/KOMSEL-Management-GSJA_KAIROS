<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OikosVisit extends Model
{
    use HasFactory;

    // Izinkan semua kolom diisi (mass assignable)
    protected $guarded = [];

    /**
     * [PENTING] Relasi ke user "jejak" LOKAL
     * Ini akan memperbaiki 'with('pelayan')' di controller Anda
     */
    public function pelayan()
    {
        // Nama relasinya 'pelayan'
        // Foreign key-nya 'pelayan_user_id'
        // Terhubung ke model 'User'
        return $this->belongsTo(User::class, 'pelayan_user_id');
    }
}