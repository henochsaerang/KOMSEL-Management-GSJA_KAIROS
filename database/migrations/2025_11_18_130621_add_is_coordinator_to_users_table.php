<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom is_coordinator jika belum ada
            if (!Schema::hasColumn('users', 'is_coordinator')) {
                $table->boolean('is_coordinator')->default(false)->after('password');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_coordinator')) {
                $table->dropColumn('is_coordinator');
            }
        });
    }
};