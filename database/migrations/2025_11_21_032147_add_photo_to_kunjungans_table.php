<?php
    
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;
    
    return new class extends Migration
    {
        public function up()
        {
            Schema::table('kunjungans', function (Blueprint $table) {
                // Cek agar tidak error jika kolom sudah ada (opsional, tapi aman)
                if (!Schema::hasColumn('kunjungans', 'photo_path')) {
                    $table->string('photo_path')->nullable()->after('catatan');
                }
            });
        }
    
        public function down()
        {
            Schema::table('kunjungans', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }
    };