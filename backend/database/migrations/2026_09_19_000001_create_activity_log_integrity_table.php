<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log_integrity', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_log_id')->unique();
            $table->char('previous_hash', 64)->nullable();
            $table->char('payload_hash', 64);
            $table->char('chain_hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log_integrity');
    }
};
