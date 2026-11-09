<?php

namespace App\Support;

class IonianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Ionian Scales #1';

    public const LEGACY_TITLE = 'Ionian Mode';

    public const MOBILE_TITLE = self::TITLE;

    public const PREFIX = 'scales/ionian-scales-c-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ['c' => 0];

    public const SCALES = [
        'ionian' => ['Ionian', [0, 2, 4, 5, 7, 9, 11]],
        'ionian-flat-two' => ['Ionian ♭2', [0, 1, 4, 5, 7, 9, 11]],
        'ionian-sharp-two' => ['Ionian ♯2', [0, 3, 4, 5, 7, 9, 11]],
        'ionian-augmented' => ['Ionian Augmented', [0, 2, 4, 5, 8, 9, 11]],
        'ionian-flat-six' => ['Ionian ♭6', [0, 2, 4, 5, 7, 8, 11]],
        'ionian-flat-two-flat-six' => ['Ionian ♭2 ♭6', [0, 1, 4, 5, 7, 8, 11]],
    ];
}
