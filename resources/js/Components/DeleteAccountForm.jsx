import InputError from '@/Components/InputError';
import { PLAN_INFO } from '@/lib/subscription';
import { Link, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

const CONFIRM_WORD = 'DELETE';
const REMOVED = ['Your profile and account details', 'Quiz progress, streaks and stats', 'Saved quizzes and challenges'];

const PAYMENT_PROVIDER_LABELS = { paypal: 'PayPal', stripe: 'Stripe' };

function TrashIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 7h14M9 7V5.5A1.5 1.5 0 0110.5 4h3A1.5 1.5 0 0115 5.5V7m-7 0v12a2 2 0 002 2h4a2 2 0 002-2V7" />
        </svg>
    );
}

function WarningIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M12 9v4m0 4h.01M10.3 3.9L2.5 17a1.8 1.8 0 001.5 2.7h16a1.8 1.8 0 001.5-2.7L13.7 3.9a1.8 1.8 0 00-3.4 0z" />
        </svg>
    );
}

function ChevronRightIcon(props) {
    return (
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2" {...props}>
            <path d="M7.5 5l5 5-5 5" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

// Mirrors the mobile app's DeleteAccountSheet (src/components/delete-account-sheet.tsx)
// visually — same icon, title, renewal warning, "permanently removes" list and
// Cancel/Delete layout — but as a full page (Member/DeleteAccount.jsx), not a
// pop-up modal, per explicit direction. The web also has a REAL
// account-deletion backend (ProfileController::destroy), so unlike mobile's
// demo-only "type DELETE" (no backend to send it to), this keeps the actual
// security check: the member's current password is still required, with
// "type DELETE" layered on top as the same extra friction step mobile shows,
// not a replacement for it.
export default function DeleteAccountForm({ sub, user }) {
    const passwordInput = useRef(null);
    const [typed, setTyped] = useState('');
    const confirmed = typed === CONFIRM_WORD;

    const { data, setData, delete: destroy, processing, errors } = useForm({ password: '' });

    const deleteAccount = (e) => {
        e.preventDefault();
        if (!confirmed) return;

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onError: () => passwordInput.current?.focus(),
        });
    };

    const plan = PLAN_INFO[sub.planId];
    const provider = PAYMENT_PROVIDER_LABELS[user.payment_method] ?? 'your payment provider';
    const warning =
        sub.planId !== 'lifetime' && sub.status === 'active'
            ? `Your ${plan.tier} · ${plan.label} plan renews on ${sub.renews}. Deleting your account won’t cancel it — cancel it in ${provider} first.`
            : null;

    return (
        <form onSubmit={deleteAccount} className="mx-auto max-w-sm space-y-5">
            <div className="flex flex-col items-center gap-2.5 text-center">
                <span className="flex h-13 w-13 items-center justify-center rounded-full bg-rose-50 text-brand" style={{ height: '52px', width: '52px' }}>
                    <TrashIcon className="h-6 w-6" />
                </span>
                <p className="text-xl font-extrabold text-neutral-900">Delete account?</p>
            </div>

            {warning && (
                <div className="space-y-2.5 rounded-2xl border border-amber-200 bg-amber-50 p-3.5">
                    <div className="flex items-start gap-2.5">
                        <WarningIcon className="mt-0.5 h-[18px] w-[18px] shrink-0 text-amber-700" />
                        <p className="flex-1 text-sm leading-relaxed text-neutral-900">{warning}</p>
                    </div>
                    <a
                        href={route('subscription')}
                        className="ml-7 inline-flex items-center gap-1 text-sm font-bold text-amber-700 hover:underline"
                    >
                        Manage Subscription
                        <ChevronRightIcon className="h-3.5 w-3.5" />
                    </a>
                </div>
            )}

            <div className="space-y-2.5">
                <p className="text-xs font-bold text-neutral-500">This permanently removes</p>
                {REMOVED.map((item) => (
                    <div key={item} className="flex items-center gap-3">
                        <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-neutral-400" />
                        <span className="text-sm text-neutral-900">{item}</span>
                    </div>
                ))}
            </div>

            <div className="space-y-1.5">
                <label htmlFor="delete-password" className="block text-sm font-medium text-neutral-800">
                    Password
                </label>
                <input
                    id="delete-password"
                    ref={passwordInput}
                    type="password"
                    autoComplete="current-password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    placeholder="Enter your password"
                    className="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-[15px] text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                />
                <InputError message={errors.password} />
            </div>

            <div className="space-y-2">
                <p className="text-sm font-semibold text-neutral-600">
                    Type <span className="font-extrabold text-neutral-900">{CONFIRM_WORD}</span> to confirm
                </p>
                <input
                    value={typed}
                    onChange={(e) => setTyped(e.target.value)}
                    placeholder={CONFIRM_WORD}
                    autoComplete="off"
                    autoCapitalize="characters"
                    spellCheck={false}
                    className={`block w-full rounded-xl border bg-white px-3.5 py-3 text-[15px] font-bold tracking-wide text-neutral-900 placeholder:font-normal placeholder:tracking-normal placeholder:text-neutral-400 focus:outline-none ${
                        confirmed ? 'border-brand ring-2 ring-brand/10' : 'border-gray-300'
                    }`}
                />
            </div>

            <div className="space-y-2.5 pt-1">
                <button
                    type="submit"
                    disabled={!confirmed || processing}
                    className={`flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-sm font-extrabold transition-opacity duration-150 ${
                        confirmed ? 'bg-brand text-white hover:opacity-90' : 'cursor-not-allowed bg-neutral-100 text-neutral-400'
                    }`}
                >
                    <TrashIcon className="h-[18px] w-[18px]" />
                    {processing ? 'Deleting…' : 'Delete Account'}
                </button>
                <Link
                    href={route('account')}
                    className="block w-full rounded-xl py-3 text-center text-sm font-bold text-neutral-900 transition-colors duration-150 hover:bg-neutral-100"
                >
                    Cancel
                </Link>
            </div>
        </form>
    );
}
