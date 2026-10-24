<?php

// Builds "Chordal Progressions" — same 13 chord-progression patterns as
// Chordal melodies / Chordal Bass Lines, same audio (reused, nothing
// re-rendered), but the answer is each chord's QUALITY only (root/degree
// deliberately left out — this tests chord-quality recognition, not root
// identification) instead of a single melody/bass note. Like the other two
// quizzes, every pattern gets TWO rows — one for the original close-position
// voicing's audio, one for the drop-2 voicing's audio — since a chord's
// quality doesn't change between the two voicings; this just gives the
// member the same chord progression in two different voicings/textures.
// Rebuilds the quiz's questions from scratch every run.

require __DIR__.'/chordal_melodies_pool.php';

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

const CHORDAL_PROGRESSION_QUALITY_LABELS = [
    'maj' => 'Major', 'min' => 'Minor', 'dim' => 'Diminished',
    'maj7' => 'Major 7th', 'min7' => 'Minor 7th', 'dom7' => 'Dominant 7th',
    'm7b5' => 'Minor 7(♭5)', 'dim7' => 'Diminished 7th',
];

function chordalProgressionAnswer(array $steps): string
{
    return implode(' - ', array_map(
        fn ($step) => CHORDAL_PROGRESSION_QUALITY_LABELS[$step['quality']],
        $steps
    ));
}

$melodies = Quiz::where('title', 'Chordal melodies')->firstOrFail();
$bassLines = Quiz::where('title', 'Chordal Bass Lines')->firstOrFail();

$quiz = Quiz::firstOrNew(['title' => 'Chordal Progressions']);
if (!$quiz->exists) {
    $quiz->fill([
        'created_by' => $bassLines->created_by,
        'slug' => Quiz::uniqueSlug('Chordal Progressions'),
        'category' => 'Chord Progressions',
        'difficulty' => 'Beginner',
        'reference_audio_tracks' => $bassLines->reference_audio_tracks,
        'is_published' => true,
    ]);
    $quiz->save();
}

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$patterns = chordalLiteralProgressions();
$melodyRows = $melodies->questions()->orderBy('position')->get();
$bassRows = $bassLines->questions()->orderBy('position')->get();

if ($melodyRows->count() < count($patterns) || $bassRows->count() < count($patterns)) {
    echo "Source rows too short — aborting.".PHP_EOL;
    return;
}

$position = 0;
$created = 0;

foreach ($patterns as $i => $steps) {
    $answer = chordalProgressionAnswer($steps);
    $originalAudio = QuizQuestionAudio::where('quiz_question_id', $melodyRows[$i]->id)->where('sound', 'Piano')->first();
    $dropAudio = QuizQuestionAudio::where('quiz_question_id', $bassRows[$i]->id)->where('sound', 'Piano')->first();

    foreach ([$originalAudio, $dropAudio] as $audio) {
        if (!$audio) {
            echo "  pattern {$i}: missing Piano audio on a source row — skipping that variant.".PHP_EOL;
            continue;
        }

        $position++;
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => 'Tap the quality of each chord in the order you hear it.',
            'choices' => [$answer],
            'answer' => $answer,
        ]);
        QuizQuestionAudio::updateOrCreate(
            ['quiz_question_id' => $question->id, 'sound' => 'Piano'],
            ['audio_path' => $audio->audio_path]
        );
        $created++;
    }
}

$quiz->update(['question_count' => $created]);

echo "Created {$created} rows on \"{$quiz->title}\" (quiz id {$quiz->id}).".PHP_EOL;
