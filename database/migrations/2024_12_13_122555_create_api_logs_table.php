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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string("url")->nullable();
            $table->string("method")->nullable();
            $table->json("input")->nullable();
            $table->string("status_code")->nullable();
            $table->string("user_id")->nullable();
            $table->string("token")->nullable();
            $table->json("headers")->nullable();
            $table->json("output")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
