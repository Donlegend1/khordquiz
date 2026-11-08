<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MajorGroupThreeCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_group_three_major_seventh_rows_are_removed_and_positions_are_closed(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Major Chord Extentions · Level 7', 'category' => 'Major Chord Extensions']);
        $questions = [];
        foreach ([1 => 'level1/piano/f-maj7-v1', 2 => 'level3/piano/f-maj7-v1', 3 => 'level3/piano/f-maj7s11-v1'] as $position => $path) {
            $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => $position]);
            QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'Piano', 'audio_path' => 'major-chord-extensions/'.$path.'.wav']);
            $questions[$position] = $question;
        }
        $naming = Quiz::factory()->create(['title' => 'Chord Naming · Level 1', 'category' => 'Chord Naming']);
        $namingQuestion = QuizQuestion::factory()->create(['quiz_id' => $naming->id]);
        $namingBefore = $namingQuestion->fresh()->getAttributes();
        $affected = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'status' => QuizAttempt::STATUS_IN_PROGRESS, 'question_sequence' => [$questions[2]->id]]);
        $unaffected = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'status' => QuizAttempt::STATUS_IN_PROGRESS, 'question_sequence' => [$questions[1]->id, $questions[3]->id]]);
        $beforeAttempt = $unaffected->fresh()->getAttributes();
        $previousArguments = $_SERVER['argv'] ?? [];
        $_SERVER['argv'][] = '--apply';
        ob_start();
        try {
            require base_path('scripts/audio/remove_group3_major_seventh.php');
            require base_path('scripts/audio/remove_group3_major_seventh.php');
        } finally {
            $_SERVER['argv'] = $previousArguments;
            ob_end_clean();
        }
        $this->assertDatabaseMissing('quiz_questions', ['id' => $questions[2]->id]);
        $this->assertDatabaseMissing('quiz_question_audio', ['quiz_question_id' => $questions[2]->id]);
        $this->assertNotNull($questions[1]->fresh());
        $this->assertSame(2, $questions[3]->fresh()->position);
        $this->assertSame($namingBefore, $namingQuestion->fresh()->getAttributes());
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $affected->id]);
        $this->assertSame($beforeAttempt, $unaffected->fresh()->getAttributes());
    }
}
