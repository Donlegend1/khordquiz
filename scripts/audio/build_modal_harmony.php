<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\ModalHarmonySamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$numbersToBuild = $modalHarmonyLessonNumbers ?? range(1, count(ModalHarmonySamples::titles()));
if ($numbersToBuild === [] || array_diff($numbersToBuild, range(1, count(ModalHarmonySamples::titles()))) !== []) {
    throw new InvalidArgumentException('Unknown Modal Harmony lesson selection.');
}
$titlesToBuild = array_map(fn (int $n): string => ModalHarmonySamples::titles()[$n - 1], $numbersToBuild);
$export = [];
foreach (ModalHarmonySamples::titles() as $index => $title) {
    $rows = ModalHarmonySamples::manifest($index + 1);
    if (count($rows) !== (count(ModalHarmonySamples::choices($index + 1)) * 2 * ($index % 2 === 0 ? 1 : 12))) {
        throw new RuntimeException('Incomplete Modal Harmony voicing pool.');
    }
    foreach ($rows as $row) {
        if (count($row['samples']) !== count(ModalHarmonySamples::sounds($index + 1))) {
            throw new RuntimeException('An instrument cannot play the complete Modal Harmony lesson.');
        }
    }
    $export[$title] = $rows;
}
DB::transaction(function () use ($export, $numbersToBuild, $titlesToBuild): void {
    $existing = Quiz::where('category', ModalHarmonySamples::CATEGORY)->whereIn('title', [...$titlesToBuild, ...(in_array(1, $numbersToBuild, true) ? ['Major Triads over Bass'] : [])])->get();
    if (! app()->runningUnitTests() && $existing->isNotEmpty()) {
        $questionIds = $existing->flatMap(fn ($quiz) => $quiz->questions()->pluck('id'));
        $attempts = $existing->flatMap(fn ($quiz) => $quiz->attempts()->get());
        $backup = ['quizzes' => $existing->map->getAttributes()->all(), 'questions' => $existing->flatMap(fn ($quiz) => $quiz->questions()->get()->map->getAttributes())->all(), 'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $questionIds)->get()->map->getAttributes()->all(), 'attempts' => $attempts->map->getAttributes()->all(), 'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attempts->pluck('id'))->get()->all()];
        $path = storage_path('app/private/modal-harmony-before-live-mix-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save Modal Harmony backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    foreach (array_keys($export) as $index => $title) {
        $number = $index + 1;
        if (! in_array($number, $numbersToBuild, true)) {
            continue;
        }
        $quality = $number <= 4 ? 'major' : 'minor';
        $quiz = Quiz::where('category', ModalHarmonySamples::CATEGORY)->where('title', $title)->first();
        if ($quiz === null && $number === 1) {
            $quiz = Quiz::where('category', ModalHarmonySamples::CATEGORY)->where('title', 'Major Triads over Bass')->first();
        }
        $quiz ??= new Quiz(['category' => ModalHarmonySamples::CATEGORY, 'slug' => Quiz::uniqueSlug($title)]);
        $quiz->fill([
            'title' => $title,
            'difficulty' => 'Advanced',
            'question_count' => 25,
            'is_published' => true,
            'description' => 'Identify the modal-color voicing and upper '.$quality.'-triad relationship. '.($number % 2 === 1 ? 'C bass only.' : 'All twelve bass notes.').' '.count(ModalHarmonySamples::choices($number)).' answer options, with two upper-triad positions for each answer. The modal labels describe the taught color associations, not a uniquely determined full scale.',
            'guide_text' => 'Listen to the bass note and the '.$quality.' triad above it. Choose the modal-color label and Roman numeral matching the upper triad relative to the bass. Each answer can be played in two upper-triad positions. The bass stays in the same octave for each key and instrument.',
        ])->save();
        $quiz->attempts()->delete();
        $quiz->questions()->delete();
        foreach ($export[$title] as $position => $row) {
            $question = $quiz->questions()->create(['position' => $position + 1, 'prompt' => 'Which modal-color voicing do you hear?', 'choices' => ModalHarmonySamples::choices($number), 'answer' => $row['answer']]);
            foreach ($row['samples'] as $slug => $notes) {
                $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => ModalHarmonySamples::prefix($number).'/'.ModalHarmonySamples::group($number).'/'.$slug.'/'.$row['recipe'].'.wav']);
            }
        }
    }
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/modal-harmony.json'), json_encode($export, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not export Modal Harmony audio recipes.');
    }
}
$built = array_intersect_key($export, array_flip($titlesToBuild));
echo 'Built '.count($built).' Modal Harmony quizzes with '.array_sum(array_map('count', $built)).' voicings and '.array_sum(array_map(fn (array $rows): int => array_sum(array_map(fn (array $row): int => count($row['samples']), $rows)), $built)).' instrument mixes.'.PHP_EOL;
