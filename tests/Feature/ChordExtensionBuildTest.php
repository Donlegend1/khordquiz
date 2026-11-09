<?php

namespace Tests\Feature;

use App\Http\Controllers\QuizController;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;
use App\Models\User;
use App\Support\ChordExtensionQuizBuilder;
use App\Support\DominantChordExtensionSamples;
use App\Support\ExtensionVoicings;
use App\Support\MajorChordExtensionSamples;
use App\Support\MinorChordExtensionSamples;
use App\Support\NoteSample;
use App\Support\QuizCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChordExtensionBuildTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
        foreach (range(24, 107) as $midi) {
            $label = array_keys(NoteSample::CHROMATIC_SLUGS)[$midi % 12];
            Storage::disk('public')->put(NoteSample::path('piano', NoteSample::equivalentDegree($label) ?? $label, intdiv($midi, 12) - 1), 'sample');
        }
    }

    public function test_all_major_and_minor_voicings_obey_the_confirmed_rules(): void
    {
        foreach ([MajorChordExtensionSamples::class => 4, MinorChordExtensionSamples::class => 3, DominantChordExtensionSamples::class => 4] as $resolver => $third) {
            foreach (range(1, $resolver === DominantChordExtensionSamples::class ? 2 : 3) as $group) {
                foreach ($resolver::qualitiesForLevel($group) as $intervals) {
                    $variants = ExtensionVoicings::forIntervals($intervals, $third);
                    $this->assertNotEmpty($variants);
                    $this->assertLessThanOrEqual(5, count($variants));
                    $this->assertCount(count($variants), array_unique(array_map(fn ($v) => implode(',', $v), $variants)));
                    foreach ($variants as $stack) {
                        $counts = array_count_values(array_map(fn ($n) => $n % 12, $stack));
                        $expectedClasses = array_map(fn ($n) => $n % 12, $intervals);
                        sort($expectedClasses);
                        $actualClasses = array_keys($counts);
                        sort($actualClasses);
                        $this->assertSame($expectedClasses, $actualClasses);
                        $this->assertLessThanOrEqual(2, max($counts));
                        $this->assertLessThanOrEqual(3, count(array_filter($counts, fn ($n) => $n === 2)));
                        foreach (range(1, count($stack) - 1) as $i) {
                            $this->assertLessThanOrEqual(7, $stack[$i] - $stack[$i - 1]);
                        }
                    }
                }
            }
        }
        $this->assertCount(2, ExtensionVoicings::forIntervals([0, 4, 7, 18]));
        $this->assertSame('7, 9, 11, 13', ChordExtensionQuizBuilder::answerFor(MinorChordExtensionSamples::qualitiesForLevel(3)['min13']));
        $this->assertSame('7, 11, 13', ChordExtensionQuizBuilder::answerFor(MinorChordExtensionSamples::qualitiesForLevel(3)['min71113']));
        $this->assertSame('6, ♭9, 11', ChordExtensionQuizBuilder::answerFor(MinorChordExtensionSamples::qualitiesForLevel(3)['min6b911']));
    }

    public function test_minor_groups_follow_the_confirmed_tone_counts_and_membership(): void
    {
        $this->assertSame(['min6', 'min7', 'minaddb9', 'minadd9', 'minadd11'], array_keys(MinorChordExtensionSamples::qualitiesForLevel(1)));
        $this->assertSame(['minadd911', 'min69', 'min6add11', 'min9', 'min711', 'min713'], array_keys(MinorChordExtensionSamples::qualitiesForLevel(2)));
        $this->assertSame(['min69add11', 'min6b911', 'min11', 'min13', 'min71113', 'min7913'], array_keys(MinorChordExtensionSamples::qualitiesForLevel(3)));
        foreach ([1 => [4], 2 => [5], 3 => [6, 7]] as $group => $sizes) {
            foreach (MinorChordExtensionSamples::qualitiesForLevel($group) as $tones) {
                $this->assertContains(count($tones), $sizes);
            }
        }
        $this->assertArrayNotHasKey('min7', MinorChordExtensionSamples::qualitiesForLevel(3));
    }

    public function test_confirmed_dominant_groups_only_answer_with_their_tensions(): void
    {
        $this->assertCount(7, DominantChordExtensionSamples::qualitiesForLevel(1));
        $this->assertCount(6, DominantChordExtensionSamples::qualitiesForLevel(2));
        $this->assertSame([], DominantChordExtensionSamples::qualitiesForLevel(3));
        $this->assertSame(range(1, 8), array_keys(ChordExtensionQuizBuilder::levels('Dominant')));
        $this->assertSame([1, 1, 1, 1, 2, 2, 2, 2], array_column(ChordExtensionQuizBuilder::levels('Dominant'), 'group'));
        foreach (DominantChordExtensionSamples::qualitiesForLevel(1) as $tones) {
            $this->assertNotEmpty(array_intersect([13, 15], $tones));
        }
        foreach (DominantChordExtensionSamples::qualitiesForLevel(2) as $tones) {
            $this->assertSame([], array_values(array_intersect([13, 15], $tones)));
        }
        $this->assertSame('♭9, ♯9, ♭13', ChordExtensionQuizBuilder::answerFor([0, 4, 7, 10, 13, 15, 20], 'Dominant'));
        $this->assertSame('9, ♯11, 13', ChordExtensionQuizBuilder::answerFor([0, 4, 7, 10, 14, 18, 21], 'Dominant'));
        foreach ([1, 2] as $group) {
            $plan = ChordExtensionQuizBuilder::plan('Dominant', DominantChordExtensionSamples::class, ['group' => $group, 'keys' => ['f']]);
            $this->assertNotEmpty($plan);
            foreach ($plan as $entry) {
                $tokens = explode(', ', $entry['answer']);
                $this->assertContains(count($tokens), [2, 3]);
                $this->assertEmpty(array_diff($tokens, ChordExtensionQuizBuilder::DOMINANT_CHOICES));
                foreach ($entry['audio'] as $audio) {
                    $this->assertNotNull(NoteSample::resolve($audio['audio_path']));
                }
            }
        }
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_major_seventh_belongs_only_to_group_one_and_general_has_no_duplicate(): void
    {
        $scope = ChordExtensionQuizBuilder::levels()[13];
        $plan = ChordExtensionQuizBuilder::plan('Major', MajorChordExtensionSamples::class, $scope);
        $this->assertCount(1164, $plan);
        $paths = array_column(array_merge(...array_column($plan, 'audio')), 'audio_path');
        $this->assertContains('major-chord-extensions/level1/piano/f-maj7-v1.wav', $paths);
        $this->assertNotContains('major-chord-extensions/level3/piano/f-maj7-v1.wav', $paths);
        $this->assertArrayHasKey('maj7', MajorChordExtensionSamples::qualitiesForLevel(1));
        $this->assertArrayNotHasKey('maj7', MajorChordExtensionSamples::qualitiesForLevel(3));
        $this->assertCount(8, MajorChordExtensionSamples::qualitiesForLevel(3));
        $this->assertSame([1, 2, 3], $scope['group']);
    }

    public function test_explicit_dominant_build_keeps_the_fixed_vocabulary_and_chord_naming(): void
    {
        $naming = Quiz::factory()->create(['title' => 'Chord naming', 'category' => 'Chord Naming']);
        ChordExtensionQuizBuilder::build('Dominant', DominantChordExtensionSamples::class, [1 => ['group' => 2, 'keys' => ['f']]]);
        $quiz = Quiz::where('title', 'Dominant Chord Extentions · Level 1')->firstOrFail();
        $this->assertSame(ChordExtensionQuizBuilder::DOMINANT_CHOICES, $quiz->questions()->firstOrFail()->choices);
        $this->assertSame('Chord naming', $naming->fresh()->title);
        $grader = new \ReflectionMethod(QuizController::class, 'answersMatch');
        $controller = app(QuizController::class);
        $this->assertTrue($grader->invoke($controller, '♭13, ♯9, ♭9', '♭9, ♯9, ♭13'));
        $this->assertFalse($grader->invoke($controller, '♭13, ♯9', '♭9, ♯9, ♭13'));
    }

    public function test_family_levels_use_the_correct_groups_keys_and_working_audio_and_are_repeatable(): void
    {
        foreach (['Major' => MajorChordExtensionSamples::class, 'Minor' => MinorChordExtensionSamples::class, 'Dominant' => DominantChordExtensionSamples::class] as $family => $resolver) {
            $original = Quiz::factory()->create(['title' => $family.' Chord Extentions'.($family === 'Major' ? ' · Level 1' : ''), 'category' => $family.' Chord Extensions']);
            ChordExtensionQuizBuilder::build($family, $resolver);
            $this->assertSame($original->id, Quiz::where('title', $family.' Chord Extentions · Level 1')->firstOrFail()->id);
            $ids = [];
            foreach (ChordExtensionQuizBuilder::levels($family) as $level => $scope) {
                $quiz = Quiz::where('title', $family.' Chord Extentions · Level '.$level)->firstOrFail();
                $this->assertContains($quiz->title, QuizCatalog::subcategories($quiz->category));
                $questions = $quiz->questions()->with('audioVariants')->get();
                $this->assertNotEmpty($questions);
                $expectedKeys = [];
                foreach ($questions as $question) {
                    $this->assertSame($family === 'Dominant' ? ChordExtensionQuizBuilder::DOMINANT_CHOICES : ChordExtensionQuizBuilder::CHOICES, $question->choices);
                    $this->assertNotEmpty($question->audioVariants);
                    foreach ($question->audioVariants as $audio) {
                        preg_match('#/(?:level|group)([123])/#', $audio->audio_path, $groupMatch);
                        $this->assertContains((int) $groupMatch[1], (array) $scope['group']);
                        $this->assertNotNull(NoteSample::resolve($audio->audio_path));
                        $this->assertNotContains($audio->sound, ['Bass Guitar', 'Tuba', 'Upright Bass']);
                        preg_match('#/piano/([a-z]+)-#', $audio->audio_path, $matches);
                        $expectedKeys[] = $matches[1];
                    }
                }
                $this->assertEqualsCanonicalizing($scope['keys'], array_values(array_unique($expectedKeys)));
                $ids[$level] = $questions->pluck('id')->all();
            }
            ChordExtensionQuizBuilder::build($family, $resolver);
            foreach ($ids as $level => $questionIds) {
                $this->assertSame($questionIds, Quiz::where('title', $family.' Chord Extentions · Level '.$level)->firstOrFail()->questions()->pluck('id')->all());
            }
        }
    }

    public function test_restructured_pools_restart_attempts_that_reference_removed_questions(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Major Chord Extentions · Level 1', 'category' => 'Major Chord Extensions']);
        $old = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        QuizQuestionAudio::create(['quiz_question_id' => $old->id, 'sound' => 'Piano', 'audio_path' => 'major-chord-extensions/level2/piano/c-maj69-v1.wav']);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'question_sequence' => [$old->id]]);
        ChordExtensionQuizBuilder::build('Major', MajorChordExtensionSamples::class, [1 => ChordExtensionQuizBuilder::levels()[1]]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $old->id]);
    }

    public function test_minor_preview_and_member_playback_use_the_same_live_mix(): void
    {
        ChordExtensionQuizBuilder::build('Minor', MinorChordExtensionSamples::class);
        $quiz = Quiz::where('title', 'Minor Chord Extentions · Level 6')->firstOrFail();
        $quiz->update(['question_count' => 1]);
        $admin = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.audio-quiz.lesson', ['minor-chord-extensions', 'minor-chord-extentions-level-6']))->assertOk()->viewData('page')['props'];
        $previews = collect($admin['quiz']['questions'])->keyBy('id');
        foreach ($previews as $preview) {
            $this->assertEmpty(array_intersect(['Bass Guitar', 'Upright Bass', 'Tuba'], array_column($preview['missing_instrument_previews'], 'sound')));
        }
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $question = $member['question'];
        $questionId = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail()->question_sequence[0];
        $page = (int) ceil($quiz->questions()->where('id', '<=', $questionId)->count() / 25);
        $adminPage = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.audio-quiz.lesson', ['minor-chord-extensions', 'minor-chord-extentions-level-6', 'page' => $page]))
            ->assertOk()->viewData('page')['props'];
        $preview = collect(collect($adminPage['quiz']['questions'])->firstWhere('id', $questionId)['audio_variants'])->firstWhere('sound', 'Piano');
        $this->assertSame($preview['notes'], $question['notes']);
    }
}
