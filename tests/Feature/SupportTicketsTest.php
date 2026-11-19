<?php

namespace Tests\Feature;

use App\Mail\SupportRequestReceived;
use App\Models\SupportMessage;
use App\Models\User;
use App\Notifications\SupportReplied;
use App\Services\SupportDesk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function openRequest(User $member, string $source = 'web'): SupportMessage
    {
        return app(SupportDesk::class)->open($member, ['topic' => 'Bug report', 'subject' => 'No sound', 'message' => 'The quiz audio does not play.'], $source);
    }

    public function test_members_see_their_own_requests_on_the_support_page(): void
    {
        $member = User::factory()->create();
        $this->openRequest($member);
        $this->openRequest(User::factory()->create());

        $this->actingAs($member)->get(route('support'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Member/Support')
                ->has('requests', 1)
                ->where('requests.0.subject', 'No sound')
                ->where('requests.0.status', 'open')
            );
    }

    public function test_members_can_only_open_their_own_threads(): void
    {
        $member = User::factory()->create();
        $request = $this->openRequest($member);

        $this->actingAs($member)->get(route('support.requests.show', $request))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Member/SupportRequest')->where('request.message', 'The quiz audio does not play.'));

        $this->actingAs(User::factory()->create())->get(route('support.requests.show', $request))->assertNotFound();
        $this->actingAs(User::factory()->create())->post(route('support.requests.reply', $request), ['body' => 'Hijack'])->assertNotFound();
    }

    public function test_an_admin_reply_notifies_the_member_and_their_follow_up_reopens_the_request(): void
    {
        Notification::fake();
        $member = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $request = $this->openRequest($member);

        $this->actingAs($admin)->post(route('admin.support.reply', $request), ['body' => 'Try turning off silent mode.'])->assertRedirect();

        $this->assertSame('answered', $request->fresh()->status);
        Notification::assertSentTo($member, SupportReplied::class, fn (SupportReplied $notification) => $notification->toArray($member)['url'] === '/support/requests/'.$request->id);

        $this->actingAs($member)->post(route('support.requests.reply', $request), ['body' => 'Still silent, sadly.'])->assertRedirect();

        $this->assertSame('open', $request->fresh()->status);
        $this->assertSame([true, false], $request->replies()->pluck('from_staff')->all());
        Mail::assertSent(SupportRequestReceived::class, fn (SupportRequestReceived $mail) => $mail->reply?->body === 'Still silent, sadly.');
    }

    public function test_admins_can_reply_and_resolve_in_one_step_and_reopen_later(): void
    {
        $admin = User::factory()->admin()->create();
        $request = $this->openRequest(User::factory()->create());

        $this->actingAs($admin)->post(route('admin.support.reply', $request), ['body' => 'Fixed in the latest update.', 'resolve' => true]);
        $this->assertSame('resolved', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->resolved_at);

        $this->actingAs($admin)->patch(route('admin.support.status', $request), ['status' => 'open']);
        $this->assertSame('open', $request->fresh()->status);
        $this->assertNull($request->fresh()->resolved_at);
    }

    public function test_the_inbox_lists_and_filters_requests_for_admins_only(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['name' => 'Grace Ade']);
        $this->openRequest($member);
        $resolved = $this->openRequest(User::factory()->create(), 'app');
        app(SupportDesk::class)->setStatus($resolved, 'resolved');

        $this->actingAs($admin)->get(route('admin.support.index', ['status' => 'open']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Support/Index')
                ->where('counts.open', 1)
                ->where('counts.resolved', 1)
                ->has('requests.data', 1)
                ->where('requests.data.0.member.name', 'Grace Ade')
                ->where('openSupportRequests', 1)
            );

        $this->actingAs($member)->get(route('admin.support.index'))->assertForbidden();
    }

    public function test_the_app_creates_the_same_ticket_and_can_follow_the_thread(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $id = $this->postJson('/api/v1/support', ['topic' => 'Billing', 'message' => 'I was charged twice this month.'])
            ->assertCreated()
            ->assertJsonPath('support_message.status', 'open')
            ->json('support_message.id');

        $this->assertSame('app', SupportMessage::findOrFail($id)->source);
        Mail::assertSent(SupportRequestReceived::class);

        $this->postJson("/api/v1/support/requests/{$id}/replies", ['body' => 'Here is my receipt number.'])->assertCreated();

        $this->getJson('/api/v1/support/requests')->assertOk()->assertJsonPath('requests.0.replies_count', 1);
        $this->getJson("/api/v1/support/requests/{$id}")->assertOk()->assertJsonPath('request.replies.0.body', 'Here is my receipt number.');
    }
}
