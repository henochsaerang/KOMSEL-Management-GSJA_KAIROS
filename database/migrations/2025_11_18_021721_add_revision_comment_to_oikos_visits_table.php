<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oikos_visits', function (Blueprint $table) {
            // Tambahkan kolom untuk komentar revisi, setelah kolom 'status'
            $table->text('revision_comment')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('oikos_visits', function (Blueprint $table) {
            $table->dropColumn('revision_comment');
        });
    }
};
