<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\BasicChordNamingSamples;
use App\Support\ChordNamingAnswer;
use App\Support\ChordNamingQuizBuilder;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChordNamingOmissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (range(24, 95) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
    }

    public function test_only_seventh_chords_get_both_omissions_and_exactly_one_tone_is_removed(): void
    {
        foreach ([1 => 36, 2 => 36, 3 => 137] as $group => $count) {
            $base = BasicChordNamingSamples::recipes($group);
            $expanded = BasicChordNamingSamples::recipesWithOmissions($group);
            $this->assertCount($count, $expanded);
            foreach ($base as $id => $recipe) {
                $this->assertSame($recipe, $expanded[$id]);
                $answer = ChordNamingAnswer::decode($recipe['answer']);
                $eligible = array_intersect($answer['degrees'], ['7', '♭7', 'maj7']) !== [];
                foreach (['no3' => 1, 'no5' => 2] as $suffix => $index) {
                    if (! $eligible) {
                        $this->assertArrayNotHasKey($id.'-'.$suffix, $expanded);

                        continue;
                    }
                    $omitted = $expanded[$id.'-'.$suffix];
                    $this->assertCount(count($recipe['intervals']) - 1, $omitted['intervals']);
                    $this->assertSame(array_values(array_diff($recipe['intervals'], [$recipe['intervals'][$index]])), $omitted['intervals']);
                    $this->assertSame($omitted['intervals'], ChordNamingAnswer::intervals($omitted['answer']));
                    $path = ($group === 1 ? 'chord-naming/basic-v1/' : 'chord-naming/basic-group'.$group.'-v1/').'piano/f-'.$id.'-'.$suffix.'.wav';
                    $notes = NoteSample::resolve($path);
                    $this->assertCount(count($omitted['intervals']), $notes);
                    $this->assertSame(array_fill(0, count($notes), 0.0), array_column($notes, 'offset'));
                }
            }
        }
    }

    public function test_ambiguous_omissions_accept_the_same_sounding_chord_and_reject_wrong_tones(): void
    {
        $this->assertTrue(ChordNamingAnswer::matches('v2|Major|♭7,9,11|No 3', 'v2|Minor|♭7,9,11|No 3'));
        $this->assertTrue(ChordNamingAnswer::matches('v2|Augmented|7,9|No 5', 'v2|Major|7,9|No 5'));
        $this->assertTrue(ChordNamingAnswer::matches('v2|Minor|♭5,♭7,9|No 5', 'v2|Minor|♭7,9|No 5'));
        $this->assertFalse(ChordNamingAnswer::matches('v2|Major|7,9,11|No 3', 'v2|Minor|♭7,9,11|No 3'));
        $this->assertFalse(ChordNamingAnswer::matches('v2|Major|♭7,9|No 3', 'v2|Minor|♭7,9,11|No 3'));
        $this->assertFalse(ChordNamingAnswer::matches('v2|Major|♭7,9,11|None', 'v2|Minor|♭7,9,11|No 3'));
        $this->assertFalse(ChordNamingAnswer::matches('v2|Major|♭7,9,11|No 5', 'v2|Minor|♭7,9,11|No 3'));
        $this->assertFalse(ChordNamingAnswer::isValid('v2|Major|6,9|No 3'));
        $this->assertFalse(ChordNamingAnswer::matches('v2|Diminished|6|None', 'v2|Diminished|7|None'));
    }

    public function test_all_six_levels_expand_repeatably_without_changing_existing_questions_audio_or_attempts(): void
    {
        $saved = [];
        foreach (range(1, 6) as $level) {
            $group = intdiv($level - 1, 2) + 1;
            $recipe = array_key_first(BasicChordNamingSamples::recipes($group));
            $quiz = Quiz::factory()->create(['title' => 'Chord Naming · Level '.$level, 'category' => 'Chord Naming']);
            $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id, 'prompt' => 'Listen to the chord and build its full name.', 'choices' => [], 'answer' => BasicChordNamingSamples::recipes($group)[$recipe]['answer']]);
            $prefix = $group === 1 ? 'chord-naming/basic-v1/' : 'chord-naming/basic-group'.$group.'-v1/';
            $audio = QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'Piano', 'audio_path' => $prefix.'piano/'.($level % 2 === 1 ? 'c' : 'f').'-'.$recipe.'.wav']);
            $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$question->id]]);
            foreach ([$question, $audio, $attempt] as $model) {
                $saved[] = [$model, $model->fresh()->getAttributes()];
            }
        }
        $report = ChordNamingQuizBuilder::build();
        $this->assertSame([36, 432, 36, 432, 137, 1644], array_column($report, 'questions'));
        $ids = QuizQuestion::orderBy('id')->pluck('id')->all();
        ChordNamingQuizBuilder::build();
        $this->assertSame($ids, QuizQuestion::orderBy('id')->pluck('id')->all());
        foreach ($saved as [$model, $before]) {
            $this->assertSame($before, $model->fresh()->getAttributes());
        }
    }
}
