<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private function validMessage(array $overrides = []): array
    {
        return [
            'name' => 'Ada Obi',
            'email' => 'ada@example.com',
            'subject' => 'Bug report',
            'message' => 'The reference audio does not play on my iPad.',
            ...$overrides,
        ];
    }

    public function test_message_is_saved_and_emailed_to_support(): void
    {
        Mail::fake();

        $this->post('/contact', $this->validMessage())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('contact_messages', ['email' => 'ada@example.com', 'subject' => 'Bug report']);

        Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail) {
            return $mail->hasTo(config('mail.contact_address'))
                && $mail->hasReplyTo('ada@example.com')
                && $mail->hasSubject('[Contact] Bug report');
        });
    }

    public function test_required_fields_are_validated(): void
    {
        Mail::fake();

        $this->post('/contact', ['name' => '', 'email' => 'not-an-email', 'subject' => '', 'message' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_honeypot_blocks_bots(): void
    {
        Mail::fake();

        $this->post('/contact', $this->validMessage(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_sending_is_rate_limited(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $attempt) {
            $this->post('/contact', $this->validMessage())->assertRedirect();
        }

        $this->post('/contact', $this->validMessage())->assertStatus(429);
    }
}
