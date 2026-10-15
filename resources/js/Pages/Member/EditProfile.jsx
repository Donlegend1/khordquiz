import InputError from '@/Components/InputError';
import AppShell from '@/Layouts/AppShell';
import { COUNTRIES } from '@/lib/countries';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function ChevronDownIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 9l6 6 6-6" />
        </svg>
    );
}

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 13l4 4L19 7" />
        </svg>
    );
}

function SearchIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="11" cy="11" r="7" />
            <path d="M21 21l-4.3-4.3" />
        </svg>
    );
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

function CountryPicker({ value, onChange }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const ref = useRef(null);
    const matches = COUNTRIES.filter((c) => c.toLowerCase().includes(query.trim().toLowerCase()));

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (e) => {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className={`${fieldClass} flex items-center justify-between text-left`}
            >
                <span className={value ? 'text-neutral-900' : 'text-neutral-400'}>{value || 'Select your country'}</span>
                <ChevronDownIcon className="h-4 w-4 shrink-0 text-neutral-400" />
            </button>

            {open && (
                <div className="absolute left-0 right-0 top-full z-20 mt-1.5 overflow-hidden rounded-xl border border-black/5 bg-white shadow-lg">
                    <div className="flex items-center gap-2 border-b border-black/5 px-3.5 py-2.5">
                        <SearchIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <input
                            autoFocus
                            type="text"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Search countries"
                            className="w-full text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none"
                        />
                    </div>
                    <div className="max-h-60 overflow-y-auto py-1">
                        {matches.map((country) => (
                            <button
                                key={country}
                                type="button"
                                onClick={() => {
                                    onChange(country);
                                    setOpen(false);
                                    setQuery('');
                                }}
                                className="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm text-neutral-800 transition-colors duration-150 hover:bg-neutral-50"
                            >
                                {country}
                                {country === value && <CheckIcon className="h-4 w-4 text-brand" />}
                            </button>
                        ))}
                        {matches.length === 0 && <p className="px-3.5 py-3 text-sm text-neutral-400">No matches.</p>}
                    </div>
                </div>
            )}
        </div>
    );
}

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
    const { data, setData, patch, processing, errors } = useForm({
        first_name: first,
        last_name: last,
        email: user.email ?? '',
        country: user.country ?? '',
        skill_level: user.skill_level ?? 'Beginner',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('profile.update'));
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
                        <label className="mb-1.5 block text-sm font-semibold text-neutral-700">Country</label>
                        <CountryPicker value={data.country} onChange={(value) => setData('country', value)} />
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
                        Save Changes
                    </button>
                    <Link href={route('account')} className="rounded-xl px-5 py-3.5 text-sm font-semibold text-neutral-600 hover:bg-neutral-50">
                        Cancel
                    </Link>
                </div>
            </form>
        </AppShell>
    );
}
