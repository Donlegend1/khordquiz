<?php

namespace Tests;

use App\Support\DiatonicProgressionSamples;
use App\Support\NoteSample;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // These caches are request-local in production, but PHPUnit reuses PHP
        // across application/storage instances. Never reuse a previous bank.
        (new \ReflectionProperty(NoteSample::class, 'instrumentFilesMemo'))->setValue(null, []);
        (new \ReflectionProperty(DiatonicProgressionSamples::class, 'voicingCache'))->setValue(null, []);
    }
}
