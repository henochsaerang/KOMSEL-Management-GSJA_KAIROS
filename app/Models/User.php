<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * ID tidak auto-increment karena berasal dari API Lama/Sinkronisasi
     */
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'roles',               // Array JSON
        'no_hp',               // Penting untuk WA Broadcast
        'komsel_id',           // Penting untuk Filter Wilayah
        'is_coordinator',      // Boolean
        'scheduling_unlock_until', // Datetime untuk izin input telat
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
            'roles' => 'array',              // Otomatis jadi Array PHP
            'is_coordinator' => 'boolean',
            'scheduling_unlock_until' => 'datetime',
        ];
    }

    // =========================================================================
    // HELPER METHODS (LOGIKA HIERARKI ROLE)
    // =========================================================================

    /**
     * Cek apakah user adalah Leader Komsel (Prioritas 1)
     * Hak Akses: Full Control (CRUD Jadwal, Hapus, Edit, Broadcast)
     */
    public function isLeaderKomsel(): bool
    {
        return in_array('Leaders', $this->roles ?? []);
    }

    /**
     * Cek apakah user adalah Partner (Prioritas 2)
     * Hak Akses: Input Absensi, Input Oikos, Lihat Data (ReadOnly Jadwal)
     */
    public function isPartner(): bool
    {
        return in_array('Partners', $this->roles ?? []);
    }

    /**
     * Cek apakah user adalah OTR (Prioritas 3)
     */
    public function isOTR(): bool
    {
        return in_array('Orang Tua Rohani', $this->roles ?? []);
    }

    /**
     * Cek apakah user punya wewenang Memanajemen Jadwal (Hapus/Edit)
     * Logic: Hanya Super Admin atau Leader Komsel yang boleh.
     */
    public function canManageSchedule(): bool
    {
        $roles = $this->roles ?? [];
        return in_array('super_admin', $roles) || in_array('Leaders', $roles);
    }

    /**
     * Attribute Accessor: Mendapatkan Label Role Utama
     * Digunakan di Blade untuk Badge Warna-warni.
     * Logika: Leader > Partner > OTR > Staff > Anggota
     */
    public function getPrimaryRoleLabelAttribute(): string
    {
        $roles = $this->roles ?? [];

        if (in_array('super_admin', $roles)) return 'Super Admin';
        if (in_array('Leaders', $roles)) return 'Leader Komsel';
        if (in_array('Partners', $roles)) return 'Partner';
        if (in_array('Orang Tua Rohani', $roles)) return 'Orang Tua Rohani';
        if (in_array('Koordinator Bidang', $roles)) return 'Koordinator';
        if (in_array('Staff Kantor', $roles)) return 'Staff';
        
        return 'Anggota';
    }

    // =========================================================================
    // LOGIKA LAINNYA
    // =========================================================================

    /**
     * Cek apakah user boleh input jadwal di luar hari yang ditentukan (Unlock Feature)
     */
    public function canScheduleNow(): bool
    {
        if ($this->scheduling_unlock_until && now()->lessThan($this->scheduling_unlock_until)) {
            return true;
        }
        return false;
    }
    
    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
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
        return substr($initials, 0, 2); // Ambil maks 2 huruf
    }
}