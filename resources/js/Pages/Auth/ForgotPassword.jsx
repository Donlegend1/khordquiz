import InputError from '@/Components/InputError';
import { Head, Link, useForm } from '@inertiajs/react';

const fieldClass = (error) =>
    `block w-full rounded-lg border bg-white px-3.5 py-2.5 text-[15px] text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 ${
        error
            ? 'border-brand focus:border-brand focus:ring-brand/20'
            : 'border-gray-300 focus:border-neutral-900 focus:ring-neutral-900/10'
    }`;

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
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
            <Head title="Forgot Password" />

            <main className="flex flex-1 items-center justify-center px-6 py-16">
                <div className="w-full max-w-[520px] rounded-2xl border border-white bg-white/90 px-6 py-8 shadow-[0_18px_50px_-24px_rgba(17,24,39,0.35)] sm:px-8">
                    <Link href="/" aria-label="KhordQuiz home" className="mx-auto mb-2 block w-fit">
                        <img src="/images/logo-mark.png" alt="KhordQuiz" width="287" height="192" className="block h-14 w-auto" />
                    </Link>
                    <h1 className="mt-4 text-center text-xl font-bold text-neutral-900">Forgot your password?</h1>
                    <p className="mt-2 text-center text-sm text-neutral-500">
                        Enter the email associated with your account and we&apos;ll send you a link to reset your password.
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

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-6 inline-flex w-full items-center justify-center rounded-md bg-brand px-6 py-3 text-base font-medium text-white transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? 'Sending…' : 'Email Password Reset Link'}
                        </button>
                    </form>

                    <p className="mt-5 text-center text-sm text-neutral-600">
                        Remembered it after all?{' '}
                        <Link href={route('login')} className="font-medium text-brand hover:underline">
                            Back to sign in
                        </Link>
                    </p>
                </div>
            </main>
        </div>
    );
}
