<?php

namespace Tests\Feature;

use App\Mail\SupportRequestReceived;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportEmailTest extends TestCase
{
    use RefreshDatabase;

    private function validMessage(array $overrides = []): array
    {
        return [
            'topic' => 'Bug report',
            'subject' => 'Audio does not play',
            'message' => 'The reference audio does not play on my iPad.',
            'include_device' => true,
            'device_info' => 'App version 1.0.0 · Safari',
            ...$overrides,
        ];
    }

    public function test_member_can_send_a_support_message(): void
    {
        Mail::fake();
        $user = User::factory()->create(['name' => 'Ada Obi', 'email' => 'ada@example.com']);

        $this->actingAs($user)
            ->post('/support-email', $this->validMessage())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('support_messages', [
            'user_id' => $user->id,
            'topic' => 'Bug report',
            'subject' => 'Audio does not play',
            'source' => 'web',
            'status' => 'open',
        ]);
        $this->assertStringContainsString('Safari', SupportMessage::firstOrFail()->message);

        Mail::assertSent(SupportRequestReceived::class, function (SupportRequestReceived $mail) {
            return $mail->hasTo(config('mail.contact_address'))
                && $mail->hasReplyTo('ada@example.com');
        });
    }

    public function test_an_attachment_is_stored_and_emailed(): void
    {
        Mail::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('screenshot.png')->size(500);

        $this->actingAs($user)
            ->post('/support-email', $this->validMessage(['attachment' => $file]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $supportMessage = SupportMessage::firstOrFail();
        $this->assertNotNull($supportMessage->attachment_path);
        $this->assertSame('screenshot.png', $supportMessage->attachment_name);
        Storage::disk('public')->assertExists($supportMessage->attachment_path);

        Mail::assertSent(SupportRequestReceived::class, fn (SupportRequestReceived $mail) => count($mail->attachments()) === 1);
    }

    public function test_attachment_over_10mb_is_rejected(): void
    {
        Mail::fake();
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf');

        $this->actingAs($user)
            ->post('/support-email', $this->validMessage(['attachment' => $file]))
            ->assertSessionHasErrors('attachment');

        $this->assertSame(0, SupportMessage::count());
        Mail::assertNothingSent();
    }

    public function test_guests_cannot_send_a_support_message(): void
    {
        $this->post('/support-email', $this->validMessage())->assertRedirect('/login');
    }

    public function test_required_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/support-email', ['topic' => '', 'message' => ''])
            ->assertSessionHasErrors(['topic', 'message']);

        $this->assertSame(0, SupportMessage::count());
    }
}
