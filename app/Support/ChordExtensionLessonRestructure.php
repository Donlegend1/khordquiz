<?php

namespace App\Support;

use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

class ChordExtensionLessonRestructure
{
    public const VARIATION_LEVELS = [7 => 13, 6 => 12, 5 => 11, 4 => 8, 3 => 7, 2 => 4, 1 => 3];

    /** @return array<string, list<array{level: int, quiz_id: int, questions: int, attachments: int}>> */
    public static function apply(array $families = ['Major', 'Minor']): array
    {
        return DB::transaction(function () use ($families): array {
            $report = [];
            foreach (['Major' => MajorChordExtensionSamples::class, 'Minor' => MinorChordExtensionSamples::class, 'Dominant' => DominantChordExtensionSamples::class] as $family => $resolver) {
                if (! in_array($family, $families, true)) {
                    continue;
                }
                $mapping = $family === 'Dominant' ? [4 => 8, 3 => 7, 2 => 4, 1 => 3] : self::VARIATION_LEVELS;
                $lastLevel = $family === 'Dominant' ? 8 : 13;
                if (! Quiz::where('category', $family.' Chord Extensions')->where('title', $family.' Chord Extentions · Level '.$lastLevel)->exists()) {
                    $original = [];
                    foreach ($mapping as $old => $new) {
                        $original[$old] = Quiz::where('category', $family.' Chord Extensions')->where('title', $family.' Chord Extentions · Level '.$old)->firstOrFail();
                    }
                    foreach ($mapping as $old => $new) {
                        DB::table('quizzes')->where('id', $original[$old]->id)->update(['title' => $family.' Chord Extentions · Level '.$new]);
                    }
                }
                $basicLevels = array_filter(ChordExtensionQuizBuilder::levels($family), fn (array $scope): bool => $scope['basic'] ?? false);
                $report[$family] = ChordExtensionQuizBuilder::build($family, $resolver, $basicLevels);
            }

            return $report;
        });
    }
}
