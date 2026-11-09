<?php

namespace App\Support;

class MinorModeVoicingSamples extends ModalVoicingSamples
{
    public const TITLE = 'Minor Modes';

    public const LEGACY_TITLE = self::TITLE;

    public const PREFIX = 'modal-voicings/minor-modes-c-v1';

    /** Complete seven-note collections over C3, with the agreed six-note upper chords. */
    public const VOICINGS = [
        'natural-minor' => ['Natural Minor', [48, 51, 55, 56, 58, 62, 65]],
        'harmonic-minor' => ['Harmonic Minor', [48, 51, 55, 56, 59, 62, 65]],
        'melodic-minor' => ['Melodic Minor', [48, 51, 55, 57, 59, 62, 65]],
        'hungarian-minor' => ['Hungarian Minor', [48, 51, 55, 56, 59, 62, 66]],
        'romanian-minor' => ['Romanian Minor', [48, 51, 55, 57, 58, 62, 66]],
        'neapolitan-minor' => ['Neapolitan Minor', [48, 51, 55, 56, 59, 61, 65]],
    ];
}
