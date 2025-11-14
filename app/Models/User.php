<?php

namespace App\Models; // Sesuaikan namespace Anda

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * [FIX 1] KITA TIDAK INGIN ID LOKAL AUTO-INCREMENT
     * Kita ingin menggunakan ID dari API lama
     */
    public $incrementing = false;
    protected $keyType = 'int'; // Asumsi ID adalah integer

    /**
     * [FIX 2] Pastikan 'id' ada di $fillable
     */
    protected $fillable = [
        'id', 
        'name', 
        'email', 
        'password', 
        'roles'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array', 
        ];
    }

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