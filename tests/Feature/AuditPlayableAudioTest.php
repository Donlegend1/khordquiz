<?php

namespace Tests\Feature;

use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditPlayableAudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_deduplicates_shared_examples_and_excludes_unplayable_and_hidden_content(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('notes/piano/do.wav', 'sample');
        $quiz = Quiz::factory()->create(['is_published' => true, 'question_count' => 25]);
        foreach (['notes/piano/do.wav', 'notes/piano/do.wav', 'unknown/example.wav', 'notes/piano/re.wav'] as $index => $path) {
            $quiz->questions()->create(['position' => $index + 1, 'prompt' => 'Listen', 'choices' => ['C'], 'answer' => 'C', 'audio_path' => $path]);
        }
        $hidden = Quiz::factory()->create(['is_published' => false]);
        $hidden->questions()->create(['position' => 1, 'prompt' => 'Listen', 'choices' => ['C'], 'answer' => 'C', 'audio_path' => 'notes/piano/do.wav']);
        $output = tempnam(sys_get_temp_dir(), 'audio-audit-');
        try {
            $this->artisan('audio:audit-playable', ['--output' => $output])->assertSuccessful();
            $report = json_decode(file_get_contents($output), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(1, $report['examples']);
            $this->assertSame(1, $report['instruments']);
            $this->assertSame(4, $report['candidates']);
            $this->assertSame(1, $report['unresolved']);
            $this->assertSame(1, $report['missing_notes']);
        } finally {
            unlink($output);
        }
    }
}
