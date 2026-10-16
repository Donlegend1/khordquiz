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
        // One row per graded question — lets the results page show a per-question review
        // (what was picked vs. the right answer), which the single-row QuizAttempt can't:
        // it only ever holds the latest attempt's final tally, not a history of each answer.
        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('picked');
            $table->boolean('correct');
            $table->timestamps();

            // A quiz attempt is reused on retry (same row, not a new one — see
            // QuizController::start()), so these rows are cleared and rebuilt each run;
            // this keeps at most one row per question position per attempt.
            $table->unique(['quiz_attempt_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
    }
};
