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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // The specific sequence of quiz_question IDs this attempt draws its 25
            // questions from — generated fresh (a fair shuffle of the quiz's full
            // question pool, cycling if the pool has fewer than question_count
            // entries) every time the attempt restarts from question 1, so "Try
            // Again" gets a different draw. Kept on the attempt itself (not
            // regenerated from `position` on every request) so a future Challenge
            // feature can pin a specific sequence by copying this array onto the
            // recipient's attempt instead of each side drawing their own.
            $table->json('question_sequence')->nullable()->after('stopped_at_question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('question_sequence');
        });
    }
};
