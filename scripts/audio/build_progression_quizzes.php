<?php

// Builds real content for the 5 "Chord Progressions" quizzes that were
// stuck on a single row padded to 25 (Diatonic Progression, Progression
// Build #1/#2, Progression recognition, 2-5-1 chord degree). Some presets
// only have a handful of genuinely distinct answers (quality-fixed-by-degree
// or fixed-degree-sequence configs) — per the "no padding to 25" rule, only
// the real distinct rows are kept, not padded back up to 25 with repeats.
// `answer` is composeProgression()'s exact "degree Quality(ext) - ..."
// format, already what the frontend submits — no new encoding needed here,
// unlike Chord naming (whose old answer format was lossy).

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const PROGRESSION_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
    'Trombone' => 'trombone', 'Saxophone' => 'saxophone', 'Marimba' => 'marimba',
    'Celesta' => 'celesta', 'Vibraphone' => 'vibraphone',
];

const PRESETS = [
    'diatonic-progression' => ['title' => 'Diatonic Progression', 'folder' => 'diatonic-progression'],
    'progression-build-1' => ['title' => 'Progression Build #1', 'folder' => 'progression-build-1'],
    'progression-build-2' => ['title' => 'Progression Build #2', 'folder' => 'progression-build-2'],
    'progression-recognition' => ['title' => 'Progression recognition', 'folder' => 'progression-recognition'],
    '2-5-1-chord-degree' => ['title' => '2-5-1 chord degree', 'folder' => '2-5-1-chord-degree'],
];

const PROMPTS = [
    'diatonic-progression' => 'Listen to the progression and choose the number of each chord.',
    'progression-build-1' => 'Listen to the progression and choose the number and quality of each chord.',
    'progression-build-2' => 'Listen to the progression and choose the number and 7th chord quality of each chord.',
    'progression-recognition' => 'Listen to the progression and select the chord quality for each chord.',
    '2-5-1-chord-degree' => 'Listen to the progression and select the chord quality and every degree for each chord.',
];

foreach (PRESETS as $presetKey => $preset) {
    $quiz = Quiz::where('title', $preset['title'])->firstOrFail();

    QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
    $quiz->questions()->delete();

    $rows = [];
    exec("php ".__DIR__."/gen_progression_voicings.php {$presetKey} 48", $rows);

    $seenAnswers = [];
    $position = 0;
    $created = 0;
    $audioAttached = 0;

    foreach ($rows as $line) {
        [$slug, $answer] = explode("\t", $line);
        if (isset($seenAnswers[$answer])) {
            continue; // real-duplicate skip — see "no padding" note above
        }
        $seenAnswers[$answer] = true;
        $position++;

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => PROMPTS[$presetKey],
            'choices' => [],
            'answer' => $answer,
        ]);

        foreach (PROGRESSION_INSTRUMENTS as $sound => $folderSlug) {
            QuizQuestionAudio::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sound' => $sound],
                ['audio_path' => "chord-progressions/{$presetKey}/{$folderSlug}/{$slug}.wav"]
            );
            $audioAttached++;
        }
        $created++;
    }

    echo "{$preset['title']}: created {$created} rows (question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(PROGRESSION_INSTRUMENTS)." instruments.".PHP_EOL;
}
