import CountryPicker from '@/Components/CountryPicker';
import InputError from '@/Components/InputError';
import { PLAN_INFO } from '@/lib/subscription';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const fieldClass = (error) =>
    `block w-full rounded-lg border bg-white px-3.5 py-2.5 text-[15px] text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 ${
        error
            ? 'border-brand focus:border-brand focus:ring-brand/20'
            : 'border-gray-300 focus:border-neutral-900 focus:ring-neutral-900/10'
    }`;

function EyeIcon({ open, ...props }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            {open ? (
                <>
                    <path d="M3 3l18 18" />
                    <path d="M10.6 5.1A10.4 10.4 0 0112 5c6 0 9.5 7 9.5 7a17.6 17.6 0 01-3.1 4.1M6.6 6.6A17.4 17.4 0 002.5 12s3.5 7 9.5 7a9.7 9.7 0 005.4-1.6" />
                    <path d="M9.9 9.9a3 3 0 004.2 4.2" />
                </>
            ) : (
                <>
                    <path d="M2.5 12S6 5 12 5s9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z" />
                    <circle cx="12" cy="12" r="3" />
                </>
            )}
        </svg>
    );
}

function CheckCircleIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="9" fill="#111827" stroke="none" />
            <path d="M8 12.3l2.6 2.6L16.2 9" stroke="#fff" />
        </svg>
    );
}

// Simplified, brand-appropriate marks — not the official logos — matching
// how StoreBadges.jsx already represents Apple/Google this way.
function StripeIcon(props) {
    return (
        <svg viewBox="0 0 24 24" {...props}>
            <rect width="24" height="24" rx="6" fill="#635BFF" />
            <path
                fill="#fff"
                d="M11.1 9.6c0-.6.5-.8 1.3-.8.9 0 2.2.3 3.1.8V7c-1-.4-2-.6-3.1-.6-2.5 0-4.2 1.3-4.2 3.5 0 3.4 4.7 2.9 4.7 4.3 0 .6-.5.8-1.4.8-1 0-2.5-.4-3.5-1v2.7c1.1.5 2.2.7 3.5.7 2.6 0 4.4-1.3 4.4-3.5-.1-3.7-4.8-3-4.8-4.3z"
            />
        </svg>
    );
}

function PayPalIcon(props) {
    return (
        <svg viewBox="0 0 24 24" {...props}>
            <rect width="24" height="24" rx="6" fill="#003087" />
            <path fill="#009CDE" d="M9.6 17.5l.5-3H12c2.9 0 5.1-1.2 5.7-4.5.1-.4.1-.8.1-1.1-.9.5-2 .7-3.2.7H11l-1.4 7.9z" />
            <path fill="#fff" d="M8.3 6.5h3.2c1.7 0 3 .4 3.5 1.5.3.6.3 1.3.1 2.2-.6 3.3-2.8 4.5-5.7 4.5H8l-.9 5H5l2-11.5c.1-.4.5-.7.9-.7h.4z" />
        </svg>
    );
}

function DiamondIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 3h12l4 6-10 12L2 9Z" />
            <path d="M11 3 8 9l4 12 4-12-3-6" />
            <path d="M2 9h20" />
        </svg>
    );
}

const PLAN_ORDER = ['monthly', 'yearly', 'lifetime'];

// Intent only — there's no Stripe/PayPal integration or subscriptions table
// yet, so picking one here just records which method the member prefers.
const PAYMENT_METHODS = [
    { id: 'stripe', label: 'Stripe', icon: StripeIcon },
    { id: 'paypal', label: 'PayPal', icon: PayPalIcon },
];

export default function Signup({ plan: initialPlan }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        country: '',
        password: '',
        terms: false,
        plan: initialPlan ?? 'yearly',
        payment_method: null,
    });

    const plan = PLAN_INFO[data.plan];
    const isLifetime = data.plan === 'lifetime';
    const isTrial = !isLifetime;

    const submit = (e) => {
        e.preventDefault();

        post(route('signup'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div
            className="flex min-h-screen flex-col"
            style={{
                backgroundColor: '#FBF6F3',
                backgroundImage: [
                    'linear-gradient(to right, rgba(17, 24, 39, 0.05) 1px, transparent 1px)',
                    'linear-gradient(to bottom, rgba(17, 24, 39, 0.05) 1px, transparent 1px)',
                    'linear-gradient(to bottom, #F3F6FB 0%, #FBF4F0 55%, #FDEFE7 100%)',
                ].join(', '),
                backgroundSize: '48px 48px, 48px 48px, 100% 100%',
            }}
        >
            <Head title="Create your account" />

            <main className="flex flex-1 items-center justify-center px-6 py-14">
                <div className="w-full max-w-[560px] rounded-2xl border border-white bg-white/90 px-6 py-8 shadow-[0_18px_50px_-24px_rgba(17,24,39,0.35)] sm:px-8">
                    <Link href="/" aria-label="KhordQuiz home" className="mx-auto mb-2 block w-fit">
                        <img src="/images/logo-mark.png" alt="KhordQuiz" width="287" height="192" className="block h-14 w-auto" />
                    </Link>
                    <h1 className="mt-4 text-center text-xl font-bold text-neutral-900">Create your account</h1>
                    <p className="mt-2 text-center text-sm text-neutral-500">
                        {isLifetime ? "You're one step from lifetime access." : 'Start your 7-day free trial today.'}
                    </p>

                    {/* Plan summary */}
                    <div
                        className={`mt-6 rounded-xl p-4 ${isLifetime ? 'bg-neutral-900 text-white' : 'border border-black/5 bg-neutral-50'}`}
                    >
                        <div className="flex items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                {isLifetime && <DiamondIcon className="h-4 w-4 text-amber-300" />}
                                <div>
                                    <p className={`text-xs font-bold uppercase tracking-wide ${isLifetime ? 'text-amber-300' : 'text-neutral-400'}`}>
                                        {plan.tier}
                                    </p>
                                    <p className={`text-base font-bold ${isLifetime ? 'text-white' : 'text-neutral-900'}`}>{plan.label} plan</p>
                                </div>
                            </div>
                            <div className="text-right">
                                {plan.originalPrice && <p className="text-xs text-neutral-400 line-through">{plan.originalPrice}</p>}
                                <p className={`text-lg font-extrabold ${isLifetime ? 'text-white' : 'text-neutral-900'}`}>{plan.price}</p>
                            </div>
                        </div>

                        <div className="mt-3 flex items-center justify-between gap-3">
                            <div className="flex flex-wrap gap-1.5">
                                {PLAN_ORDER.map((id) => (
                                    <button
                                        key={id}
                                        type="button"
                                        onClick={() => setData('plan', id)}
                                        className={`rounded-full px-2.5 py-1 text-xs font-semibold transition-colors duration-150 ${
                                            data.plan === id
                                                ? isLifetime
                                                    ? 'bg-amber-400 text-neutral-900'
                                                    : 'bg-neutral-900 text-white'
                                                : isLifetime
                                                  ? 'bg-white/10 text-white/70 hover:bg-white/15'
                                                  : 'bg-white text-neutral-600 hover:bg-neutral-100'
                                        }`}
                                    >
                                        {PLAN_INFO[id].label}
                                    </button>
                                ))}
                            </div>
                            {isTrial && (
                                <span className="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                    7 DAYS FREE
                                </span>
                            )}
                        </div>
                    </div>

                    <form onSubmit={submit} className="mt-6">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="first_name" className="mb-1.5 block text-sm font-medium text-neutral-800">
                                    First name
                                </label>
                                <input
                                    id="first_name"
                                    type="text"
                                    name="first_name"
                                    value={data.first_name}
                                    className={fieldClass(errors.first_name)}
                                    autoComplete="given-name"
                                    autoFocus
                                    placeholder="First name"
                                    onChange={(e) => setData('first_name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.first_name} className="mt-1.5" />
                            </div>

                            <div>
                                <label htmlFor="last_name" className="mb-1.5 block text-sm font-medium text-neutral-800">
                                    Last name
                                </label>
                                <input
                                    id="last_name"
                                    type="text"
                                    name="last_name"
                                    value={data.last_name}
                                    className={fieldClass(errors.last_name)}
                                    autoComplete="family-name"
                                    placeholder="Last name"
                                    onChange={(e) => setData('last_name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.last_name} className="mt-1.5" />
                            </div>
                        </div>

                        <div className="mt-4">
                            <label htmlFor="email" className="mb-1.5 block text-sm font-medium text-neutral-800">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className={fieldClass(errors.email)}
                                autoComplete="username"
                                placeholder="you@example.com"
                                onChange={(e) => setData('email', e.target.value)}
                                required
                            />
                            <InputError message={errors.email} className="mt-1.5" />
                        </div>

                        <div className="mt-4">
                            <label className="mb-1.5 block text-sm font-medium text-neutral-800">Country / Region</label>
                            <CountryPicker
                                value={data.country}
                                onChange={(value) => setData('country', value)}
                                triggerClassName={fieldClass(errors.country)}
                            />
                            <InputError message={errors.country} className="mt-1.5" />
                        </div>

                        <div className="mt-4">
                            <label htmlFor="password" className="mb-1.5 block text-sm font-medium text-neutral-800">
                                Password
                            </label>
                            <div className="relative">
                                <input
                                    id="password"
                                    type={showPassword ? 'text' : 'password'}
                                    name="password"
                                    value={data.password}
                                    className={`${fieldClass(errors.password)} pe-11`}
                                    autoComplete="new-password"
                                    placeholder="Password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((visible) => !visible)}
                                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                                    className="absolute inset-y-0 right-0 flex items-center px-3.5 text-neutral-400 hover:text-neutral-700"
                                >
                                    <EyeIcon open={showPassword} className="h-[18px] w-[18px]" />
                                </button>
                            </div>
                            <InputError message={errors.password} className="mt-1.5" />
                        </div>

                        {/* Payment method */}
                        <div className="mt-5">
                            <label className="mb-1.5 block text-sm font-medium text-neutral-800">Payment method</label>
                            <div className="grid gap-2.5 sm:grid-cols-2">
                                {PAYMENT_METHODS.map((method) => {
                                    const Icon = method.icon;
                                    const selected = data.payment_method === method.id;
                                    return (
                                        <button
                                            key={method.id}
                                            type="button"
                                            onClick={() => setData('payment_method', method.id)}
                                            className={`relative flex items-center gap-3 rounded-xl border px-3.5 py-3 text-left transition-colors duration-150 ${
                                                selected ? 'border-brand/50 bg-rose-50' : 'border-gray-300 bg-white hover:bg-neutral-50'
                                            }`}
                                        >
                                            <Icon className="h-9 w-9 shrink-0 rounded-md" />
                                            <span className="text-sm font-semibold text-neutral-900">{method.label}</span>
                                            {selected && <CheckCircleIcon className="absolute right-2.5 top-2.5 h-5 w-5" />}
                                        </button>
                                    );
                                })}
                            </div>
                            <InputError message={errors.payment_method} className="mt-1.5" />
                        </div>

                        <div className="mt-5">
                            <label className="flex items-start gap-2.5">
                                <input
                                    type="checkbox"
                                    checked={data.terms}
                                    onChange={(e) => setData('terms', e.target.checked)}
                                    className="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-brand focus:ring-brand/30"
                                    required
                                />
                                <span className="text-sm leading-relaxed text-neutral-600">
                                    I agree to the{' '}
                                    <a href={route('terms')} target="_blank" rel="noopener noreferrer" className="font-medium text-brand hover:underline">
                                        Terms of Service
                                    </a>{' '}
                                    and{' '}
                                    <a href={route('privacy')} target="_blank" rel="noopener noreferrer" className="font-medium text-brand hover:underline">
                                        Privacy Policy
                                    </a>
                                    .
                                </span>
                            </label>
                            <InputError message={errors.terms} className="mt-1.5" />
                        </div>

                        <button
                            type="submit"
                            disabled={
                                processing ||
                                !data.first_name ||
                                !data.last_name ||
                                !data.email ||
                                !data.password ||
                                !data.payment_method
                            }
                            className="mt-6 inline-flex w-full items-center justify-center rounded-md bg-brand px-6 py-3 text-base font-medium text-white transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? 'Creating account…' : isLifetime ? `Get Lifetime · ${plan.price}` : 'Start 7-Day Free Trial'}
                        </button>

                        {isTrial && (
                            <p className="mt-3 text-center text-xs text-neutral-400">
                                Cancel anytime.
                            </p>
                        )}
                    </form>

                    <p className="mt-5 text-center text-sm text-neutral-600">
                        Already have an account?{' '}
                        <Link href={route('login')} className="font-medium text-brand hover:underline">
                            Sign in
                        </Link>
                    </p>
                </div>
            </main>
        </div>
    );
}
