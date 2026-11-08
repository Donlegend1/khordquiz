<?php

namespace App\Support;

class DorianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Dorian Scales #1';

    public const LEGACY_TITLE = 'Dorian Mode';

    public const MOBILE_TITLE = 'Scales · Level 4';

    public const PREFIX = 'scales/dorian-scales-c-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ['c' => 0];

    public const SCALES = [
        'dorian' => ['Dorian', [0, 2, 3, 5, 7, 9, 10]],
        'dorian-flat-two' => ['Dorian ♭2', [0, 1, 3, 5, 7, 9, 10]],
        'dorian-sharp-four' => ['Dorian ♯4', [0, 2, 3, 6, 7, 9, 10]],
        'dorian-flat-five' => ['Dorian ♭5', [0, 2, 3, 5, 6, 9, 10]],
        'dorian-natural-seven' => ['Dorian ♮7', [0, 2, 3, 5, 7, 9, 11]],
    ];
}
