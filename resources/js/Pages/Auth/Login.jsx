import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import Eyebrow from '@/Components/Site/Eyebrow';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const fieldClass = (error) =>
    `block w-full rounded-lg border bg-white px-3.5 py-2.5 text-[15px] text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 ${
        error
            ? 'border-brand focus:border-brand focus:ring-brand/20'
            : 'border-gray-300 focus:border-neutral-900 focus:ring-neutral-900/10'
    }`;

export default function Login({ status, canResetPassword }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
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
            <Head title="Sign in" />

            <header className="mx-auto flex w-full max-w-7xl items-center px-6 py-6 lg:px-[100px]">
                <Link href="/" className="flex items-baseline text-[28px] leading-none tracking-tight">
                    <span className="font-sans font-bold text-black">Khord</span>
                    <span className="font-serif font-semibold text-brand">Quiz</span>
                </Link>
            </header>

            <main className="flex flex-1 items-center justify-center px-6 pb-16">
                <div className="w-full max-w-[440px] rounded-2xl border border-white bg-white/90 px-6 py-8 shadow-[0_18px_50px_-24px_rgba(17,24,39,0.35)] sm:px-8">
                    <Eyebrow>Account</Eyebrow>
                    <h1 className="mt-4 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">
                        Sign <span className="font-display font-medium tracking-normal text-brand">in</span>
                    </h1>
                    <p className="mt-2 text-[15px] leading-relaxed text-neutral-600">
                        Continue your ear training. Your streak is waiting.
                    </p>

                    {status && (
                        <div className="mt-5 rounded-lg border border-green-200 bg-green-50 px-3.5 py-2.5 text-sm font-medium text-green-700">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-6">
                        <div>
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
                                autoFocus
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
                                    className={`${fieldClass(errors.password)} pe-16`}
                                    autoComplete="current-password"
                                    placeholder="Your password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((visible) => !visible)}
                                    className="absolute inset-y-0 right-0 px-3.5 text-sm font-medium text-neutral-500 hover:text-neutral-900"
                                >
                                    {showPassword ? 'Hide' : 'Show'}
                                </button>
                            </div>
                            <InputError message={errors.password} className="mt-1.5" />
                        </div>

                        <div className="mt-4 flex items-center justify-between gap-3">
                            <label className="flex items-center">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    className="text-brand focus:ring-brand/30"
                                    onChange={(e) => setData('remember', e.target.checked)}
                                />
                                <span className="ms-2 text-sm text-neutral-600">Remember me</span>
                            </label>

                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-sm font-medium text-brand hover:underline"
                                >
                                    Forgot password?
                                </Link>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-6 inline-flex w-full items-center justify-center rounded-md bg-brand px-6 py-3 text-base font-medium text-white transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? 'Signing in…' : 'Sign in'}
                        </button>
                    </form>

                    <div className="mt-6 rounded-lg border border-dashed border-gray-300 bg-[#FBF6F3] px-4 py-3 text-sm text-neutral-600">
                        <p className="font-medium text-neutral-900">Demo account</p>
                        <p className="mt-1">
                            <span className="text-neutral-500">Email</span> demo@khordquiz.com
                        </p>
                        <p>
                            <span className="text-neutral-500">Password</span> password
                        </p>
                        <button
                            type="button"
                            className="mt-2 text-sm font-medium text-brand hover:underline"
                            onClick={() =>
                                setData({
                                    email: 'demo@khordquiz.com',
                                    password: 'password',
                                    remember: data.remember,
                                })
                            }
                        >
                            Fill demo credentials
                        </button>
                    </div>
                </div>
            </main>
        </div>
    );
}
