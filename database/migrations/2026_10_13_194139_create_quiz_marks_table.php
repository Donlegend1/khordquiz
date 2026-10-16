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
        // Deliberately separate from quiz_attempts: bookmarking or manually
        // marking a quiz "complete" from its preview card is independent of
        // actually playing it, and must never feed dashboard accuracy, the
        // "quizzes done" count, or achievements — those stay tied to real
        // QuizAttempt rows with real correct_answers.
        Schema::create('quiz_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->boolean('bookmarked')->default(false);
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'quiz_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_marks');
    }
};
