<?php

namespace App\Support;

class IonianVoicingSamples extends ModalVoicingSamples
{
    public const TITLE = 'Ionian Mode';

    public const LEGACY_TITLE = self::TITLE;

    public const PREFIX = 'modal-voicings/ionian-mode-c-v1';

    /** Six-note voicings retain the C bass, major third, and major seventh. */
    public const VOICINGS = [
        'ionian' => ['Ionian', [48, 55, 57, 59, 62, 64]],
        'ionian-flat-two' => ['Ionian ♭2', [48, 55, 57, 59, 61, 64]],
        'ionian-sharp-two' => ['Ionian ♯2', [48, 55, 57, 59, 63, 64]],
        'ionian-augmented' => ['Ionian Augmented', [48, 56, 57, 59, 62, 64]],
        'ionian-flat-six' => ['Ionian ♭6', [48, 55, 56, 59, 62, 64]],
        'ionian-flat-two-flat-six' => ['Ionian ♭2 ♭6', [48, 55, 56, 59, 61, 64]],
    ];
}
