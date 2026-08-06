<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarded because the table was created manually in some environments
        // before this migration existed.
        if (Schema::hasTable('mqr_data')) {
            return;
        }

        Schema::create('mqr_data', function (Blueprint $table): void {
            $table->id();
            $table->string('kod_rujukan_akreditasi_penuh', 191)->unique();
            $table->string('id_institusi')->nullable();
            $table->text('nama_institusi')->nullable();
            $table->string('kod_nec')->nullable();
            $table->text('bidang_nec')->nullable();
            $table->text('nama_kelayakan_bm')->nullable();
            $table->text('nama_kelayakan_bi')->nullable();
            $table->text('nama_kelayakan_baru_bm')->nullable();
            $table->text('nama_kelayakan_baru_bi')->nullable();
            $table->string('peringkat_kelayakan')->nullable();
            $table->string('tahap')->nullable();
            $table->string('tarikh_akreditasi_mula')->nullable();
            $table->string('tarikh_akreditasi_tamat')->nullable();
            $table->string('tarikh_henti_tamat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mqr_data');
    }
};
