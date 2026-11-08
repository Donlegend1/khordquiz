<?php

use App\Models\Quiz;
use App\Models\QuizQuestionAudio;
use App\Support\ModalVoicingLessons;
use App\Support\ModalVoicingSamples;
use App\Support\QuizCatalog;
use Illuminate\Support\Facades\DB;

config(['cache.default' => 'array']);
$lessons = ModalVoicingLessons::lessons();
$export = [];
foreach ($lessons as $lesson) {
    $export[$lesson['title']] = ModalVoicingLessons::manifest($lesson['family'], $lesson['number']);
}
DB::transaction(function () use ($lessons, $export): void {
    $existing = Quiz::where('category', ModalVoicingSamples::CATEGORY)->get();
    if (! app()->runningUnitTests() && $existing->isNotEmpty()) {
        $questionIds = $existing->flatMap(fn ($quiz) => $quiz->questions()->pluck('id'));
        $attempts = $existing->flatMap(fn ($quiz) => $quiz->attempts()->get());
        $backup = ['quizzes' => $existing->map->getAttributes()->all(), 'questions' => $existing->flatMap(fn ($quiz) => $quiz->questions()->get()->map->getAttributes())->all(), 'audio' => QuizQuestionAudio::whereIn('quiz_question_id', $questionIds)->get()->map->getAttributes()->all(), 'attempts' => $attempts->map->getAttributes()->all(), 'answers' => DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attempts->pluck('id'))->get()->all()];
        $path = storage_path('app/private/modal-voicings-before-pairs-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json');
        if (file_put_contents($path, json_encode($backup, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Could not save modal voicing backup.');
        }
        echo 'Saved backup: '.$path.PHP_EOL;
    }
    foreach ($lessons as $lesson) {
        ['title' => $title, 'family' => $family, 'number' => $number, 'choices' => $choices] = $lesson;
        $source = ModalVoicingLessons::FAMILIES[$family];
        $query = Quiz::where('category', ModalVoicingSamples::CATEGORY);
        $quiz = $query->where('title', $title)->first();
        if ($quiz === null && $number === 1) {
            $quiz = Quiz::where('category', ModalVoicingSamples::CATEGORY)->whereIn('title', [$source::TITLE, $source::LEGACY_TITLE])->first();
        }
        $quiz ??= new Quiz(['category' => ModalVoicingSamples::CATEGORY, 'slug' => 'modal-voicings-'.$family.'-'.$number]);
        $quiz->fill(['title' => $title, 'difficulty' => 'Advanced', 'question_count' => 25, 'is_published' => true, 'description' => 'Identify the modal color. '.($number === 1 ? 'Key of F only.' : 'All twelve keys.').' The agreed chord structures are transposed intact, with a consistent bass octave for each key and instrument.'])->save();
        $quiz->attempts()->delete();
        $quiz->questions()->delete();
        foreach ($export[$title] as $index => $row) {
            $question = $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen to the voicing and identify the mode.', 'choices' => $choices, 'answer' => $row['answer']]);
            foreach ($row['samples'] as $slug => $notes) {
                $question->audioVariants()->create(['sound' => array_search($slug, QuizCatalog::SOUND_SLUGS, true), 'audio_path' => ModalVoicingLessons::PREFIX.'/'.$family.'/'.$slug.'/'.$row['recipe'].'.wav']);
            }
        }
    }
});
if (! app()->runningUnitTests()) {
    if (file_put_contents(storage_path('app/private/modal-voicings-pairs.json'), json_encode($export, JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not export modal voicings.');
    }
}
echo 'Built '.count($lessons).' modal lessons with '.array_sum(array_map('count', $export)).' voicings.'.PHP_EOL;
