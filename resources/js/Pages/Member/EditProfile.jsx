import CountryPicker from '@/Components/CountryPicker';
import InputError from '@/Components/InputError';
import AppShell from '@/Layouts/AppShell';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function LockIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="5" y="10.5" width="14" height="9" rx="2" />
            <path d="M8 10.5V7a4 4 0 118 0v3.5" />
        </svg>
    );
}

const fieldClass = 'w-full rounded-xl border border-neutral-200 px-3.5 py-3 text-[15px] text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10';

// The users table has one `name` column, so an existing name is split on its
// first space into first/last for these two fields — the same shape the
// signup form already collects, just combined back into `name` on save.
function splitName(name) {
    const [first, ...rest] = (name ?? '').trim().split(/\s+/).filter(Boolean);
    return { first: first ?? '', last: rest.join(' ') };
}

export default function EditProfile() {
    const { auth } = usePage().props;
    const user = auth.user;
    const { first, last } = splitName(user.name);
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        first_name: first,
        last_name: last,
        email: user.email ?? '',
        country: user.country ?? '',
        skill_level: user.skill_level ?? 'Beginner',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('profile.update'), { preserveScroll: true });
    };

    return (
        <AppShell eyebrow="Member" title="Edit Profile" intro="Update your name, email, country and skill level.">
            <Head title="Edit Profile" />

            <form onSubmit={submit} className="mx-auto max-w-xl space-y-6">
                <div className="flex flex-col items-center gap-2 py-2">
                    <span className="flex h-20 w-20 items-center justify-center rounded-full bg-brand/10 text-2xl font-bold text-brand ring-4 ring-white">
                        {initials(`${data.first_name} ${data.last_name}`.trim() || user.name)}
                    </span>
                </div>

                <div className="grid gap-5 rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label className="mb-1.5 block text-sm font-semibold text-neutral-700">First name</label>
                            <input
                                type="text"
                                value={data.first_name}
                                onChange={(e) => setData('first_name', e.target.value)}
                                className={fieldClass}
                            />
                            <InputError message={errors.first_name} className="mt-1.5" />
                        </div>
                        <div>
                            <label className="mb-1.5 block text-sm font-semibold text-neutral-700">Last name</label>
                            <input
                                type="text"
                                value={data.last_name}
                                onChange={(e) => setData('last_name', e.target.value)}
                                className={fieldClass}
                            />
                            <InputError message={errors.last_name} className="mt-1.5" />
                        </div>
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-semibold text-neutral-700">Email</label>
                        <div className={`${fieldClass} flex items-center gap-2.5 bg-neutral-50 text-neutral-500`}>
                            <LockIcon className="h-3.5 w-3.5 shrink-0 text-neutral-400" />
                            <span className="truncate">{data.email}</span>
                        </div>
                        <p className="mt-1.5 text-xs text-neutral-400">Contact support to change the email on your account.</p>
                        <InputError message={errors.email} className="mt-1.5" />
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-semibold text-neutral-700">Country / Region</label>
                        <CountryPicker value={data.country} onChange={(value) => setData('country', value)} triggerClassName={fieldClass} />
                        <InputError message={errors.country} className="mt-1.5" />
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-semibold text-neutral-700">Skill level</label>
                        <div className="flex rounded-xl bg-neutral-100 p-1">
                            {['Beginner', 'Intermediate', 'Advanced'].map((level) => {
                                const active = level === data.skill_level;
                                return (
                                    <button
                                        key={level}
                                        type="button"
                                        onClick={() => setData('skill_level', level)}
                                        className={`flex-1 rounded-lg py-2 text-sm font-semibold transition-colors duration-150 ${
                                            active ? 'bg-brand text-white shadow-sm' : 'text-neutral-500 hover:text-neutral-700'
                                        }`}
                                    >
                                        {level}
                                    </button>
                                );
                            })}
                        </div>
                        <InputError message={errors.skill_level} className="mt-1.5" />
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="flex-1 rounded-xl bg-brand py-3.5 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-60"
                    >
                        {processing ? 'Saving…' : 'Save Changes'}
                    </button>
                    <Link href={route('account')} className="rounded-xl px-5 py-3.5 text-sm font-semibold text-neutral-600 hover:bg-neutral-50">
                        Cancel
                    </Link>
                </div>
                {recentlySuccessful && (
                    <p className="text-center text-sm font-semibold text-emerald-700" role="status">
                        Profile saved.
                    </p>
                )}
            </form>
        </AppShell>
    );
}
