<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mqr_mapping_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('status', 20);
            $table->unsignedTinyInteger('active_slot')
                ->nullable()
                ->unique();
            $table->unsignedBigInteger('max_mqr_id')->default(0);
            $table->unsignedBigInteger('last_mqr_id')->default(0);
            $table->unsignedBigInteger('total_records')->default(0);
            $table->unsignedBigInteger('processed_count')->default(0);
            $table->unsignedBigInteger('success_count')->default(0);
            $table->unsignedBigInteger('failure_count')->default(0);
            $table->unsignedInteger('batch_number')->default(0);
            $table->string('report_path', 500)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mqr_mapping_runs');
    }
};
