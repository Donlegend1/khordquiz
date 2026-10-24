import AppShell from '@/Layouts/AppShell';
import { INSTRUMENT_GROUPS, INSTRUMENT_LABELS, LANGUAGES } from '@/lib/profile-options';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

// Most rows here mirror the mobile app's own Settings screen (see app/settings.tsx
// there), where appearance, language, instrument, sound, haptics and notification
// preferences live only on-device (AsyncStorage), never on the server — so keeping
// them in local React state here, reset on reload, is faithful to the app, not a
// shortcut. Daily goal is the one row backed by a real column (users.daily_goal),
// so it reuses the same endpoint as the Progress page's goal picker.
const APPEARANCES = ['System', 'Light', 'Dark'];
const NOTATIONS = ['Solfège', 'Numbers'];
const NOTIFICATION_DELIVERIES = ['Push', 'Email', 'Off'];

// Reminder time deliberately diverges from the app's fixed 5-slot picker —
// the member asked for any time, not just the app's preset list. Built as a
// custom brand-styled picker rather than the native <input type="time">,
// whose browser-default UI (blue accent, generic popup) didn't match the
// rest of the app.
const TIME_HOURS = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0'));
const TIME_MINUTES = Array.from({ length: 12 }, (_, i) => String(i * 5).padStart(2, '0'));
const TIME_PERIODS = ['AM', 'PM'];

function parseTime12(value) {
    const match = /^(\d{1,2}):(\d{2})\s*(AM|PM)$/i.exec((value ?? '').trim());
    if (!match) return { hour: '07', minute: '00', period: 'PM' };
    return { hour: match[1].padStart(2, '0'), minute: match[2], period: match[3].toUpperCase() };
}

function formatTime12({ hour, minute, period }) {
    return `${parseInt(hour, 10)}:${minute} ${period}`;
}

function icon(paths, viewBox = '0 0 24 24') {
    return function Icon(props) {
        return (
            <svg viewBox={viewBox} fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
                {paths}
            </svg>
        );
    };
}

const ContrastIcon = icon(
    <>
        <circle cx="12" cy="12" r="8.5" />
        <path d="M12 3.5a8.5 8.5 0 010 17z" fill="currentColor" stroke="none" />
    </>,
);
const LanguageIcon = icon(
    <>
        <path d="M4 6h9M8.5 4v2.5M6 6.5c.5 3 2.3 5.3 5 6.8M11 6.5c-.7 3.3-3 6-6.5 7.5" />
        <path d="M14 20l3.5-8 3.5 8M14.9 17.5h5.2" />
    </>,
);
const FlagIcon = icon(
    <>
        <path d="M6 3.5v17" />
        <path d="M6 4.5h10l-2 3 2 3H6z" />
    </>,
);
const NoteIcon = icon(<path d="M9 18V9l9-2v9M9 18a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 019 18zM18 16a2.5 2.5 0 11-2.5-2.5A2.5 2.5 0 0118 16z" />);
const NotationIcon = icon(<path d="M4 17a2 2 0 104 0V6l12-2v10M4 17V8m8 9a2 2 0 104 0" />);
const VolumeIcon = icon(
    <>
        <path d="M4 9.5v5h3.5L13 19V5L7.5 9.5z" />
        <path d="M16.5 9a4 4 0 010 6M19 7a7 7 0 010 10" />
    </>,
);
const PhoneIcon = icon(
    <>
        <rect x="6.5" y="3" width="11" height="18" rx="2" />
        <path d="M11 18h2" />
    </>,
);
const BellIcon = icon(
    <>
        <path d="M6 9a6 6 0 1112 0c0 4.2 1.3 5.6 1.5 6H4.5c.2-.4 1.5-1.8 1.5-6z" />
        <path d="M10.3 19a1.8 1.8 0 003.4 0" />
    </>,
);
const ClockIcon = icon(
    <>
        <circle cx="12" cy="12" r="8.5" />
        <path d="M12 7.5V12l3 2" />
    </>,
);
const FlameIcon = icon(<path d="M12 2.5c1 3 .5 4.5-1 6-2 2-3 3.8-3 6a4 4 0 008 0c0-1.3-.5-2-1-2.8.9.4 2 1.6 2 3.8a5 5 0 01-10 0c0-4.2 3-6 4-8.2.6-1.3.7-2.8 1-4.8z" />);
const BellCircleIcon = icon(
    <>
        <circle cx="12" cy="12" r="8.5" />
        <path d="M9.5 10a2.5 2.5 0 015 0c0 1.8.6 2.3.7 2.6h-6.4c.1-.3.7-.8.7-2.6z" />
        <path d="M11 14.8a1 1 0 002 0" />
    </>,
);
const RefreshIcon = icon(
    <>
        <path d="M4 12a8 8 0 0113.7-5.7L20 8M20 4v4h-4" />
        <path d="M20 12a8 8 0 01-13.7 5.7L4 16M4 20v-4h4" />
    </>,
);
const LifeBuoyIcon = icon(
    <>
        <circle cx="12" cy="12" r="8.5" />
        <circle cx="12" cy="12" r="3.5" />
        <path d="M5.5 5.5l3.2 3.2M18.5 5.5l-3.2 3.2M5.5 18.5l3.2-3.2M18.5 18.5l-3.2-3.2" />
    </>,
);
const InfoIcon = icon(
    <>
        <circle cx="12" cy="12" r="8.5" />
        <path d="M12 11v5.5M12 8v.01" />
    </>,
);
const ChevronIcon = icon(<path d="M9 5l7 7-7 7" />);

function Section({ title, children }) {
    return (
        <div className="mb-6">
            <p className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-neutral-400">{title}</p>
            {/* No overflow-hidden here — PickerRow's dropdown needs to overflow
                below the card, and clipping it was cutting off "Light"/"Dark".
                Rows have no background of their own (only a hover highlight),
                so skipping the corner-clip costs only a faint square corner on
                a hovered first/last row — worth it to keep the dropdown visible. */}
            <div className="rounded-2xl border border-black/5 bg-white">{children}</div>
        </div>
    );
}

function RowShell({ first, disabled, onClick, href, icon: Icon, label, hint, destructive, children }) {
    const className = `flex w-full items-center gap-3 px-4 py-3.5 text-left ${first ? '' : 'border-t border-black/5'} ${
        (onClick || href) && !disabled ? 'transition-colors duration-150 hover:bg-neutral-50' : ''
    } ${disabled ? 'opacity-40' : ''}`;
    const content = (
        <>
            <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${destructive ? 'bg-rose-50 text-brand' : 'bg-neutral-100 text-neutral-600'}`}>
                <Icon className="h-4 w-4" />
            </span>
            <span className="min-w-0 flex-1">
                <span className={`block text-sm font-semibold ${destructive ? 'text-brand' : 'text-neutral-900'}`}>{label}</span>
                {hint && <span className="block text-xs text-neutral-400">{hint}</span>}
            </span>
            {children}
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {content}
            </Link>
        );
    }

    const Tag = onClick ? 'button' : 'div';
    return (
        <Tag type={onClick ? 'button' : undefined} disabled={disabled} onClick={onClick} className={className}>
            {content}
        </Tag>
    );
}

function Toggle({ value, onChange, disabled }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={value}
            disabled={disabled}
            onClick={(e) => {
                e.stopPropagation();
                onChange(!value);
            }}
            className={`inline-flex h-7 w-12 shrink-0 items-center rounded-full p-1 transition-colors duration-150 ${value ? 'bg-brand' : 'bg-neutral-200'} ${disabled ? 'cursor-not-allowed' : ''}`}
        >
            <span className={`h-5 w-5 rounded-full bg-white shadow transition-transform duration-150 ${value ? 'translate-x-5' : 'translate-x-0'}`} />
        </button>
    );
}

// Row height and the column's vertical padding must agree: padding equals
// half the viewport height minus half a row, so the first and last options
// can still scroll all the way to the centered selection band.
const TIME_ROW_HEIGHT = 36;
const TIME_VIEWPORT_HEIGHT = 180;
const TIME_COLUMN_PADDING = (TIME_VIEWPORT_HEIGHT - TIME_ROW_HEIGHT) / 2;

function TimeColumn({ label, options, value, onChange }) {
    const selectedRef = useRef(null);

    useEffect(() => {
        selectedRef.current?.scrollIntoView({ block: 'center' });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <div className="flex flex-1 flex-col">
            <p className="border-b border-black/5 bg-neutral-50/80 py-2 text-center text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                {label}
            </p>
            <div className="relative" style={{ height: TIME_VIEWPORT_HEIGHT }}>
                <div
                    className="pointer-events-none absolute inset-x-1.5 top-1/2 -translate-y-1/2 rounded-lg bg-brand/[0.08] ring-1 ring-inset ring-brand/20"
                    style={{ height: TIME_ROW_HEIGHT }}
                />
                <div
                    className="h-full snap-y snap-mandatory overflow-y-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    style={{
                        paddingBlock: TIME_COLUMN_PADDING,
                        maskImage: 'linear-gradient(to bottom, transparent, black 25%, black 75%, transparent)',
                        WebkitMaskImage: 'linear-gradient(to bottom, transparent, black 25%, black 75%, transparent)',
                    }}
                >
                    {options.map((option) => (
                        <button
                            key={option}
                            ref={option === value ? selectedRef : null}
                            type="button"
                            onClick={(event) => {
                                onChange(option);
                                event.currentTarget.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            }}
                            className={`flex w-full snap-center items-center justify-center text-sm font-bold tabular-nums transition-colors duration-150 ${
                                option === value ? 'text-brand' : 'text-neutral-400 hover:text-neutral-600'
                            }`}
                            style={{ height: TIME_ROW_HEIGHT }}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

function TimeRow({ icon: Icon, label, value, onChange, disabled }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    const parsed = parseTime12(value);

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    function update(part, partValue) {
        onChange(formatTime12({ ...parsed, [part]: partValue }));
    }

    return (
        <div className="relative" ref={ref}>
            <RowShell icon={Icon} label={label} disabled={disabled} onClick={() => !disabled && setOpen((v) => !v)}>
                <span className="shrink-0 text-sm text-neutral-500">{value}</span>
                <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
            </RowShell>

            {open && (
                <div className="absolute right-4 top-full z-20 mt-1.5 flex w-60 divide-x divide-black/5 overflow-hidden rounded-2xl border border-black/5 bg-white shadow-xl">
                    <TimeColumn label="Hour" options={TIME_HOURS} value={parsed.hour} onChange={(v) => update('hour', v)} />
                    <TimeColumn label="Min" options={TIME_MINUTES} value={parsed.minute} onChange={(v) => update('minute', v)} />
                    <TimeColumn label="AM/PM" options={TIME_PERIODS} value={parsed.period} onChange={(v) => update('period', v)} />
                </div>
            )}
        </div>
    );
}

// `groups` (e.g. Default Instrument's Piano/Guitar/Strings/... families) renders a
// labeled section per group instead of one flat list — `options` stays the plain
// flat dropdown every other PickerRow (Appearance, Language, Notation) still uses.
function PickerRow({ first, icon: Icon, label, hint, options, groups, value, onChange, disabled, formatOption = (option) => option }) {
    const [open, setOpen] = useState(false);
    // Two-step drill-down: the dropdown always opens on just the 6 category names
    // (no instruments shown yet) — tapping one drills into that category's
    // instrument list, with a back row to return to the category list.
    const [activeGroup, setActiveGroup] = useState(null);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    function toggleOpen() {
        if (disabled) return;
        setOpen((current) => {
            const next = !current;
            if (next) setActiveGroup(null);
            return next;
        });
    }

    function choose(option) {
        onChange(option);
        setOpen(false);
    }

    function OptionButton({ option }) {
        return (
            <button
                type="button"
                onClick={() => choose(option)}
                className={`flex w-full items-center justify-between px-3.5 py-2 text-sm transition-colors duration-150 hover:bg-neutral-50 ${
                    option === value ? 'font-semibold text-brand' : 'text-neutral-700'
                }`}
            >
                {formatOption(option)}
                {option === value && <span>✓</span>}
            </button>
        );
    }

    return (
        <div className="relative" ref={ref}>
            <RowShell first={first} icon={Icon} label={label} hint={hint} disabled={disabled} onClick={toggleOpen}>
                <span className="shrink-0 text-sm text-neutral-500">{formatOption(value)}</span>
                <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
            </RowShell>

            {open && (
                <div className="absolute right-4 top-full z-20 mt-1 max-h-80 w-56 overflow-y-auto overflow-x-hidden rounded-xl border border-black/5 bg-white py-1 shadow-lg">
                    {groups ? (
                        activeGroup ? (
                            <>
                                <button
                                    type="button"
                                    onClick={() => setActiveGroup(null)}
                                    className="flex w-full items-center gap-1 border-b border-black/5 px-3.5 py-2 text-left text-xs font-bold text-neutral-500 transition-colors duration-150 hover:bg-neutral-50"
                                >
                                    <ChevronIcon className="h-3 w-3 shrink-0 rotate-180 text-neutral-400" />
                                    {groups.find((group) => group.name === activeGroup)?.icon && (
                                        <span className="text-sm leading-none">{groups.find((group) => group.name === activeGroup).icon}</span>
                                    )}
                                    {activeGroup}
                                </button>
                                {groups.find((group) => group.name === activeGroup).options.map((option) => (
                                    <OptionButton key={option} option={option} />
                                ))}
                            </>
                        ) : (
                            groups.map((group) => (
                                <button
                                    key={group.name}
                                    type="button"
                                    onClick={() => setActiveGroup(group.name)}
                                    className="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm text-neutral-700 transition-colors duration-150 hover:bg-neutral-50"
                                >
                                    <span className="flex min-w-0 items-center gap-2">
                                        {group.icon && <span className="shrink-0 text-base leading-none">{group.icon}</span>}
                                        <span className="truncate">{group.name}</span>
                                    </span>
                                    <span className="flex shrink-0 items-center gap-1.5">
                                        {group.options.includes(value) && (
                                            <span className="text-xs font-semibold text-brand">{formatOption(value)}</span>
                                        )}
                                        <ChevronIcon className="h-3.5 w-3.5 shrink-0 text-neutral-300" />
                                    </span>
                                </button>
                            ))
                        )
                    ) : (
                        options.map((option) => <OptionButton key={option} option={option} />)
                    )}
                </div>
            )}
        </div>
    );
}

function DailyGoalRow({ current, options }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    const { data, setData, patch, processing } = useForm({ daily_goal: current });

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (event) => {
            if (ref.current && !ref.current.contains(event.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    const choose = (value) => {
        setData('daily_goal', value);
        patch(route('progress.goal'), { preserveScroll: true, onSuccess: () => setOpen(false) });
    };

    return (
        <div className="relative" ref={ref}>
            <RowShell first icon={FlagIcon} label="Daily goal" onClick={() => setOpen((v) => !v)}>
                <span className="shrink-0 text-sm text-neutral-500">{data.daily_goal} questions</span>
                <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
            </RowShell>

            {open && (
                <div className="absolute right-4 top-full z-20 mt-1 w-44 overflow-hidden rounded-xl border border-black/5 bg-white py-1 shadow-lg">
                    {options.map((value) => (
                        <button
                            key={value}
                            type="button"
                            disabled={processing}
                            onClick={() => choose(value)}
                            className={`flex w-full items-center justify-between px-3.5 py-2 text-sm transition-colors duration-150 hover:bg-neutral-50 ${
                                value === data.daily_goal ? 'font-semibold text-brand' : 'text-neutral-700'
                            }`}
                        >
                            {value} questions
                            {value === data.daily_goal && <span>✓</span>}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

function ResetProgressSheet({ open, onClose }) {
    const { delete: destroy, processing } = useForm();

    if (!open) return null;

    const confirm = () => {
        destroy(route('settings.reset-progress'), { onSuccess: onClose, preserveScroll: true });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/40 p-4" onClick={onClose}>
            <div className="w-full max-w-sm rounded-2xl bg-white p-6" onClick={(e) => e.stopPropagation()}>
                <div className="flex flex-col items-center text-center">
                    <span className="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-brand">
                        <RefreshIcon className="h-5 w-5" />
                    </span>
                    <p className="mt-3 text-base font-bold text-neutral-900">Reset progress?</p>
                    <p className="mt-1 text-sm text-neutral-500">This can&apos;t be undone.</p>
                </div>

                <ul className="mt-5 space-y-2.5 text-sm">
                    <li className="flex items-start gap-2 text-brand">
                        <span>✕</span>
                        <span className="text-neutral-700">Completed quizzes, scores and streaks</span>
                    </li>
                    <li className="flex items-start gap-2 text-brand">
                        <span>✕</span>
                        <span className="text-neutral-700">Your list of mistakes to review</span>
                    </li>
                    <li className="flex items-start gap-2 text-emerald-600">
                        <span>✓</span>
                        <span className="text-neutral-700">Your settings, account and plan stay the same</span>
                    </li>
                </ul>

                <div className="mt-6 space-y-2">
                    <button
                        type="button"
                        disabled={processing}
                        onClick={confirm}
                        className="w-full rounded-xl bg-brand py-3 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-60"
                    >
                        Reset Progress
                    </button>
                    <button type="button" onClick={onClose} className="w-full rounded-xl py-3 text-sm font-semibold text-neutral-600 hover:bg-neutral-50">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Settings({ dailyGoal, goalOptions, defaultInstrument }) {
    const [appearance, setAppearance] = useState('System');
    const [language, setLanguage] = useState('English');
    const [instrument, setInstrument] = useState(defaultInstrument ?? 'Piano');

    // Persisted immediately on pick, same as the Daily goal row — this is what
    // the quiz-preview page's sound picker reads back to decide which category
    // (and which specific instrument within it) to pre-select.
    function chooseInstrument(option) {
        setInstrument(option);
        router.patch(route('settings.default-instrument'), { default_instrument: option }, { preserveScroll: true, preserveState: true });
    }
    const [notation, setNotation] = useState('Solfège');
    const [soundEffects, setSoundEffects] = useState(true);
    const [haptics, setHaptics] = useState(true);
    const [reminders, setReminders] = useState(true);
    const [reminderTime, setReminderTime] = useState('7:00 PM');
    const [streakAlerts, setStreakAlerts] = useState(true);
    const [notifyBy, setNotifyBy] = useState('Push');
    const [resetOpen, setResetOpen] = useState(false);

    return (
        <AppShell eyebrow="Member" title="Settings" intro="Preferences for how KhordQuiz looks and sounds.">
            <Head title="Settings" />

            <div className="mx-auto max-w-2xl">
                <Section title="General">
                    <PickerRow first icon={ContrastIcon} label="Appearance" hint="System follows your phone's setting" options={APPEARANCES} value={appearance} onChange={setAppearance} />
                    <PickerRow icon={LanguageIcon} label="Language" options={LANGUAGES} value={language} onChange={setLanguage} />
                </Section>

                <Section title="Practice">
                    <DailyGoalRow current={dailyGoal} options={goalOptions} />
                    <PickerRow
                        icon={NoteIcon}
                        label="Default instrument"
                        hint="Used for new quizzes"
                        groups={INSTRUMENT_GROUPS}
                        value={instrument}
                        onChange={chooseInstrument}
                        formatOption={(option) => INSTRUMENT_LABELS[option] ?? option}
                    />
                    <PickerRow icon={NotationIcon} label="Notation" hint="For quizzes with solfège answers" options={NOTATIONS} value={notation} onChange={setNotation} />
                    <RowShell icon={VolumeIcon} label="Sound effects">
                        <Toggle value={soundEffects} onChange={setSoundEffects} />
                    </RowShell>
                    <RowShell icon={PhoneIcon} label="Haptic feedback">
                        <Toggle value={haptics} onChange={setHaptics} />
                    </RowShell>
                </Section>

                <Section title="Notifications">
                    <RowShell first icon={BellIcon} label="Practice reminders" hint="A daily nudge to keep practising">
                        <Toggle value={reminders} onChange={setReminders} />
                    </RowShell>
                    <TimeRow icon={ClockIcon} label="Reminder time" value={reminderTime} onChange={setReminderTime} disabled={!reminders} />
                    <RowShell icon={FlameIcon} label="Streak alerts" hint="Before your streak runs out">
                        <Toggle value={streakAlerts} onChange={setStreakAlerts} />
                    </RowShell>
                    <PickerRow icon={BellCircleIcon} label="Notify me by" hint="Perfect scores, challenges and updates" options={NOTIFICATION_DELIVERIES} value={notifyBy} onChange={setNotifyBy} />
                </Section>

                <Section title="Data">
                    <RowShell first destructive icon={RefreshIcon} label="Reset progress" onClick={() => setResetOpen(true)} />
                </Section>

                <Section title="About">
                    <RowShell first href={route('support')} icon={LifeBuoyIcon} label="Help & Support">
                        <ChevronIcon className="h-4 w-4 shrink-0 text-neutral-300" />
                    </RowShell>
                    <RowShell icon={InfoIcon} label="Version">
                        <span className="text-sm text-neutral-500">1.0.0</span>
                    </RowShell>
                </Section>
            </div>

            <ResetProgressSheet open={resetOpen} onClose={() => setResetOpen(false)} />
        </AppShell>
    );
}
