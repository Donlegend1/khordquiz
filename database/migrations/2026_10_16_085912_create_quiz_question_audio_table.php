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
        // One row per (question, sound) pair — the per-instrument audio variants a
        // question's single audio_path column has no room for. audio_path here often
        // points at a shared note/chord sample reused across many questions and even
        // many quizzes (e.g. every "Find the Note" question whose answer is "C" points
        // at the same rendered C note for a given sound), not a unique file per row.
        Schema::create('quiz_question_audio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->string('sound');
            $table->string('audio_path');
            $table->timestamps();

            $table->unique(['quiz_question_id', 'sound']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_question_audio');
    }
};
