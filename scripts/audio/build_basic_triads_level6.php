<?php

// Builds "Basic Triads · Level 6" — same compound quality+inversion answer
// shape as Level 5 (COMPOUND_ANSWER_PRESETS in quizAnswerPresets.js, answer
// composed as "{quality} · {inversion}"), but across ALL 12 keys (C3 through
// B3) instead of just C. Major/Minor/Diminished only — Augmented and
// Suspended (Sus2) are deliberately excluded here (matching Level 8, the
// drop-2 version of this level): with no fixed key to anchor against (unlike
// Level 5, which stays on C throughout), a member can't tell inversion apart
// by ear for these two qualities. An augmented triad is symmetric (stacked
// major 3rds), so root/1st/2nd inversion all have the IDENTICAL relative
// interval shape — e.g. "E augmented root position" and "C augmented 1st
// inversion" are the same pitch classes, indistinguishable without absolute
// pitch. A Sus2 chord's 2nd inversion has the same relative shape as some
// other root's Sus4 chord in root position, and Sus4 was never one of this
// quiz's own answer options to tell them apart with. Major/Minor/Diminished
// don't have this problem — each inversion has its own distinct interval
// shape regardless of root. 3 qualities x 3 inversions x 12 keys = 108 rows
// — MORE than the quiz's question_count (25), same pool-larger-than-
// question_count pattern as Levels 2/4/8: QuizController::buildQuestionSequence()
// fair-shuffles a random 25 per attempt instead of hard-cycling a fixed
// subset. Rebuilds the quiz's questions and every instrument's attached
// audio from scratch every run (reuses already-rendered Major/Minor/
// Diminished files; the Augmented/Suspended files rendered for the original
// 180-combo version are simply no longer referenced).

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionAudio;

// Major/Minor/Diminished only — Augmented and Suspended deliberately excluded.
const BASIC_TRIADS_L6_QUALITY_LABELS = [
    'major' => 'Major', 'minor' => 'Minor', 'diminished' => 'Diminished',
];

const BASIC_TRIADS_L6_INVERSION_LABELS = ['root' => 'Root Position', '1st' => '1st Inversion', '2nd' => '2nd Inversion'];

// key slug => display name, C3 through B3 chromatically.
const BASIC_TRIADS_L6_KEYS = [
    'c' => 'C', 'db' => 'Db', 'd' => 'D', 'eb' => 'Eb', 'e' => 'E', 'f' => 'F',
    'gb' => 'Gb', 'g' => 'G', 'ab' => 'Ab', 'a' => 'A', 'bb' => 'Bb', 'b' => 'B',
];

// sound => storage folder slug, matching Levels 1-5's naming.
const BASIC_TRIADS_L6_INSTRUMENTS = [
    'Piano' => 'piano', 'EP' => 'ep', 'Organ' => 'organ',
    'Guitar' => 'guitar', 'Jazz Guitar' => 'jazz-guitar', 'Bass Guitar' => 'bass-guitar',
    'Violin' => 'violin', 'Strings' => 'strings', 'Cello' => 'cello', 'Harp' => 'harp',
    'Voice' => 'voice',
];

$quiz = Quiz::where('title', 'Basic Triads · Level 6')->firstOrFail();

QuizAttempt::where('quiz_id', $quiz->id)->whereDoesntHave('answers')->delete();
$quiz->questions()->delete();

$position = 0;
$created = 0;
$audioAttached = 0;

foreach (BASIC_TRIADS_L6_KEYS as $keySlug => $keyName) {
    foreach (BASIC_TRIADS_L6_QUALITY_LABELS as $qualitySlug => $qualityLabel) {
        foreach (BASIC_TRIADS_L6_INVERSION_LABELS as $invSlug => $invLabel) {
            $position++;
            $answer = $qualityLabel.' · '.$invLabel;

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'position' => $position,
                'prompt' => 'Listen to the triad and identify its quality and inversion.',
                'choices' => [$answer],
                'answer' => $answer,
            ]);

            foreach (BASIC_TRIADS_L6_INSTRUMENTS as $sound => $folderSlug) {
                QuizQuestionAudio::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sound' => $sound],
                    ['audio_path' => "basic-triads/level6/{$folderSlug}/{$keySlug}-{$qualitySlug}-{$invSlug}.wav"]
                );
                $audioAttached++;
            }
            $created++;
        }
    }
}

echo "Created {$created} rows on \"{$quiz->title}\" (pool of ".count(BASIC_TRIADS_L6_KEYS)." keys x 3 qualities x 3 inversions; question_count stays {$quiz->question_count}), attached {$audioAttached} audio rows across ".count(BASIC_TRIADS_L6_INSTRUMENTS)." instruments.".PHP_EOL;
