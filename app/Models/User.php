<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * [FIX 1] Matikan auto-increment karena ID berasal dari API/Manual
     */
    public $incrementing = false;
    protected $keyType = 'int'; 

    /**
     * [FIX 2] Tambahkan 'is_coordinator' agar bisa di-update via Controller
     */
    protected $fillable = [
        'id', 
        'name', 
        'email', 
        'password', 
        'roles',
        'is_coordinator' // [BARU] Penting untuk fitur penobatan
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts untuk konversi otomatis tipe data
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
            'is_coordinator' => 'boolean',
            'scheduling_unlock_until' => 'datetime', // [WAJIB TAMBAH] Casting kolom baru
        ];
    }

    // [WAJIB TAMBAH] Tambahkan ke array $fillable di atas juga:
    // 'scheduling_unlock_until',

    // [WAJIB TAMBAH] Fungsi Logic Waktu
    public function canScheduleNow(): bool
    {
        if ($this->scheduling_unlock_until && now()->lessThan($this->scheduling_unlock_until)) {
            return true;
        }
        return false;
    }
    
    // [WAJIB TAMBAH] Relasi Notifikasi
    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    // Helper untuk inisial nama
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return $initials;
    }
}