<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// [FIX 1] Tambahkan ini untuk mendefinisikan tipe relasi
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    // Izinkan semua kolom diisi
    protected $guarded = [];

    /**
     * [FIX 2] Definisikan relasi "has many" ke model Attendance.
     * Ini akan memperbaiki error 'undefined method attendances()'.
     */
    public function attendances(): HasMany
    {
        // Ini terhubung ke model Attendance
        return $this->hasMany(Attendance::class);
    }

    /**
     * [FIX 3] Definisikan juga relasi ke GuestAttendance.
     * Ini akan memperbaiki error 'withCount('guestAttendances')'.
     */
    public function guestAttendances(): HasMany
    {
        // Ini terhubung ke model GuestAttendance
        return $this->hasMany(GuestAttendance::class);
    }
}