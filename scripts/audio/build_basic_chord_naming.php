<?php

use App\Support\ChordNamingQuizBuilder;

config(['cache.default' => 'array']);
foreach (ChordNamingQuizBuilder::build([1]) as $row) {
    echo 'Level '.$row['level'].': '.$row['questions'].' questions, '.$row['mixes'].' playable instrument mixes.'.PHP_EOL;
}
