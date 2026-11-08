<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\ChordNamingAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChordNamingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sevenths_and_legacy_answers_keep_their_distinct_meanings(): void
    {
        $this->assertTrue(ChordNamingAnswer::matches('v2|Major|9,♭7|None', 'Dominant|9|None'));
        $this->assertTrue(ChordNamingAnswer::matches('v2|Minor|♭7|None', 'Minor|7|None'));
        $this->assertFalse(ChordNamingAnswer::matches('v2|Minor|7|None', 'Minor|7|None'));
        $this->assertTrue(ChordNamingAnswer::matches('v2|Diminished|7|None', 'Diminished|7|None'));
        $this->assertFalse(ChordNamingAnswer::isValid('v2|Major||None'));
        $this->assertFalse(ChordNamingAnswer::isValid('v2|Major|9|No 3'));
        $this->assertFalse(ChordNamingAnswer::isValid('v2|Minor|7,♭7|None'));
        $this->assertFalse(ChordNamingAnswer::isValid('v2|Sus4|6|None'));
        $this->assertTrue(ChordNamingAnswer::isValid('v2|Major|♭7,9|No 3'));
    }

    public function test_secondary_sevenths_remain_distinct_and_equivalent_fifth_spellings_grade_correctly(): void
    {
        $this->assertTrue(ChordNamingAnswer::isValid('v2|Diminished|maj7,9|None'));
        $this->assertFalse(ChordNamingAnswer::isValid('v2|Major|maj7|None'));
        $this->assertFalse(ChordNamingAnswer::isValid('v2|Diminished|7,maj7|None'));
        $this->assertFalse(ChordNamingAnswer::isValid('v2|Diminished|♭7,maj7|None'));
        $this->assertFalse(ChordNamingAnswer::matches('v2|Diminished|7,9|None', 'v2|Diminished|maj7,9|None'));
        $this->assertTrue(ChordNamingAnswer::matches('v2|Minor|9,7,♭5|None', 'v2|Diminished|maj7,9|None'));
        $this->assertTrue(ChordNamingAnswer::matches('v2|Diminished|♭7,11|None', 'v2|Minor|♭5,♭7,11|None'));
        $this->assertTrue(ChordNamingAnswer::matches('v2|Augmented|7,9|None', 'v2|Major|♯5,7,9|None'));
        $this->assertTrue(ChordNamingAnswer::matches('v2|Major|♯5,♭7,9|None', 'v2|Augmented|♭7,9|None'));
        $this->assertFalse(ChordNamingAnswer::matches('v2|Augmented|7,9|None', 'v2|Augmented|♭7,9|None'));
    }

    public function test_new_major_flat_seventh_selection_grades_against_existing_dominant_audio_questions(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Chord naming', 'category' => 'Chord Naming', 'question_count' => 1]);
        $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'answer' => 'Dominant|9|None']);
        $bare = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'position' => 2, 'answer' => 'Major||None']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz))->assertRedirect();
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame([$question->id], $attempt->question_sequence);
        $this->post(route('quiz.answer', $quiz), ['choice' => 'v2|Major|♭7,9|None'])->assertRedirect();
        $this->assertTrue($attempt->answers()->firstOrFail()->correct);
        $this->assertNotNull($bare->fresh());
        $this->assertSame('Dominant|9|None', $question->fresh()->answer);
    }
}
