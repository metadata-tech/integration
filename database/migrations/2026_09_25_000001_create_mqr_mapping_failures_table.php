<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mqr_mapping_failures', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('run_id')
                ->constrained('mqr_mapping_runs')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('mqr_data_id');
            $table->string('kod_mqa')->nullable();
            $table->text('nama_institusi')->nullable();
            $table->text('nama_kelayakan')->nullable();
            $table->text('reason');
            $table->boolean('missing_institution')->default(false);
            $table->boolean('missing_specialization')->default(false);
            $table->boolean('ambiguous_institution')->default(false);
            $table->timestamps();

            $table->unique(['run_id', 'mqr_data_id']);
            $table->index(['run_id', 'missing_institution']);
            $table->index(['run_id', 'missing_specialization']);
            $table->index(['run_id', 'ambiguous_institution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mqr_mapping_failures');
    }
};
