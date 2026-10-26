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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('host_token');
            $table->unsignedInteger('question_count')->default(10);
            $table->string('status')->default('lobby'); // lobby, playing, ended
            $table->unsignedInteger('current_question_index')->default(0);
            $table->decimal('question_started_at', 16, 4)->nullable(); // server microtime
            $table->boolean('is_question_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
