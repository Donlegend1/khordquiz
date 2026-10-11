import InputError from '@/Components/InputError';
import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

const fieldClass = 'w-full rounded-xl border border-neutral-200 px-3.5 py-3 text-[15px] text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10';

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

function PasswordField({ id, label, hint, value, onChange, error, inputRef, autoComplete }) {
    const [visible, setVisible] = useState(false);

    return (
        <div>
            <label htmlFor={id} className="mb-1.5 block text-sm font-semibold text-neutral-700">
                {label}
            </label>
            <div className="relative">
                <input
                    id={id}
                    ref={inputRef}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    onChange={onChange}
                    autoComplete={autoComplete}
                    className={`${fieldClass} pr-11`}
                />
                <button
                    type="button"
                    onClick={() => setVisible((v) => !v)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    className="absolute inset-y-0 right-0 flex items-center px-3.5 text-neutral-400 hover:text-neutral-700"
                >
                    <EyeIcon open={visible} className="h-[18px] w-[18px]" />
                </button>
            </div>
            {hint && !error && <p className="mt-1.5 text-xs text-neutral-400">{hint}</p>}
            <InputError message={error} className="mt-1.5" />
        </div>
    );
}

export default function ChangePassword() {
    const currentPasswordInput = useRef(null);
    const passwordInput = useRef(null);
    const { data, setData, put, errors, processing, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errs) => {
                if (errs.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }
                if (errs.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <AppShell eyebrow="Security" title="Change Password">
            <Head title="Change Password" />

            <form onSubmit={submit} className="mx-auto max-w-md">
                <div className="grid gap-5 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                    <PasswordField
                        id="current_password"
                        label="Current password"
                        value={data.current_password}
                        onChange={(e) => setData('current_password', e.target.value)}
                        error={errors.current_password}
                        inputRef={currentPasswordInput}
                        autoComplete="current-password"
                    />

                    <div className="border-t border-black/5" />

                    <PasswordField
                        id="password"
                        label="New password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={errors.password}
                        inputRef={passwordInput}
                        autoComplete="new-password"
                    />

                    <PasswordField
                        id="password_confirmation"
                        label="Confirm new password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        error={errors.password_confirmation}
                        autoComplete="new-password"
                    />
                </div>

                <div className="mt-6 flex items-center gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="flex-1 rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-60"
                    >
                        {processing ? 'Updating…' : 'Update Password'}
                    </button>
                    <Link href={route('account')} className="rounded-xl px-5 py-3.5 text-sm font-semibold text-neutral-600 hover:bg-neutral-50">
                        Cancel
                    </Link>
                </div>
            </form>
        </AppShell>
    );
}
