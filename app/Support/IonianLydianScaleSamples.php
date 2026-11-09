<?php

namespace App\Support;

class IonianLydianScaleSamples extends TonalModeScaleSamples
{
    public const TITLE = 'Ionian and Lydian';

    public const MOBILE_TITLE = self::TITLE;

    public const PREFIX = 'scales/ionian-lydian-all-keys-v1';

    public const TONIC_MIDI = 48;

    public const KEYS = ModalVoicingLessons::KEYS;

    public const SCALES = [
        'ionian' => ['Ionian', [0, 2, 4, 5, 7, 9, 11]],
        'lydian' => ['Lydian', [0, 2, 4, 6, 7, 9, 11]],
    ];
}
