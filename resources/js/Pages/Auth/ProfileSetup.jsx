import InstrumentIcon, { FAMILY_INSTRUMENTS } from '@/Components/Quiz/InstrumentIcon';
import AvatarBadge from '@/Components/AvatarBadge';
import InputError from '@/Components/InputError';
import { AVATAR_OPTIONS, INSTRUMENT_GROUPS, INSTRUMENT_LABELS, LANGUAGES, SKILL_LEVELS } from '@/lib/profile-options';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTrans } from '@/lib/i18n';

const triggerClass = (error) =>
    `flex w-full items-center justify-between rounded-lg border bg-white px-3.5 py-2.5 text-left text-[15px] text-neutral-900 transition-colors duration-150 ${
        error ? 'border-brand' : 'border-gray-300 hover:border-neutral-400'
    }`;

function ChevronDownIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 9l6 6 6-6" />
        </svg>
    );
}

function ChevronBackIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M15 18l-6-6 6-6" />
        </svg>
    );
}

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M20 6L9 17l-5-5" />
        </svg>
    );
}

// Custom light-themed dropdown, replacing the native <select> — browsers render
// <optgroup>-based selects in a dark, OS-styled popover that clashed badly with
// this card's own light design. Pass `groups` (two-step drill-down: category
// names first, tap one to see its options, with a back row) for Default
// Instrument, or `options` for a flat list like Default Language — same split
// as Settings.jsx's PickerRow, just styled for this card instead of a row list.
function Dropdown({ label, error, value, onChange, options, groups, format = (option) => option }) {
    const t = useTrans();
    const [open, setOpen] = useState(false);
    const [activeGroup, setActiveGroup] = useState(null);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) setOpen(false);
        };
        const onKeyDown = (event) => {
            if (event.key === 'Escape') { setOpen(false); ref.current?.querySelector('button')?.focus(); }
        };
        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    const toggleOpen = () =>
        setOpen((current) => {
            const next = !current;
            if (next) setActiveGroup(null);
            return next;
        });

    const choose = (option) => {
        onChange(option);
        setOpen(false);
    };

    const group = groups?.find((item) => item.name === activeGroup);

    return (
        <div className="relative" ref={ref}>
            <label className="mb-1.5 block text-sm font-medium text-neutral-800">{label}</label>
            <button type="button" aria-label={label} aria-expanded={open} onClick={toggleOpen} className={triggerClass(error)}>
                <span className="flex items-center gap-2.5">{groups && <InstrumentIcon sound={value} size={24} />}{format(value)}</span>
                <ChevronDownIcon className="h-4 w-4 shrink-0 text-neutral-400" />
            </button>
            <InputError message={error} className="mt-1.5" />

            {open && (
                <div className="absolute bottom-full left-0 z-20 mb-2 max-h-[360px] w-full overflow-y-auto rounded-2xl border border-neutral-200 bg-white p-1.5 shadow-xl">
                    {groups ? (
                        group ? (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setActiveGroup(null)}
                                    className="flex w-full items-center min-h-11 gap-2 border-b border-black/5 px-2.5 py-2 text-left text-xs font-bold text-neutral-500 transition-colors duration-150 hover:bg-neutral-50"
                                >
                                    <ChevronBackIcon className="h-3 w-3" />
                                    <InstrumentIcon sound={FAMILY_INSTRUMENTS[group.name]} size={24} />
                                    {t(group.name)}
                                </button>
                                {group.options.map((option) => (
                                    <button
                                        key={option}
                                        type="button"
                                        onClick={() => choose(option)}
                                        aria-pressed={option === value}
                                        className={`flex min-h-12 w-full items-center justify-between rounded-xl px-2.5 py-2 text-sm transition-colors duration-150 hover:bg-neutral-50 ${
                                            option === value ? 'bg-brand/5 font-semibold text-brand' : 'text-neutral-700'
                                        }`}
                                    >
                                        <span className="flex items-center gap-3"><InstrumentIcon sound={option} size={28} />{format(option)}</span>
                                        {option === value && <CheckIcon className="h-3.5 w-3.5" />}
                                    </button>
                                ))}
                            </>
                        ) : (
                            groups.map((item) => (
                                <button
                                    key={item.name}
                                    type="button"
                                    onClick={() => setActiveGroup(item.name)}
                                    className="flex min-h-12 w-full items-center justify-between gap-2 rounded-xl px-2.5 py-2 text-left text-sm font-semibold text-neutral-700 transition-colors duration-150 hover:bg-neutral-50"
                                >
                                    <span className="flex items-center gap-2">
                                        <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-neutral-50"><InstrumentIcon sound={FAMILY_INSTRUMENTS[item.name]} size={26} /></span>
                                        {t(item.name)}
                                    </span>
                                    <span className="flex items-center gap-2">{item.options.includes(value) && <CheckIcon className="h-3.5 w-3.5 text-brand" />}<ChevronDownIcon className="h-3.5 w-3.5 -rotate-90 text-neutral-400" /></span>
                                </button>
                            ))
                        )
                    ) : (
                        options.map((option) => (
                            <button
                                key={option}
                                type="button"
                                onClick={() => choose(option)}
                                className={`flex w-full items-center justify-between px-3.5 py-2 text-left text-sm transition-colors duration-150 hover:bg-neutral-50 ${
                                    option === value ? 'font-semibold text-brand' : 'text-neutral-700'
                                }`}
                            >
                                {format(option)}
                                {option === value && <CheckIcon className="h-3.5 w-3.5" />}
                            </button>
                        ))
                    )}
                </div>
            )}
        </div>
    );
}

export default function ProfileSetup({ user }) {
    const t = useTrans();
    const { data, setData, post, processing, errors } = useForm({
        avatar: user.avatar ?? AVATAR_OPTIONS[0].id,
        skill_level: user.skill_level ?? 'Beginner',
        language: user.language ?? 'English',
        default_instrument: user.default_instrument ?? 'Piano',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('profile-setup.update'));
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
            <Head title={t('Set up your profile')} />

            <main className="flex flex-1 items-center justify-center px-6 py-14">
                <div className="w-full max-w-[560px] rounded-2xl border border-white bg-white/90 px-6 py-8 shadow-[0_18px_50px_-24px_rgba(17,24,39,0.35)] sm:px-8">
                    <Link href="/" aria-label={t('KhordQuiz home')} className="mx-auto mb-2 block w-fit">
                        <img src="/images/logo-mark.png" alt="KhordQuiz" width="287" height="192" className="block h-14 w-auto" />
                    </Link>
                    <h1 className="mt-4 text-center text-xl font-bold text-neutral-900">{t('Set up your profile')}</h1>
                    <p className="mt-2 text-center text-sm text-neutral-500">{t("Let's personalize your experience.")}</p>

                    <form onSubmit={submit} className="mt-6 space-y-6">
                        <div>
                            <label className="mb-2 block text-sm font-medium text-neutral-800">{t('Choose your avatar')}</label>
                            <div className="grid grid-cols-5 justify-items-center gap-x-2 gap-y-3">
                                {AVATAR_OPTIONS.map((option) => {
                                    const active = data.avatar === option.id;
                                    return (
                                        <button
                                            key={option.id}
                                            type="button"
                                            aria-label={t(option.label)}
                                            aria-pressed={active}
                                            onClick={() => setData('avatar', option.id)}
                                            className={`relative flex h-13 w-13 items-center justify-center rounded-full border-2 text-2xl transition-colors duration-150 ${
                                                active ? 'border-brand' : 'border-transparent hover:border-neutral-200'
                                            }`}
                                            style={{ backgroundColor: option.bg, height: '52px', width: '52px' }}
                                        >
                                            <AvatarBadge id={option.id} size={44} />
                                            {active && (
                                                <span className="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-brand">
                                                    <CheckIcon className="h-2.5 w-2.5 text-white" />
                                                </span>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>
                            <InputError message={errors.avatar} className="mt-1.5" />
                        </div>

                        <div>
                            <label className="mb-2 block text-sm font-medium text-neutral-800">{t('Skill level')}</label>
                            <div className="flex rounded-xl bg-neutral-100 p-1">
                                {SKILL_LEVELS.map((level) => {
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
                                            {t(level)}
                                        </button>
                                    );
                                })}
                            </div>
                            <InputError message={errors.skill_level} className="mt-1.5" />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Dropdown
                                label={t('Default language')}
                                error={errors.language}
                                value={data.language}
                                onChange={(language) => setData('language', language)}
                                options={LANGUAGES}
                            />

                            <Dropdown
                                label={t('Default instrument')}
                                error={errors.default_instrument}
                                value={data.default_instrument}
                                onChange={(instrument) => setData('default_instrument', instrument)}
                                groups={INSTRUMENT_GROUPS}
                                format={(option) => t(INSTRUMENT_LABELS[option] ?? option)}
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex w-full items-center justify-center rounded-md bg-brand px-6 py-3 text-base font-medium text-white transition hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? t('Saving…') : t('Setup')}
                        </button>
                    </form>
                </div>
            </main>
        </div>
    );
}
