<?php

namespace Database\Seeders;

use App\Models\userKairos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserKairosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        userKairos::create([
            'nama' => 'Henoch Saerang',
            'komsel' => 'Revology',
            'email' => 'henochsaerang@gmail.com',
            'pass' => Hash::make('henok12345'),
        ]);
    }
}
