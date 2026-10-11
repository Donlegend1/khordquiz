import InputError from '@/Components/InputError';
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

export default function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
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

            <main className="flex flex-1 items-center justify-center px-6 py-16">
                <div className="w-full max-w-[520px] rounded-2xl border border-white bg-white/90 px-6 py-8 shadow-[0_18px_50px_-24px_rgba(17,24,39,0.35)] sm:px-8">
                    <Link href="/" aria-label="KhordQuiz home" className="mx-auto mb-2 block w-fit">
                        <img src="/images/logo-mark.png" alt="KhordQuiz" width="287" height="192" className="block h-14 w-auto" />
                    </Link>
                    <h1 className="mt-4 text-center text-xl font-bold text-neutral-900">Create your account</h1>
                    <p className="mt-2 text-center text-sm text-neutral-500">Start training your ear today.</p>

                    <form onSubmit={submit} className="mt-6">
                        <div>
                            <label htmlFor="name" className="mb-1.5 block text-sm font-medium text-neutral-800">
                                Name
                            </label>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                value={data.name}
                                className={fieldClass(errors.name)}
                                autoComplete="name"
                                autoFocus
                                placeholder="Your name"
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} className="mt-1.5" />
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

                        <div className="mt-4">
                            <label htmlFor="password_confirmation" className="mb-1.5 block text-sm font-medium text-neutral-800">
                                Confirm password
                            </label>
                            <input
                                id="password_confirmation"
                                type={showPassword ? 'text' : 'password'}
                                name="password_confirmation"
                                value={data.password_confirmation}
                                className={fieldClass(errors.password_confirmation)}
                                autoComplete="new-password"
                                placeholder="Confirm password"
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                required
                            />
                            <InputError message={errors.password_confirmation} className="mt-1.5" />
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
                            disabled={processing}
                            className="mt-6 inline-flex w-full items-center justify-center rounded-md bg-brand px-6 py-3 text-base font-medium text-white transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? 'Creating account…' : 'Create Account'}
                        </button>
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
