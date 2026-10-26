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
        Schema::create('player_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('room_question_id')->constrained('room_questions')->cascadeOnDelete();
            $table->string('selected_answer');
            $table->boolean('is_correct');
            $table->integer('score_awarded')->default(0);
            $table->decimal('time_taken_seconds', 8, 4);
            $table->timestamps();

            $table->unique(['player_id', 'room_question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_answers');
    }
};
