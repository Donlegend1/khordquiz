<?php

namespace App\Support;

class AeolianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Aeolian Scales #1';

    public const LEGACY_TITLE = 'Aeolian Mode';

    public const MOBILE_TITLE = self::TITLE;

    public const PREFIX = 'scales/aeolian-scales-c-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ['c' => 0];

    public const SCALES = [
        'aeolian' => ['Aeolian', [0, 2, 3, 5, 7, 8, 10]],
        'aeolian-flat-two' => ['Aeolian ♭2', [0, 1, 3, 5, 7, 8, 10]],
        'aeolian-natural-six' => ['Aeolian ♮6', [0, 2, 3, 5, 7, 9, 10]],
        'aeolian-natural-seven' => ['Aeolian ♮7', [0, 2, 3, 5, 7, 8, 11]],
        'aeolian-dominant' => ['Aeolian Dominant', [0, 2, 4, 5, 7, 8, 10]],
        'aeolian-flat-five' => ['Aeolian ♭5', [0, 2, 3, 5, 6, 8, 10]],
    ];
}
