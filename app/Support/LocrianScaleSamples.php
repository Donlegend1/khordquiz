<?php

namespace App\Support;

class LocrianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Locrian Scales #1';

    public const LEGACY_TITLE = 'Locrian Mode';

    public const MOBILE_TITLE = 'Scales · Level 6';

    public const PREFIX = 'scales/locrian-scales-c-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ['c' => 0];

    public const SCALES = [
        'locrian' => ['Locrian', [0, 1, 3, 5, 6, 8, 10]],
        'locrian-natural-two' => ['Locrian ♮2', [0, 2, 3, 5, 6, 8, 10]],
        'locrian-natural-six' => ['Locrian ♮6', [0, 1, 3, 5, 6, 9, 10]],
        'locrian-dominant' => ['Locrian Dominant', [0, 1, 4, 5, 6, 8, 10]],
        'super-locrian' => ['Super Locrian', [0, 1, 3, 4, 6, 8, 10]],
    ];
}
