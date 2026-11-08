<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Support\ChordExtensionLessonRestructure;
use App\Support\ChordExtensionQuizBuilder;
use App\Support\DominantChordExtensionSamples;
use App\Support\MajorChordExtensionSamples;
use App\Support\MinorChordExtensionSamples;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BasicExtensionLessonsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        foreach (range(24, 107) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
    }

    public function test_four_step_group_sequence_and_exact_basic_pitches(): void
    {
        $natural = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];
        foreach (['Major' => MajorChordExtensionSamples::class, 'Minor' => MinorChordExtensionSamples::class, 'Dominant' => DominantChordExtensionSamples::class] as $family => $source) {
            $levels = ChordExtensionQuizBuilder::levels($family);
            $this->assertCount($family === 'Dominant' ? 8 : 13, $levels);
            foreach (range(1, $family === 'Dominant' ? 2 : 3) as $group) {
                $base = ($group - 1) * 4;
                $this->assertTrue($levels[$base + 1]['basic']);
                $this->assertTrue($levels[$base + 2]['basic']);
                $this->assertFalse($levels[$base + 3]['basic']);
                $this->assertFalse($levels[$base + 4]['basic']);
                $this->assertSame(['f'], $levels[$base + 1]['keys']);
                $this->assertCount(12, $levels[$base + 2]['keys']);
                foreach ($source::qualitiesForLevel($group) as $quality => $intervals) {
                    foreach (['c' => 48, 'f' => 53, 'b' => 59] as $key => $root) {
                        $path = 'plain-chord-extensions/'.strtolower($family).'/group'.$group.'/piano/'.$key.'-'.$quality.'.wav';
                        $notes = NoteSample::resolve($path);
                        $this->assertCount(count($intervals), $notes);
                        $actual = array_map(fn ($note) => ($note['octave'] + 1) * 12 + ($natural[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
                        $this->assertSame(array_map(fn ($tone) => $root + $tone, $intervals), $actual);
                        $this->assertSame(array_fill(0, count($notes), 0.0), array_column($notes, 'offset'));
                    }
                }
            }
            if ($family !== 'Dominant') {
                $this->assertSame([1, 2, 3], $levels[13]['group']);
                $this->assertFalse($levels[13]['basic']);
            }
        }
        $this->assertCount(8, ChordExtensionQuizBuilder::levels('Dominant'));
    }

    public function test_renumbering_preserves_existing_variations_ids_audio_slugs_and_progress(): void
    {
        $legacy = [];
        foreach (['Major', 'Minor', 'Dominant'] as $family) {
            foreach (range(1, $family === 'Dominant' ? 4 : 7) as $level) {
                $title = $family.' Chord Extentions · Level '.$level;
                $quiz = Quiz::factory()->create(['title' => $title, 'slug' => Str::slug($title), 'category' => $family.' Chord Extensions']);
                $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
                $audio = QuizQuestionAudio::create(['quiz_question_id' => $question->id, 'sound' => 'Piano', 'audio_path' => 'preserved/'.$family.'-'.$level.'.wav']);
                $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$question->id]]);
                $legacy[] = [$family, $level, $quiz, $quiz->fresh()->getAttributes(), $question, $question->fresh()->getAttributes(), $audio, $audio->fresh()->getAttributes(), $attempt, $attempt->fresh()->getAttributes()];
            }
        }
        $other = Quiz::factory()->create(['category' => 'Chord Naming']);
        $otherBefore = $other->fresh()->getAttributes();
        ChordExtensionLessonRestructure::apply(['Major', 'Minor', 'Dominant']);
        foreach ($legacy as [$family, $oldLevel, $quiz, $quizBefore, $question, $questionBefore, $audio, $audioBefore, $attempt, $attemptBefore]) {
            $this->assertSame([...$quizBefore, 'title' => $family.' Chord Extentions · Level '.($family === 'Dominant' ? [1 => 3, 2 => 4, 3 => 7, 4 => 8][$oldLevel] : ChordExtensionLessonRestructure::VARIATION_LEVELS[$oldLevel])], $quiz->fresh()->getAttributes());
            $this->assertSame($questionBefore, $question->fresh()->getAttributes());
            $this->assertSame($audioBefore, $audio->fresh()->getAttributes());
            $this->assertSame($attemptBefore, $attempt->fresh()->getAttributes());
        }
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        foreach (['Major', 'Minor', 'Dominant'] as $family) {
            $this->assertSame($family === 'Dominant' ? 8 : 13, Quiz::where('category', $family.' Chord Extensions')->count());
        }
        ChordExtensionLessonRestructure::apply(['Major', 'Minor', 'Dominant']);
        foreach (['Major', 'Minor', 'Dominant'] as $family) {
            $this->assertSame($family === 'Dominant' ? 8 : 13, Quiz::where('category', $family.' Chord Extensions')->count());
            $quiz = Quiz::where('title', $family.' Chord Extentions · Level 1')->firstOrFail();
            $this->assertSame(range(1, $quiz->questions()->count()), $quiz->questions()->orderBy('position')->pluck('position')->all());
        }
    }
}
