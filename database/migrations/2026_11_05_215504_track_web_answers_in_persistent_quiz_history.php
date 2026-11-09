<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_answers')) {
            Schema::create('quiz_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('slug');
                $table->string('answer');
                $table->string('picked')->nullable();
                $table->boolean('correct');
                $table->string('kind')->default('single');
                $table->timestamp('answered_at');
                $table->index(['user_id', 'answered_at']);
            });
        }
        if (! Schema::hasColumn('quiz_answers', 'web_answer_id')) {
            Schema::table('quiz_answers', function (Blueprint $table) {
                $table->unsignedBigInteger('web_answer_id')->nullable()->unique();
            });
        }
        DB::table('quiz_attempt_answers as answers')
            ->join('quiz_attempts as attempts', 'attempts.id', '=', 'answers.quiz_attempt_id')
            ->join('quizzes', 'quizzes.id', '=', 'attempts.quiz_id')
            ->join('quiz_questions as questions', 'questions.id', '=', 'answers.quiz_question_id')
            ->select(['answers.id', 'attempts.user_id', 'quizzes.slug', 'questions.answer', 'answers.picked', 'answers.correct', 'answers.created_at'])
            ->orderBy('answers.id')->chunk(500, function ($rows): void {
                DB::table('quiz_answers')->insertOrIgnore($rows->map(fn ($row) => [
                    'web_answer_id' => $row->id, 'user_id' => $row->user_id, 'slug' => $row->slug,
                    'answer' => $row->answer, 'picked' => $row->picked, 'correct' => $row->correct,
                    'kind' => 'single', 'answered_at' => $row->created_at,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropUnique(['web_answer_id']);
            $table->dropColumn('web_answer_id');
        });
    }
};
