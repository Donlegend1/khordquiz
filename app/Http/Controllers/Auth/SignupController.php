<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BillingController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Role;
use App\Services\Billing\StripeGateway;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

// Reached from the pricing section's plan buttons rather than a bare "/register"
// link — same account creation as RegisteredUserController, then straight on to
// Stripe Checkout for the picked plan (7-day free trial for Monthly/Yearly).
// plan_choice records what they picked; access comes from the Subscription row
// the checkout creates. PayPal isn't wired up yet.
// Without Stripe configured (local dev), it skips payment and goes to onboarding.
class SignupController extends Controller
{
    private const PLANS = ['monthly', 'yearly', 'lifetime'];

    private const PAYMENT_METHODS = ['stripe'];

    /**
     * Display the signup view, pre-selecting the plan from the pricing page.
     */
    public function create(Request $request): Response
    {
        $plan = $request->query('plan');

        return Inertia::render('Auth/Signup', [
            'plan' => in_array($plan, self::PLANS, true) ? $plan : 'yearly',
        ]);
    }

    /**
     * Handle an incoming signup request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, StripeGateway $stripe, BillingController $billing): SymfonyResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'country' => 'nullable|string|max:255',
            'password' => ['required', Rules\Password::defaults()],
            'terms' => ['accepted'],
            'plan' => ['required', Rule::in(self::PLANS)],
            'payment_method' => ['required', Rule::in(self::PAYMENT_METHODS)],
        ]);

        $user = User::create([
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'country' => $data['country'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => Role::Member,
            'terms_accepted_at' => now(),
            'plan_choice' => $data['plan'],
            'payment_method' => $data['payment_method'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        if ($stripe->isConfigured()) {
            return $billing->startCheckout($user, $data['plan']);
        }

        // Same next step for a trial start and a lifetime purchase — both land
        // on the profile-setup screen (ProfileSetupController), matching the
        // mobile app's onboarding flow, before either ever reaches the dashboard.
        return redirect(route('profile-setup.edit', absolute: false));
    }
}
