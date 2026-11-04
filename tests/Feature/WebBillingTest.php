<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\Billing\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

class WebBillingTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test',
            'services.stripe.webhook_secret' => 'whsec_test',
            'services.stripe.prices' => ['monthly' => 'price_monthly', 'yearly' => 'price_yearly', 'lifetime' => 'price_lifetime'],
        ]);

        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);
    }

    public function test_members_without_a_plan_are_sent_to_choose_one_before_playing(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $this->actingAs($user)
            ->post(route('quiz.start', $quiz))
            ->assertRedirect(route('subscription', ['locked' => 1]));

        $this->actingAs($user)
            ->get(route('subscription', ['locked' => 1]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Subscription')
                ->where('locked', true)
                ->where('subscription.access', 'none')
                ->where('subscription.trialAvailable', true));
    }

    public function test_admin_granted_plans_and_admins_can_play_but_signup_intent_cannot(): void
    {
        $quiz = Quiz::factory()->create();

        $granted = User::factory()->create(['plan_choice' => 'yearly', 'plan_started_at' => now(), 'plan_expires_at' => now()->addMonth()]);
        $this->assertTrue($granted->hasQuizAccess());

        $picked = User::factory()->create(['plan_choice' => 'yearly']);
        $this->assertFalse($picked->hasQuizAccess());

        $this->assertTrue(User::factory()->admin()->create()->hasQuizAccess());
        $this->actingAs($granted)->post(route('quiz.start', $quiz))->assertSessionHasNoErrors();
    }

    public function test_checkout_starts_a_trial_for_first_time_members(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('billing.checkout', 'yearly'), [], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.test/cs_test');

        $params = $this->stripe->called('createCheckoutSession')[0];
        $this->assertSame('subscription', $params['mode']);
        $this->assertSame('price_yearly', $params['line_items'][0]['price']);
        $this->assertSame(7, $params['subscription_data']['trial_period_days']);
        $this->assertSame('cus_test', Subscription::forUser($user)->stripe_customer_id);
    }

    public function test_no_second_trial_and_lifetime_is_a_one_time_payment(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['trial_used' => true]);

        $this->actingAs($user)->post(route('billing.checkout', 'monthly'));
        $this->actingAs($user)->post(route('billing.checkout', 'lifetime'));

        [$monthly, $lifetime] = $this->stripe->called('createCheckoutSession');
        $this->assertArrayNotHasKey('trial_period_days', $monthly['subscription_data']);
        $this->assertSame('payment', $lifetime['mode']);
    }

    public function test_returning_from_checkout_activates_the_plan_immediately(): void
    {
        $user = User::factory()->create(['avatar' => 'note']);
        Subscription::forUser($user)->update(['stripe_customer_id' => 'cus_test']);
        $this->stripe->subscriptions['sub_1'] = FakeStripeGateway::subscription('sub_1', 'price_yearly', 'trialing', now()->addDays(7)->getTimestamp());
        $this->stripe->sessions['cs_1'] = ['id' => 'cs_1', 'mode' => 'subscription', 'customer' => 'cus_test', 'client_reference_id' => (string) $user->id, 'subscription' => 'sub_1'];

        $this->actingAs($user)
            ->get(route('billing.success', ['session_id' => 'cs_1']))
            ->assertRedirect(route('subscription'));

        $subscription = Subscription::forUser($user);
        $this->assertSame('trial', $subscription->access);
        $this->assertSame('yearly', $subscription->plan_id);
        $this->assertTrue($subscription->trial_used);
        $this->assertTrue($user->fresh()->hasQuizAccess());
    }

    public function test_someone_elses_checkout_session_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->stripe->sessions['cs_1'] = ['id' => 'cs_1', 'mode' => 'payment', 'client_reference_id' => '999999'];

        $this->actingAs($user)->get(route('billing.success', ['session_id' => 'cs_1']))->assertForbidden();
    }

    public function test_webhook_rejects_bad_signatures_and_syncs_signed_events(): void
    {
        $this->app->instance(StripeGateway::class, new StripeGateway); // real signature check
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['stripe_customer_id' => 'cus_test']);

        $payload = json_encode([
            'id' => 'evt_1',
            'object' => 'event',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => FakeStripeGateway::subscription('sub_1', 'price_monthly', 'active', now()->addMonth()->getTimestamp(), ['cancel_at_period_end' => true])],
        ]);

        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=nope'], $payload)->assertStatus(400);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test');
        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}"], $payload)->assertOk();

        $subscription = Subscription::forUser($user);
        $this->assertSame('paid', $subscription->access);
        $this->assertSame('monthly', $subscription->plan_id);
        $this->assertSame('cancelling', $subscription->status);
        $this->assertSame('sub_1', $subscription->stripe_subscription_id);
    }

    public function test_paid_invoices_are_added_to_billing_history_once(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['stripe_customer_id' => 'cus_test']);
        $sync = app(\App\Services\Billing\SubscriptionSync::class);
        $invoice = ['id' => 'in_1', 'customer' => 'cus_test', 'amount_paid' => 4999, 'created' => now()->getTimestamp(), 'lines' => ['data' => [['price' => ['id' => 'price_yearly']]]]];

        $sync->recordInvoice($invoice);
        $sync->recordInvoice($invoice);

        $payments = SubscriptionPayment::query()->where('user_id', $user->id)->get();
        $this->assertCount(1, $payments);
        $this->assertSame('$49.99', $payments[0]->amount);
        $this->assertSame('Premium · Yearly', $payments[0]->plan);
    }

    public function test_cancel_resume_and_switch(): void
    {
        $user = User::factory()->create();
        $periodEnd = now()->addMonth()->getTimestamp();
        Subscription::forUser($user)->update([
            'access' => 'paid', 'plan_id' => 'monthly', 'status' => 'active', 'provider' => 'Stripe',
            'stripe_customer_id' => 'cus_test', 'stripe_subscription_id' => 'sub_1',
            'current_period_end' => \Illuminate\Support\Carbon::createFromTimestamp($periodEnd),
        ]);
        $this->stripe->subscriptions['sub_1'] = FakeStripeGateway::subscription('sub_1', 'price_monthly', 'active', $periodEnd);

        $this->actingAs($user)->post(route('billing.cancel'), ['reason' => 'Too expensive']);
        $this->assertSame('cancelling', Subscription::forUser($user)->status);
        $this->assertSame('too_expensive', $this->stripe->called('updateSubscription')[0][1]['cancellation_details']['feedback']);

        $this->actingAs($user)->post(route('billing.resume'));
        $this->assertSame('active', Subscription::forUser($user)->status);

        // Switching is scheduled: still Monthly until the period rolls over.
        $this->actingAs($user)->post(route('billing.switch'), ['plan' => 'yearly']);
        $subscription = Subscription::forUser($user);
        $this->assertSame('monthly', $subscription->plan_id);
        $this->assertSame('yearly', $subscription->next_plan);
        $this->assertSame('none', end($this->stripe->calls)[1][1]['proration_behavior']);

        // Renewal: Stripe reports the next period on the new price.
        app(\App\Services\Billing\SubscriptionSync::class)->syncStripeSubscription(
            FakeStripeGateway::subscription('sub_1', 'price_yearly', 'active', now()->addYear()->getTimestamp())
        );
        $subscription->refresh();
        $this->assertSame('yearly', $subscription->plan_id);
        $this->assertNull($subscription->next_plan);
    }

    public function test_lifetime_purchase_replaces_the_recurring_plan(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update([
            'access' => 'paid', 'plan_id' => 'yearly', 'status' => 'active', 'provider' => 'Stripe',
            'stripe_customer_id' => 'cus_test', 'stripe_subscription_id' => 'sub_1', 'current_period_end' => now()->addMonth(),
        ]);

        app(\App\Services\Billing\SubscriptionSync::class)->completeCheckout([
            'id' => 'cs_life', 'mode' => 'payment', 'payment_status' => 'paid', 'customer' => 'cus_test',
            'client_reference_id' => (string) $user->id, 'amount_total' => 9999,
        ]);

        $subscription = Subscription::forUser($user);
        $this->assertSame('lifetime', $subscription->plan_id);
        $this->assertNull($subscription->current_period_end);
        $this->assertSame(['sub_1'], $this->stripe->called('cancelSubscriptionNow'));

        // The old subscription's cancellation event doesn't undo Lifetime.
        app(\App\Services\Billing\SubscriptionSync::class)->syncStripeSubscription(FakeStripeGateway::subscription('sub_1', 'price_yearly', 'canceled'));
        $this->assertTrue($user->fresh()->hasQuizAccess());
    }

    public function test_lapsed_paid_plans_lock_after_the_grace_period(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['access' => 'paid', 'plan_id' => 'monthly', 'status' => 'active', 'current_period_end' => now()->subDays(Subscription::GRACE_DAYS + 1)]);

        $this->assertFalse($user->hasQuizAccess());
    }

    public function test_deleting_an_account_cancels_its_stripe_billing(): void
    {
        $user = User::factory()->create();
        Subscription::forUser($user)->update(['provider' => 'Stripe', 'stripe_subscription_id' => 'sub_1', 'access' => 'paid']);

        $user->delete();

        $this->assertSame(['sub_1'], $this->stripe->called('cancelSubscriptionNow'));
    }

    public function test_signup_goes_straight_to_stripe_checkout(): void
    {
        $this->post('/signup', [
            'first_name' => 'Ada',
            'last_name' => 'N',
            'email' => 'ada@example.com',
            'password' => 'password',
            'terms' => true,
            'plan' => 'monthly',
            'payment_method' => 'stripe',
        ], ['X-Inertia' => 'true'])->assertHeader('X-Inertia-Location', 'https://checkout.stripe.test/cs_test');

        $this->assertSame('price_monthly', $this->stripe->called('createCheckoutSession')[0]['line_items'][0]['price']);
    }
}
