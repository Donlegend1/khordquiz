<?php

namespace App\Support;

class MajorModeVoicingSamples extends ModalVoicingSamples
{
    public const TITLE = 'Major Modes';

    public const LEGACY_TITLE = self::TITLE;

    public const PREFIX = 'modal-voicings/major-modes-c-v1';

    /** Complete seven-note collections over C3, with the agreed six-note upper chords. */
    public const VOICINGS = [
        'romanian-major' => ['Romanian Major', [48, 52, 55, 57, 58, 61, 66]],
        'hungarian-major' => ['Hungarian Major', [48, 52, 55, 57, 58, 63, 66]],
        'byzantine-major' => ['Byzantine Major', [48, 52, 55, 56, 59, 61, 65]],
        'harmonic-major' => ['Harmonic Major', [48, 52, 55, 56, 59, 62, 65]],
        'melodic-major' => ['Melodic Major', [48, 52, 55, 56, 58, 62, 65]],
        'major-augmented' => ['Major Augmented', [48, 52, 56, 57, 59, 62, 65]],
    ];
}
