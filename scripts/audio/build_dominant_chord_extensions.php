<?php

use App\Support\ChordExtensionQuizBuilder;
use App\Support\DominantChordExtensionSamples;

foreach (ChordExtensionQuizBuilder::build('Dominant', DominantChordExtensionSamples::class) as $result) {
    echo 'Dominant Level '.$result['level'].': '.$result['questions'].' questions, '.$result['attachments'].' playable instrument attachments.'.PHP_EOL;
}
