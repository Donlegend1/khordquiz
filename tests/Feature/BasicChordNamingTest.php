<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Support\BasicChordNamingSamples;
use App\Support\ChordNamingAnswer;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BasicChordNamingTest extends TestCase
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

    public function test_every_structure_contains_exactly_the_listed_pitches_once_in_written_register(): void
    {
        $recipes = BasicChordNamingSamples::recipes();
        $this->assertCount(18, $recipes);
        $this->assertSame(['major' => 6, 'minor' => 5, 'dominant' => 7], array_count_values(array_column($recipes, 'family')));
        $natural = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];
        foreach ($recipes as $id => $recipe) {
            $notes = NoteSample::resolve('chord-naming/basic-v1/piano/f-'.$id.'.wav');
            $this->assertCount(count($recipe['intervals']), $notes);
            $actual = array_map(fn ($note) => ($note['octave'] + 1) * 12 + ($natural[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
            $this->assertSame(array_map(fn ($i) => 53 + $i, $recipe['intervals']), $actual);
            $this->assertSame(array_fill(0, count($notes), 0.0), array_column($notes, 'offset'));
            $this->assertTrue(ChordNamingAnswer::isValid($recipe['answer']));
        }
        $this->assertArrayNotHasKey('major-maj69', $recipes);
        $this->assertArrayNotHasKey('minor-min13', $recipes);
        $this->assertArrayNotHasKey('minor-minadd911', $recipes);
        $this->assertArrayNotHasKey('dominant-dom79s11', $recipes);
        $this->assertSame([0, 4, 7, 18], $recipes['major-majadds11']['intervals']);
        $this->assertSame('v2|Major|♭7,♭9,♯11|None', $recipes['dominant-dom7b9s11']['answer']);
        $this->assertNull(NoteSample::resolve('chord-naming/basic-v1/piano/f-unknown.wav'));
    }

    public function test_missing_recorded_tones_do_not_get_replaced_or_shifted(): void
    {
        $this->assertNull(NoteSample::resolve('chord-naming/basic-v1/flute/f-major-maj6.wav'));
        $manifest = BasicChordNamingSamples::manifest();
        $this->assertCount(18, $manifest);
        foreach ($manifest as $row) {
            $this->assertArrayHasKey('piano', $row['samples']);
            $this->assertArrayNotHasKey('flute', $row['samples']);
        }
    }

    public function test_level_two_has_the_same_eighteen_structures_in_every_key(): void
    {
        $rows = BasicChordNamingSamples::manifest(BasicChordNamingSamples::KEYS);
        $this->assertCount(216, $rows);
        $counts = array_count_values(array_column($rows, 'key'));
        foreach (BasicChordNamingSamples::KEYS as $key) {
            $this->assertSame(18, $counts[$key]);
        }
        $natural = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];
        foreach ($rows as $row) {
            $notes = $row['samples']['piano'];
            $root = 48 + array_search($row['key'], BasicChordNamingSamples::KEYS, true);
            $actual = array_map(fn ($note) => ($note['octave'] + 1) * 12 + ($natural[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
            $expected = array_map(fn ($interval) => $root + $interval, BasicChordNamingSamples::recipes()[$row['recipe']]['intervals']);
            $this->assertSame($expected, $actual);
        }
    }

    public function test_level_two_build_is_repeatable_and_leaves_level_one_untouched(): void
    {
        $levelOne = Quiz::factory()->create(['title' => BasicChordNamingSamples::TITLE, 'category' => 'Chord Naming']);
        $question = QuizQuestion::factory()->create(['quiz_id' => $levelOne->id]);
        $before = [$levelOne->fresh()->getAttributes(), $question->fresh()->getAttributes()];
        ob_start();
        try {
            require base_path('scripts/audio/build_chord_naming_level2.php');
            $obsolete = QuizQuestion::factory()->create(['quiz_id' => Quiz::where('title', BasicChordNamingSamples::LEVEL_TWO_TITLE)->firstOrFail()->id, 'position' => 999]);
            require base_path('scripts/audio/build_chord_naming_level2.php');
        } finally {
            ob_end_clean();
        }
        $this->assertDatabaseMissing('quiz_questions', ['id' => $obsolete->id]);
        $this->assertSame($before[0], $levelOne->fresh()->getAttributes());
        $this->assertSame($before[1], $question->fresh()->getAttributes());
        $levelTwo = Quiz::where('title', BasicChordNamingSamples::LEVEL_TWO_TITLE)->firstOrFail();
        $this->assertSame(432, $levelTwo->questions()->count());
        $props = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-naming', 'chord-naming-level-2']))->assertOk()->viewData('page')['props'];
        $this->assertCount(25, $props['quiz']['questions']);
        $this->assertSame(432, $props['quiz']['questions_total']);
        $this->assertNotEmpty($props['quiz']['questions'][0]['audio_variants'][0]['notes']);
    }

    public function test_level_three_contains_only_group_two_in_f_with_exact_tones(): void
    {
        $firstGroup = BasicChordNamingSamples::recipes();
        $recipes = BasicChordNamingSamples::recipes(2);
        $this->assertCount(18, $recipes);
        $this->assertSame(['major' => 6, 'minor' => 6, 'dominant' => 6], array_count_values(array_column($recipes, 'family')));
        $this->assertArrayNotHasKey('major-maj6', $recipes);
        $this->assertArrayNotHasKey('minor-min7', $recipes);
        $this->assertArrayHasKey('minor-minadd911', $recipes);
        $this->assertArrayHasKey('major-maj69', $recipes);
        $this->assertArrayHasKey('dominant-dom79s1113', $recipes);
        $rows = BasicChordNamingSamples::manifest(['f'], 2);
        $this->assertCount(18, $rows);
        $natural = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];
        foreach ($rows as $row) {
            $this->assertSame('f', $row['key']);
            $notes = NoteSample::resolve('chord-naming/basic-group2-v1/piano/f-'.$row['recipe'].'.wav');
            $actual = array_map(fn ($note) => ($note['octave'] + 1) * 12 + ($natural[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
            $this->assertSame(array_map(fn ($n) => 53 + $n, $recipes[$row['recipe']]['intervals']), $actual);
            $this->assertSame('None', ChordNamingAnswer::decode($row['answer'])['exclude']);
        }
        $this->assertSame($firstGroup, BasicChordNamingSamples::recipes());
    }

    public function test_level_three_build_does_not_change_levels_one_or_two(): void
    {
        $before = [];
        foreach ([BasicChordNamingSamples::TITLE, BasicChordNamingSamples::LEVEL_TWO_TITLE] as $title) {
            $quiz = Quiz::factory()->create(['title' => $title, 'category' => 'Chord Naming']);
            $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
            $before[] = [$quiz, $quiz->fresh()->getAttributes(), $question, $question->fresh()->getAttributes()];
        }
        ob_start();
        try {
            require base_path('scripts/audio/build_chord_naming_level3.php');
            require base_path('scripts/audio/build_chord_naming_level3.php');
        } finally {
            ob_end_clean();
        }
        foreach ($before as [$quiz, $quizAttributes, $question, $questionAttributes]) {
            $this->assertSame($quizAttributes, $quiz->fresh()->getAttributes());
            $this->assertSame($questionAttributes, $question->fresh()->getAttributes());
        }
        $quiz = Quiz::where('title', BasicChordNamingSamples::LEVEL_THREE_TITLE)->firstOrFail();
        $this->assertSame(36, $quiz->questions()->count());
        $admin = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-naming', 'chord-naming-level-3']))->assertOk()->viewData('page')['props'];
        $this->assertCount(25, $admin['quiz']['questions']);
        $this->assertSame(36, $admin['quiz']['questions_total']);
        $this->assertNotEmpty($admin['quiz']['questions'][0]['audio_variants'][0]['notes']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $props = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertNotEmpty($props['question']['notes']);
    }

    public function test_level_four_is_group_two_in_all_twelve_keys_with_the_same_f_structures(): void
    {
        $rows = BasicChordNamingSamples::manifest(BasicChordNamingSamples::KEYS, 2);
        $this->assertCount(216, $rows);
        $counts = array_count_values(array_column($rows, 'key'));
        foreach (BasicChordNamingSamples::KEYS as $key) {
            $this->assertSame(18, $counts[$key]);
        }
        $this->assertSame(BasicChordNamingSamples::manifest(['f'], 2), array_values(array_filter($rows, fn ($row) => $row['key'] === 'f')));
        $natural = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];
        foreach ($rows as $row) {
            $root = 48 + array_search($row['key'], BasicChordNamingSamples::KEYS, true);
            $actual = array_map(fn ($note) => ($note['octave'] + 1) * 12 + ($natural[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $row['samples']['piano']);
            $this->assertSame(array_map(fn ($tone) => $root + $tone, BasicChordNamingSamples::recipes(2)[$row['recipe']]['intervals']), $actual);
        }
    }

    public function test_level_four_build_leaves_the_first_three_levels_unchanged(): void
    {
        $before = [];
        foreach ([BasicChordNamingSamples::TITLE, BasicChordNamingSamples::LEVEL_TWO_TITLE, BasicChordNamingSamples::LEVEL_THREE_TITLE] as $title) {
            $quiz = Quiz::factory()->create(['title' => $title, 'category' => 'Chord Naming']);
            $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
            $before[] = [$quiz, $quiz->fresh()->getAttributes(), $question, $question->fresh()->getAttributes()];
        }
        ob_start();
        try {
            require base_path('scripts/audio/build_chord_naming_level4.php');
            require base_path('scripts/audio/build_chord_naming_level4.php');
        } finally {
            ob_end_clean();
        }
        foreach ($before as [$quiz, $quizAttributes, $question, $questionAttributes]) {
            $this->assertSame($quizAttributes, $quiz->fresh()->getAttributes());
            $this->assertSame($questionAttributes, $question->fresh()->getAttributes());
        }
        $quiz = Quiz::where('title', BasicChordNamingSamples::LEVEL_FOUR_TITLE)->firstOrFail();
        $this->assertSame(432, $quiz->questions()->count());
        $props = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-naming', 'chord-naming-level-4']))->assertOk()->viewData('page')['props'];
        $this->assertCount(25, $props['quiz']['questions']);
        $this->assertSame(432, $props['quiz']['questions_total']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertNotEmpty($member['question']['notes']);
    }

    public function test_level_five_build_leaves_the_first_four_levels_unchanged(): void
    {
        $before = [];
        foreach ([BasicChordNamingSamples::TITLE, BasicChordNamingSamples::LEVEL_TWO_TITLE, BasicChordNamingSamples::LEVEL_THREE_TITLE, BasicChordNamingSamples::LEVEL_FOUR_TITLE] as $title) {
            $quiz = Quiz::factory()->create(['title' => $title, 'category' => 'Chord Naming']);
            $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
            $before[] = [$quiz, $quiz->fresh()->getAttributes(), $question, $question->fresh()->getAttributes()];
        }
        ob_start();
        try {
            require base_path('scripts/audio/build_chord_naming_level5.php');
            $levelFive = Quiz::where('title', BasicChordNamingSamples::LEVEL_FIVE_TITLE)->firstOrFail();
            $existingIds = $levelFive->questions()->orderBy('position')->pluck('id')->all();
            $attempt = QuizAttempt::factory()->create(['quiz_id' => $levelFive->id, 'question_sequence' => array_slice($existingIds, 0, 5)]);
            $attemptBefore = $attempt->fresh()->getAttributes();
            require base_path('scripts/audio/build_chord_naming_level5.php');
            $this->assertSame($existingIds, $levelFive->questions()->orderBy('position')->pluck('id')->all());
            $this->assertSame($attemptBefore, $attempt->fresh()->getAttributes());
        } finally {
            ob_end_clean();
        }
        foreach ($before as [$quiz, $quizAttributes, $question, $questionAttributes]) {
            $this->assertSame($quizAttributes, $quiz->fresh()->getAttributes());
            $this->assertSame($questionAttributes, $question->fresh()->getAttributes());
        }
        $quiz = Quiz::where('title', BasicChordNamingSamples::LEVEL_FIVE_TITLE)->firstOrFail();
        $this->assertSame(137, $quiz->questions()->count());
        $props = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-naming', 'chord-naming-level-5']))->assertOk()->viewData('page')['props'];
        $this->assertCount(25, $props['quiz']['questions']);
        $this->assertSame(137, $props['quiz']['questions_total']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertNotEmpty($member['question']['notes']);
    }

    public function test_level_five_uses_group_three_and_secondary_chords_in_f_with_exact_pitches(): void
    {
        $recipes = BasicChordNamingSamples::recipes(3);
        $this->assertSame(['major' => 8, 'minor' => 6, 'secondary' => 33], array_count_values(array_column($recipes, 'family')));
        $this->assertArrayNotHasKey('major-maj7', $recipes);
        $this->assertArrayHasKey('minor-min13', $recipes);
        $rows = BasicChordNamingSamples::manifest(['f'], 3);
        $this->assertCount(47, $rows);
        $natural = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];
        foreach ($rows as $row) {
            $this->assertSame('f', $row['key']);
            $notes = NoteSample::resolve('chord-naming/basic-group3-v1/piano/f-'.$row['recipe'].'.wav');
            $this->assertCount(count($recipes[$row['recipe']]['intervals']), $notes);
            $actual = array_map(fn ($note) => ($note['octave'] + 1) * 12 + ($natural[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
            $this->assertSame(array_map(fn ($tone) => 53 + $tone, $recipes[$row['recipe']]['intervals']), $actual);
            $this->assertSame(array_fill(0, count($notes), 0.0), array_column($notes, 'offset'));
            $this->assertTrue(ChordNamingAnswer::isValid($row['answer']));
            $this->assertSame('None', ChordNamingAnswer::decode($row['answer'])['exclude']);
        }
        $this->assertNull(NoteSample::resolve('chord-naming/basic-group3-v1/piano/f-dominant-dom79s11.wav'));
    }

    public function test_secondary_chords_have_distinct_sevenths_and_altered_fifths(): void
    {
        $recipes = BasicChordNamingSamples::recipes(3);
        $examples = [
            'secondary-major-flat5-14-21' => [[0, 4, 6, 11, 14, 21], 'v2|Major|♭5,7,9,13|None'],
            'secondary-major-sharp5-14-18' => [[0, 4, 8, 11, 14, 18], 'v2|Major|♯5,7,9,♯11|None'],
            'secondary-augmented7-13-18' => [[0, 4, 8, 10, 13, 18], 'v2|Augmented|♭7,♭9,♯11|None'],
            'secondary-minor-major7-14-18' => [[0, 3, 7, 11, 14, 18], 'v2|Minor|7,9,♯11|None'],
            'secondary-diminished-major7-14-17' => [[0, 3, 6, 11, 14, 17], 'v2|Diminished|maj7,9,11|None'],
            'secondary-diminished7-14-17' => [[0, 3, 6, 9, 14, 17], 'v2|Diminished|7,9,11|None'],
            'secondary-half-diminished7-14-17' => [[0, 3, 6, 10, 14, 17], 'v2|Minor|♭5,♭7,9,11|None'],
        ];
        foreach ($examples as $id => [$intervals, $answer]) {
            $this->assertSame($intervals, $recipes[$id]['intervals']);
            $this->assertSame($answer, $recipes[$id]['answer']);
        }
        $signatures = array_map(fn (array $recipe): string => implode(',', $recipe['intervals']), $recipes);
        $this->assertCount(47, array_unique($signatures));
        $this->assertCount(18, BasicChordNamingSamples::recipes(1));
        $this->assertCount(18, BasicChordNamingSamples::recipes(2));
    }

    public function test_level_six_build_leaves_the_first_five_levels_unchanged(): void
    {
        $before = [];
        foreach ([BasicChordNamingSamples::TITLE, BasicChordNamingSamples::LEVEL_TWO_TITLE, BasicChordNamingSamples::LEVEL_THREE_TITLE, BasicChordNamingSamples::LEVEL_FOUR_TITLE, BasicChordNamingSamples::LEVEL_FIVE_TITLE] as $title) {
            $quiz = Quiz::factory()->create(['title' => $title, 'category' => 'Chord Naming']);
            $question = QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
            $before[] = [$quiz, $quiz->fresh()->getAttributes(), $question, $question->fresh()->getAttributes()];
        }
        ob_start();
        try {
            require base_path('scripts/audio/build_chord_naming_level6.php');
            $levelSix = Quiz::where('title', BasicChordNamingSamples::LEVEL_SIX_TITLE)->firstOrFail();
            $existingIds = $levelSix->questions()->orderBy('position')->pluck('id')->all();
            $attempt = QuizAttempt::factory()->create(['quiz_id' => $levelSix->id, 'question_sequence' => array_slice($existingIds, 0, 5)]);
            $attemptBefore = $attempt->fresh()->getAttributes();
            require base_path('scripts/audio/build_chord_naming_level6.php');
            $this->assertSame($existingIds, $levelSix->questions()->orderBy('position')->pluck('id')->all());
            $this->assertSame($attemptBefore, $attempt->fresh()->getAttributes());
        } finally {
            ob_end_clean();
        }
        foreach ($before as [$quiz, $quizAttributes, $question, $questionAttributes]) {
            $this->assertSame($quizAttributes, $quiz->fresh()->getAttributes());
            $this->assertSame($questionAttributes, $question->fresh()->getAttributes());
        }
        $quiz = Quiz::where('title', BasicChordNamingSamples::LEVEL_SIX_TITLE)->firstOrFail();
        $this->assertSame(1644, $quiz->questions()->count());
        $props = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-naming', 'chord-naming-level-6']))->assertOk()->viewData('page')['props'];
        $this->assertCount(25, $props['quiz']['questions']);
        $this->assertSame(1644, $props['quiz']['questions_total']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertNotEmpty($member['question']['notes']);
    }

    public function test_level_six_has_the_same_forty_seven_structures_in_every_key_with_exact_pitches(): void
    {
        $rows = BasicChordNamingSamples::manifest(BasicChordNamingSamples::KEYS, 3);
        $this->assertCount(564, $rows);
        $counts = array_count_values(array_column($rows, 'key'));
        foreach (BasicChordNamingSamples::KEYS as $key) {
            $this->assertSame(47, $counts[$key]);
        }
        $this->assertSame(BasicChordNamingSamples::manifest(['f'], 3), array_values(array_filter($rows, fn ($row) => $row['key'] === 'f')));
        $natural = ['Do' => 0, 'Re' => 2, 'Mi' => 4, 'Fa' => 5, 'So' => 7, 'La' => 9, 'Ti' => 11];
        foreach ($rows as $row) {
            $root = 48 + array_search($row['key'], BasicChordNamingSamples::KEYS, true);
            $notes = $row['samples']['piano'];
            $actual = array_map(fn ($note) => ($note['octave'] + 1) * 12 + ($natural[$note['degree']] ?? array_search($note['degree'], array_keys(NoteSample::CHROMATIC_SLUGS), true)), $notes);
            $expected = array_map(fn ($tone) => $root + $tone, BasicChordNamingSamples::recipes(3)[$row['recipe']]['intervals']);
            $this->assertSame($expected, $actual);
            $this->assertSame(array_fill(0, count($notes), 0.0), array_column($notes, 'offset'));
        }
    }

    public function test_level_one_build_and_admin_member_audio_match(): void
    {
        $quiz = Quiz::factory()->create(['title' => 'Chord naming', 'category' => 'Chord Naming']);
        QuizQuestion::factory()->create(['quiz_id' => $quiz->id]);
        ob_start();
        try {
            require base_path('scripts/audio/build_basic_chord_naming.php');
        } finally {
            ob_end_clean();
        }
        $this->assertSame(BasicChordNamingSamples::TITLE, $quiz->fresh()->title);
        $this->assertSame(36, $quiz->questions()->count());
        $props = $this->actingAs(User::factory()->admin()->create())->get(route('admin.audio-quiz.lesson', ['chord-naming', 'chord-naming-level-1']))->assertOk()->viewData('page')['props'];
        $this->assertNotNull($props['quiz']['questions'][0]['audio_variants'][0]['notes']);
        $this->actingAs(User::factory()->create())->post(route('quiz.start', $quiz), ['sound' => 'Piano'])->assertRedirect();
        $member = $this->get(route('quiz.play', $quiz))->assertOk()->viewData('page')['props'];
        $this->assertNotEmpty($member['question']['notes']);
        $this->assertSame(array_fill(0, count($member['question']['notes']), 0.0), array_column($member['question']['notes'], 'offset'));
    }
}
