<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\Billing\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

// The member's real plan, shaped for the web pages that show it: Subscription,
// Account (plan card) and Delete Account (renewal warning).
class SubscriptionPageController extends Controller
{
    public function show(Request $request, StripeGateway $stripe): Response
    {
        return Inertia::render('Member/Subscription', [
            'subscription' => self::forWeb($request->user()),
            'locked' => $request->boolean('locked') && ! $request->user()->hasQuizAccess(),
            'billingAvailable' => $stripe->isConfigured(),
        ]);
    }

    public function account(Request $request): Response
    {
        return Inertia::render('Member/Account', ['subscription' => self::forWeb($request->user())]);
    }

    public function deleteAccount(Request $request): Response
    {
        return Inertia::render('Member/DeleteAccount', ['subscription' => self::forWeb($request->user())]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forWeb(User $user): array
    {
        $subscription = Subscription::forUser($user);
        $trial = $subscription->trial();
        $access = $subscription->access;
        $planId = $subscription->plan_id;
        $provider = $subscription->provider;
        $status = $subscription->status;
        $renewsAt = $subscription->current_period_end;

        // A plan an admin assigned by hand (Admin → Users) counts too.
        if (! $subscription->unlocksQuizzes() && $user->hasGrantedPlan()) {
            $access = 'paid';
            $planId = $user->plan_choice;
            $provider = 'KhordQuiz';
            $status = 'active';
            $renewsAt = $user->plan_expires_at;
        }

        $date = fn (?Carbon $value) => $value?->timezone(config('app.timezone'))->translatedFormat('j M Y');
        $live = $access !== 'none' && $status !== 'expired' && ($access !== 'trial' || ! ($trial['ended'] ?? false));

        return [
            'access' => $live ? $access : 'none',
            'expired' => $access !== 'none' && ! $live,
            'planId' => $planId,
            'status' => $status,
            'provider' => $provider,
            'renews' => $date($renewsAt) ?? $subscription->renews,
            'since' => $subscription->since,
            'nextPlan' => $subscription->next_plan,
            'trial' => $trial,
            'unlocked' => $user->hasQuizAccess(),
            // What the member can do from the web.
            'manageable' => $provider === Subscription::PROVIDER_STRIPE && $subscription->stripe_subscription_id !== null && $live,
            'hasBillingPortal' => $subscription->stripe_customer_id !== null,
            'trialAvailable' => ! $subscription->trial_used && $subscription->access === 'none',
            'history' => SubscriptionPayment::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(12)
                ->get()
                ->map(fn (SubscriptionPayment $payment) => [
                    'plan' => $payment->plan,
                    'amount' => $payment->amount,
                    'date' => $payment->paid_on,
                    'status' => $payment->status,
                ])
                ->all(),
        ];
    }
}
