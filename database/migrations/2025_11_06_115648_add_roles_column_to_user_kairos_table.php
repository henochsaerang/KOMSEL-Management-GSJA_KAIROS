<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('user_kairos', function (Blueprint $table) {
            // Menambahkan kolom 'roles' setelah kolom 'status'
            $table->json('roles')->nullable()->after('status');
        });
    }
    public function down(): void {
        Schema::table('user_kairos', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
