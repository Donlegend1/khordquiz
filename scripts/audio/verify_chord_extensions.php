<?php

use App\Models\Quiz;
use App\Support\ChordExtensionQuizBuilder;
use App\Support\DominantChordExtensionSamples;
use App\Support\MajorChordExtensionSamples;
use App\Support\MinorChordExtensionSamples;
use App\Support\NoteSample;

config(['cache.default' => 'array']);
$totalQuestions = 0;
$totalAudio = 0;
foreach (['Major' => MajorChordExtensionSamples::class, 'Minor' => MinorChordExtensionSamples::class, 'Dominant' => DominantChordExtensionSamples::class] as $family => $resolver) {
    foreach (ChordExtensionQuizBuilder::levels($family) as $level => $scope) {
        $quiz = Quiz::where('title', $family.' Chord Extentions · Level '.$level)->firstOrFail();
        $count = 0;
        $keys = [];
        foreach ($quiz->questions()->with('audioVariants')->lazy(100) as $question) {
            if ($question->choices !== ($family === 'Dominant' ? ChordExtensionQuizBuilder::DOMINANT_CHOICES : ChordExtensionQuizBuilder::CHOICES) || $question->audioVariants->isEmpty()) {
                throw new RuntimeException('Invalid choices or orphan question: '.$question->id);
            }
            foreach ($question->audioVariants as $audio) {
                if (in_array($audio->sound, ['Bass Guitar', 'Tuba', 'Upright Bass'], true)
                    || ! preg_match(($scope['basic'] ?? false) ? '#^plain-chord-extensions/'.strtolower($family).'/group([123])/[a-z-]+/([a-z]+)-([a-z0-9]+)\.wav$#' : '#^'.strtolower($family).'-chord-extensions/level([123])/[a-z-]+/([a-z]+)-([a-z0-9]+)-v[1-5]\.wav$#', $audio->audio_path, $match)) {
                    throw new RuntimeException('Incorrect attachment: '.$audio->id);
                }
                if (! in_array((int) $match[1], (array) $scope['group'], true)) {
                    throw new RuntimeException('Incorrect source group: '.$audio->id);
                }
                $keys[$match[2]] = true;
                $intervals = $resolver::qualitiesForLevel((int) $match[1])[$match[3]] ?? null;
                if ($intervals === null || $question->answer !== ChordExtensionQuizBuilder::answerFor($intervals, $family)) {
                    throw new RuntimeException('Answer/audio mismatch: '.$question->id);
                }
                $notes = NoteSample::resolve($audio->audio_path);
                if ($notes === null) {
                    throw new RuntimeException('Unplayable attachment: '.$audio->id);
                }
                foreach ($notes as $note) {
                    if (! NoteSample::sampleExists(NoteSample::path($note['slug'], $note['degree'], $note['octave'])) || $note['offset'] !== 0.0) {
                        throw new RuntimeException('Missing sample or incorrect timing: '.$audio->id);
                    }
                }
                $totalAudio++;
            }
            $count++;
        }
        $actualKeys = array_keys($keys);
        $expectedKeys = $scope['keys'];
        sort($actualKeys);
        sort($expectedKeys);
        if ($actualKeys !== $expectedKeys || $count === 0) {
            throw new RuntimeException('Incorrect key scope: '.$quiz->title);
        }
        $totalQuestions += $count;
        echo $quiz->title.': '.$count.' questions verified.'.PHP_EOL;
    }
}
echo $totalQuestions.' questions, '.$totalAudio.' playable attachments; zero orphan questions.'.PHP_EOL;
