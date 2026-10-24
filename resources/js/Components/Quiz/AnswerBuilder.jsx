import { Fragment, useEffect, useState } from 'react';
import {
    KEYBOARD_BLACK_KEYS,
    KEYBOARD_WHITE_KEYS,
    MELODIC_DICTATION_LENGTHS,
    NAMING_DEGREES,
    NAMING_EXCLUDES,
    NAMING_QUALITIES,
    blackKeyLeft,
    blackKeyWidth,
    buildNamedChord,
    compoundFor,
    composeProgression,
    decodeNamingAnswer,
    encodeNamingAnswer,
    multiFor,
    progressionFor,
} from '@/lib/quizAnswerPresets';

function UndoIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 14 4 9l5-5" />
            <path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11" />
        </svg>
    );
}

function TrashIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M4 7h16" />
            <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
            <path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13" />
            <path d="M10 11v6" />
            <path d="M14 11v6" />
        </svg>
    );
}

// Which of the real app's several answer-input designs (app/practice.tsx) a
// quiz uses — the same detection the Audio Quiz admin editor runs when
// authoring a question, reused here so the member sees the matching input.
export function answerTypeFor(category, title) {
    if (MELODIC_DICTATION_LENGTHS[title]) return 'sequence';
    if (compoundFor(category, title)) return 'compound';
    if (multiFor(category, title)) return 'multi';
    if (title === 'Chord naming') return 'naming';
    if (progressionFor(category, title)) return 'progression';
    if (title === 'Find the Note' || title === 'Find the Key #1' || title === 'Find the Key #2') return 'keyboard';
    return 'flat';
}

const ANSWER_LABELS = {
    flat: 'Choose the correct answer',
    compound: 'Choose the quality and inversion you hear',
    multi: 'Select every extension you hear — more than one can be correct',
    naming: 'Build the chord name you hear',
    progression: 'Build the progression you hear, chord by chord',
    sequence: 'Tap the notes in order to build the melody you hear',
    keyboard: 'Choose the correct note',
};

export function answerLabelFor(category, title) {
    return ANSWER_LABELS[answerTypeFor(category, title)];
}

// The fixed chord quality an extensions quiz always plays over (its title
// says which — "Major/Minor/Dominant Chord Extentions") — shown as its own
// pill above the options, matching the app's practice.tsx baseChord display.
// Dominant is inherently a 7th chord (its "7" isn't an optional extension the
// way Major/Minor's is), so its base includes the 7 already — matching the
// app's own 'Dominant 7' baseChord value, not just 'Dominant'.
export function baseChordFor(category, title) {
    if (answerTypeFor(category, title) !== 'multi') return null;
    const quality = title.split(' ')[0];
    return quality === 'Dominant' ? 'Dominant 7' : quality;
}

const EXTENSION_ORDER = ['6', '7', '♭9', '9', '♯9', '11', '♯11', '♭13', '13'];

// Ported 1:1 from the app's practice.tsx buildChordName() — builds the live
// chord name as extensions are picked (e.g. "Major 7(♭9)"), so the preview
// pill above the options matches exactly what the app shows. A picked '6'
// (without a 7th) is its own chord family, not a 7th-chord extension — it
// becomes "Major 6" or "Major 6/9" rather than going through the 7/9/11/13
// numbering below.
export function buildChordName(base, picks) {
    const ordered = EXTENSION_ORDER.filter((extension) => picks.includes(extension));
    const [quality, baseNumber] = base.split(' ');
    const hasSeventh = Boolean(baseNumber) || ordered.includes('7');
    const hasSixth = ordered.includes('6') && !hasSeventh;
    const extensions = ordered.filter((extension) => extension !== '7' && extension !== '6');
    if (hasSixth) {
        const hasNinth = extensions.includes('9');
        const rest = extensions.filter((extension) => extension !== '9');
        const core = hasNinth ? '6/9' : '6';
        return `${quality} ${core}${rest.length ? `(${rest.join(', ')})` : ''}`;
    }
    if (!extensions.length) return hasSeventh ? `${quality} 7` : quality;
    if (!hasSeventh) return `${quality} add ${extensions.join(', ')}`;
    const natural = extensions.filter((extension) => ['9', '11', '13'].includes(extension));
    const number = natural.length ? natural[natural.length - 1] : '7';
    const altered = extensions.filter((extension) => !natural.includes(extension));
    return `${quality} ${number}${altered.length ? `(${altered.join(', ')})` : ''}`;
}

const CHOICE_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

function ChipButton({ active, disabled, ...props }) {
    return (
        <button
            type="button"
            disabled={disabled}
            className={`rounded-full px-4 py-2 text-sm font-semibold transition-colors duration-150 disabled:pointer-events-none disabled:opacity-50 ${
                active ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
            }`}
            {...props}
        />
    );
}

// Piano-style keyboard, letter-named (C–B) rather than solfège — matches the app's
// VirtualKeyboard (app/practice.tsx), used instead of a flat list for "Find the Note".
const NOTE_WHITE_KEYS = ['C', 'D', 'E', 'F', 'G', 'A', 'B'];
const NOTE_BLACK_KEYS = [
    { afterIndex: 0, note: 'C#/Db' },
    { afterIndex: 1, note: 'D#/Eb' },
    { afterIndex: 3, note: 'F#/Gb' },
    { afterIndex: 4, note: 'G#/Ab' },
    { afterIndex: 5, note: 'A#/Bb' },
];

// Matches app/practice.tsx's colorsFor(): before answering, only the tapped choice is
// highlighted; after answering, the right answer turns green and a wrong pick turns red,
// wherever either one is — so a choice can be "correct" even if the member never selected it.
function gradedClasses({ answered, choice, correctAnswer, value }) {
    if (!answered) return value === choice ? 'selected' : 'default';
    if (choice === correctAnswer) return 'correct';
    if (choice === value) return 'incorrect';
    return 'default';
}

// referenceNote matches the app's VirtualKeyboard reference-note highlight (Find
// the Key #2 only) — one key stays visually marked throughout, independent of
// selection/answered state, so the member always has that anchor pitch in view.
function NoteKeyboardChoices({ answered, correctAnswer, value, onChange, keyboardAudio, referenceNote }) {
    const keyClass = (note, activeClass) => {
        const state = gradedClasses({ answered, choice: note, correctAnswer, value });
        if (state === 'selected' || state === 'correct') return activeClass.active;
        if (state === 'incorrect') return activeClass.incorrect;
        if (note === referenceNote) return activeClass.reference;
        return activeClass.default;
    };

    // Tapping a key plays that note in whichever sound the quiz is using, matching
    // the real instrument the question audio played in — not a fixed generic tone.
    // Only populated for quizzes whose choices are the note names themselves (see
    // QuizController::keyboardAudioMap); silently does nothing elsewhere.
    const press = (note) => {
        onChange(note);
        const url = keyboardAudio?.[note];
        if (url) new Audio(url).play().catch(() => {});
    };

    return (
        <div>
            {referenceNote && (
                <p className="mb-2 text-center text-xs text-neutral-500">
                    The highlighted key (<span className="font-semibold text-amber-600">{referenceNote}</span>) is your reference note.
                </p>
            )}
            <div className="relative flex h-36 overflow-hidden rounded-2xl border border-black/10 bg-white sm:h-44">
                {NOTE_WHITE_KEYS.map((note, index) => (
                    <button
                        key={note}
                        type="button"
                        disabled={answered}
                        onClick={() => press(note)}
                        className={`flex flex-1 items-end justify-center pb-2.5 text-sm font-bold transition-colors duration-100 disabled:pointer-events-none ${keyClass(note, {
                            active: 'bg-emerald-600 text-white',
                            incorrect: 'bg-rose-600 text-white',
                            reference: 'bg-amber-100 text-amber-700 ring-2 ring-inset ring-amber-400',
                            default: 'text-neutral-400 hover:bg-neutral-50',
                        })} ${index < NOTE_WHITE_KEYS.length - 1 ? 'border-r border-neutral-200' : ''}`}
                    >
                        {note}
                    </button>
                ))}
                {NOTE_BLACK_KEYS.map(({ afterIndex, note }) => (
                    <button
                        key={note}
                        type="button"
                        disabled={answered}
                        onClick={() => press(note)}
                        style={{ left: blackKeyLeft(afterIndex, NOTE_WHITE_KEYS.length), width: blackKeyWidth(NOTE_WHITE_KEYS.length) }}
                        className={`absolute top-0 flex h-[62%] flex-col items-center justify-end gap-0.5 rounded-b-md pb-2 text-[10px] font-bold leading-tight transition-colors duration-100 disabled:pointer-events-none ${keyClass(note, {
                            active: 'bg-emerald-600 text-white',
                            incorrect: 'bg-rose-600 text-white',
                            reference: 'bg-amber-500 text-white',
                            default: 'bg-neutral-900 text-neutral-300 hover:bg-neutral-800',
                        })}`}
                    >
                        {note.split('/').map((part) => (
                            <span key={part} className="block">
                                {part}
                            </span>
                        ))}
                    </button>
                ))}
            </div>
        </div>
    );
}

function FlatChoices({ answered, choices, correctAnswer, value, onChange }) {
    return (
        <div className="grid gap-2.5 sm:grid-cols-2">
            {choices.map((choice, index) => {
                const state = gradedClasses({ answered, choice, correctAnswer, value });
                const border =
                    state === 'correct'
                        ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
                        : state === 'incorrect'
                          ? 'border-rose-600 bg-rose-50 text-rose-700'
                          : state === 'selected'
                            ? 'border-brand bg-brand/5 text-brand'
                            : 'border-black/10 bg-white text-neutral-900 hover:border-neutral-300';
                const badge =
                    state === 'correct'
                        ? 'bg-emerald-600 text-white'
                        : state === 'incorrect'
                          ? 'bg-rose-600 text-white'
                          : state === 'selected'
                            ? 'bg-brand text-white'
                            : 'bg-neutral-100 text-neutral-500';
                return (
                    <button
                        key={choice}
                        type="button"
                        disabled={answered}
                        onClick={() => onChange(choice)}
                        className={`flex items-center gap-3 rounded-xl border px-4 py-3.5 text-left text-sm font-semibold transition-colors duration-150 disabled:pointer-events-none ${border}`}
                    >
                        <span className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold ${badge}`}>
                            {CHOICE_LETTERS[index] ?? index + 1}
                        </span>
                        {choice}
                    </button>
                );
            })}
        </div>
    );
}

// A numbered step header — matches the app's compound picker (app/practice.tsx):
// a black circled step number beside "Select the chord quality"/"Select the inversion".
function StepHeading({ step, children }) {
    return (
        <div className="mb-3 flex items-center gap-2.5">
            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-xs font-bold text-white">{step}</span>
            <p className="text-base font-bold text-neutral-900">{children}</p>
        </div>
    );
}

// Full-width radio-style row shared by both groups below — a left circle
// indicator plus the label, matching the app's compound picker (app/practice.tsx).
// Colors follow the same correct/incorrect/selected states FlatChoices uses
// elsewhere, via gradedClasses — a row can be "correct" (green) even if the
// member never picked it, same rule as everywhere else answers are graded.
function RadioRow({ children, disabled, onClick, state }) {
    const border =
        state === 'correct'
            ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
            : state === 'incorrect'
              ? 'border-rose-600 bg-rose-50 text-rose-700'
              : state === 'selected'
                ? 'border-brand bg-brand/5 text-brand'
                : 'border-black/10 bg-white text-neutral-900 hover:border-neutral-300';
    const dot = state === 'correct' ? 'border-emerald-600' : state === 'incorrect' ? 'border-rose-600' : state === 'selected' ? 'border-brand' : 'border-neutral-300';
    const dotFill = state === 'correct' ? 'bg-emerald-600' : state === 'incorrect' ? 'bg-rose-600' : 'bg-brand';
    const active = state === 'correct' || state === 'incorrect' || state === 'selected';

    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={`flex w-full items-center gap-3 rounded-xl border px-4 py-3.5 text-left text-sm font-semibold transition-colors duration-150 disabled:pointer-events-none ${border}`}
        >
            <span className={`flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full border-2 ${dot}`} style={{ height: '18px', width: '18px' }}>
                {active && <span className={`h-2 w-2 rounded-full ${dotFill}`} />}
            </span>
            {children}
        </button>
    );
}

// Two independent single-select groups, each its own numbered, bordered
// section, both laid out as the same full-width radio-row list — matches the
// app's compound quality+inversion picker (app/practice.tsx) — used where a
// chord's quality and inversion are both needed to be correct. correctAnswer
// is "{quality} · {inversion}" (see AnswerBuilder's compound onChange below),
// split back apart here so each group grades against its own half.
function CompoundChoices({ answered, compound, correctAnswer, quality, inversion, onQuality, onInversion }) {
    const [correctQuality, correctInversion] = correctAnswer ? correctAnswer.split(' · ') : [null, null];

    return (
        <div className="grid gap-4 sm:grid-cols-[3fr_2fr] sm:items-start">
            <div className="rounded-2xl border border-black/5 bg-neutral-50 p-5">
                <StepHeading step={1}>Select the chord quality</StepHeading>
                <div className="space-y-2.5">
                    {compound.qualities.map((option) => (
                        <RadioRow
                            key={option}
                            state={gradedClasses({ answered, choice: option, correctAnswer: correctQuality, value: quality })}
                            disabled={answered}
                            onClick={() => onQuality(option)}
                        >
                            {option}
                        </RadioRow>
                    ))}
                </div>
            </div>

            <div className="rounded-2xl border border-black/5 bg-neutral-50 p-5">
                <StepHeading step={2}>Select the inversion</StepHeading>
                <div className="space-y-2.5">
                    {compound.inversions.map((option) => (
                        <RadioRow
                            key={option}
                            state={gradedClasses({ answered, choice: option, correctAnswer: correctInversion, value: inversion })}
                            disabled={answered}
                            onClick={() => onInversion(option)}
                        >
                            {option}
                        </RadioRow>
                    ))}
                </div>
            </div>
        </div>
    );
}

// Checkbox-card grid for the extensions multi-select ("Name the Extensions")
// — matches the app's own checkbox-tile layout (app/practice.tsx) rather than
// the generic chip style MultiChoices below uses for other multi-selects
// (e.g. Chord naming's Degrees step), which stays a chip row on purpose.
// isWrong: this exact box should render as a red "✕" rather than a check —
// only true for a wrongly-selected box; a revealed-correct box (whether the
// member picked it or not) always shows a green "✓".
function CheckboxSquareIcon({ shown, filled, isWrong }) {
    return (
        <span
            className="flex h-5 w-5 shrink-0 items-center justify-center rounded-[6px] border-2 transition-colors duration-150"
            style={{ backgroundColor: shown ? filled : '#FFFFFF', borderColor: shown ? filled : '#D1D5DB' }}
        >
            {shown &&
                (isWrong ? (
                    <svg viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" className="h-3 w-3">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                ) : (
                    <svg viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" className="h-3 w-3">
                        <path d="M5 13l4 4L19 7" />
                    </svg>
                ))}
        </span>
    );
}

// Ported 1:1 from the app's practice.tsx multi-select feedback coloring:
// every correct extension shows green (even one the member never picked —
// "here's what you missed"), any wrongly-picked one shows red, everything
// else stays neutral.
function ExtensionCheckboxGrid({ answered, options, selected, correctPicks, onToggle }) {
    return (
        <div className="grid grid-cols-3 gap-2.5 sm:grid-cols-4">
            {options.map((option) => {
                const active = selected.includes(option);
                const isRevealedCorrect = answered && correctPicks.includes(option);
                const isWrongSelected = answered && active && !correctPicks.includes(option);
                const borderClass = isRevealedCorrect
                    ? 'border-emerald-600'
                    : isWrongSelected
                      ? 'border-brand'
                      : active
                        ? 'border-neutral-900'
                        : 'border-black/10';
                const filled = isRevealedCorrect ? '#1E8E3E' : isWrongSelected ? '#E00000' : '#171717';
                const showBox = isRevealedCorrect || isWrongSelected || active;
                return (
                    <button
                        key={option}
                        type="button"
                        disabled={answered}
                        onClick={() => onToggle(option)}
                        className={`flex items-center gap-2.5 rounded-xl border bg-white px-3.5 py-3 text-left transition-colors duration-150 disabled:pointer-events-none ${borderClass} ${
                            !answered ? 'hover:bg-neutral-50' : ''
                        }`}
                    >
                        <CheckboxSquareIcon shown={showBox} filled={filled} isWrong={isWrongSelected} />
                        <span className={`text-sm ${showBox ? 'font-bold text-neutral-900' : 'font-medium text-neutral-800'}`}>{option}</span>
                    </button>
                );
            })}
        </div>
    );
}

// Multi-select chip grid — matches the app's multi-select (multi: true in
// app/practice.tsx): more than one option can be part of the correct answer.
function MultiChoices({ answered, options, selected, onToggle }) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((option) => (
                <ChipButton key={option} active={selected.includes(option)} disabled={answered} onClick={() => onToggle(option)}>
                    {option}
                </ChipButton>
            ))}
        </div>
    );
}

// One numbered step panel (Chord Quality / Chord Degree / Exclude) — ported
// 1:1 from the app's NamingPanel (app/practice.tsx): grey card, numbered
// badge, "Pick one"/"Select all that apply" note, and per-chip green/red
// correct/incorrect coloring once answered (every correct option turns
// green even if the member never picked it — "here's what you missed" — and
// any wrongly-picked one turns red).
function NamingPanel({ answered, correct, hint, isActive, note, onToggle, options, step, title }) {
    return (
        <div className="rounded-2xl border border-black/5 bg-neutral-50 p-4">
            <div className="flex items-center gap-2.5">
                <span className="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-neutral-900 text-xs font-bold text-white">
                    {step}
                </span>
                <p className="flex-1 text-sm font-bold text-neutral-900">{title}</p>
                <p className="text-xs font-semibold text-neutral-400">{note}</p>
            </div>
            <div className="mt-3 flex flex-wrap gap-2">
                {options.map((option) => {
                    const active = isActive(option);
                    const isRevealedCorrect = answered && correct.includes(option);
                    const isWrongSelected = answered && active && !correct.includes(option);
                    const borderClass = isRevealedCorrect ? 'border-emerald-600' : isWrongSelected ? 'border-brand' : active ? 'border-neutral-900' : 'border-black/10';
                    const textClass = isRevealedCorrect ? 'text-emerald-700' : isWrongSelected ? 'text-brand' : active ? 'text-white' : 'text-neutral-700';
                    const bgClass = !answered && active ? 'bg-neutral-900' : 'bg-white';
                    const showCheck = isRevealedCorrect || isWrongSelected;
                    return (
                        <button
                            key={option}
                            type="button"
                            disabled={answered}
                            onClick={() => onToggle(option)}
                            className={`flex items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-semibold transition-colors duration-150 disabled:pointer-events-none ${borderClass} ${bgClass} ${textClass}`}
                        >
                            {isRevealedCorrect && <CheckIcon className="h-3.5 w-3.5" />}
                            {isWrongSelected && <CloseIconSmall className="h-3.5 w-3.5" />}
                            {option}
                        </button>
                    );
                })}
            </div>
            {hint && <p className="mt-2.5 text-xs text-neutral-400">{hint}</p>}
        </div>
    );
}

function CloseIconSmall(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 6l12 12M18 6L6 18" />
        </svg>
    );
}

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 13l4 4L19 7" />
        </svg>
    );
}

// Three-step chord-name builder for "Chord naming" — matches the app's naming
// flow (naming: { qualities, degrees, excludes } in app/practice.tsx), live
// preview pill included (the part the chosen degrees/exclude add shows in
// red, same as the app).
function NamingChoices({ answered, correctAnswer, quality, degrees, exclude, onQuality, onToggleDegree, onExclude, preview }) {
    const correct = correctAnswer ? decodeNamingAnswer(correctAnswer) : null;
    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-black/5 bg-white px-5 py-3.5 text-center shadow-sm">
                {quality ? (
                    <p className="text-xl font-extrabold text-neutral-900">
                        {quality}
                        <span className="text-brand">{preview.slice(quality.length)}</span>
                    </p>
                ) : (
                    <p className="text-xl font-extrabold text-neutral-300">?</p>
                )}
            </div>

            <NamingPanel
                answered={answered}
                correct={correct ? [correct.quality] : []}
                isActive={(option) => quality === option}
                note="Pick one"
                onToggle={onQuality}
                options={NAMING_QUALITIES}
                step={1}
                title="Chord Quality"
            />
            <NamingPanel
                answered={answered}
                correct={correct ? correct.degrees : []}
                isActive={(option) => degrees.includes(option)}
                note="Select all that apply"
                onToggle={onToggleDegree}
                options={NAMING_DEGREES}
                step={2}
                title="Chord Degree"
            />
            <NamingPanel
                answered={answered}
                correct={correct ? [correct.exclude] : []}
                hint='Select "None" if no notes are excluded.'
                isActive={(option) => exclude === option}
                note="Pick one"
                onToggle={onExclude}
                options={NAMING_EXCLUDES}
                step={3}
                title="Exclude (if any)"
            />
        </div>
    );
}

function ChevronRightIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 18l6-6-6-6" />
        </svg>
    );
}

// Matches the app's abbreviation shown under a fixed-quality degree box
// (Diatonic Progression) — e.g. "Minor" -> "Min".
const SHORT_QUALITY = { Major: 'Maj', Minor: 'Min', Diminished: 'Dim' };

// Ports app/practice.tsx's ProgressionBuilder: a "Your Progression" chip row
// (one chip per chord, tap to jump to it, Clear to wipe every slot) plus a
// panel below for the chord currently being edited — Number (degree) /
// Quality / Degrees(extensions) sections, each shown only when that axis
// isn't fixed/derived for this quiz. Auto-advances to the next chord once
// both degree and quality land, unless the quiz also has an extensions step
// (then advancing is manual via "Next Chord").
function ProgressionChoices({ answered, config, correctAnswer, slots, onChangeSlot, onClear, resetToken }) {
    const [activeIndex, setActiveIndex] = useState(0);
    useEffect(() => setActiveIndex(0), [resetToken]);

    const indices = Array.from({ length: config.length }, (_, index) => index);
    const degreeFor = (index) => (config.qualityOnly ? null : (config.fixedDegrees?.[index] ?? slots[index].degree));
    const qualityFor = (index) => {
        if (config.qualityOnly) return slots[index].quality;
        const degree = degreeFor(index);
        return (config.fixedQualities && degree ? config.fixedQualities[degree] : null) ?? slots[index].quality;
    };
    const composedFor = (index) => {
        const quality = qualityFor(index);
        if (!quality) return null;
        const extensions = slots[index].extensions ?? [];
        const base = config.qualityOnly ? quality : degreeFor(index) ? `${degreeFor(index)} ${quality}` : null;
        return base ? `${base}${extensions.length ? `(${extensions.join(', ')})` : ''}` : null;
    };

    const hasAny = slots.some((slot) => slot.degree || slot.quality || slot.extensions?.length);
    const correctParts = correctAnswer ? correctAnswer.split(' - ') : [];

    const current = slots[activeIndex];
    const currentDegree = degreeFor(activeIndex);
    const currentQuality = qualityFor(activeIndex);

    const advanceIfJustCompleted = (wasComplete, nowComplete) => {
        if (!config.extensionOptions && !wasComplete && nowComplete && activeIndex < config.length - 1) {
            setActiveIndex(activeIndex + 1);
        }
    };

    const chooseDegree = (option) => {
        const wasComplete = Boolean(currentDegree && currentQuality);
        onChangeSlot(activeIndex, 'degree', option);
        if (config.fixedQualities) {
            onChangeSlot(activeIndex, 'quality', config.fixedQualities[option]);
            advanceIfJustCompleted(wasComplete, true);
        } else {
            advanceIfJustCompleted(wasComplete, Boolean(option && current.quality));
        }
    };

    const chooseQuality = (option) => {
        const wasComplete = config.qualityOnly ? Boolean(currentQuality) : Boolean(currentDegree && currentQuality);
        onChangeSlot(activeIndex, 'quality', option);
        advanceIfJustCompleted(wasComplete, config.qualityOnly ? Boolean(option) : Boolean(currentDegree && option));
    };

    const toggleExtension = (option) => {
        const list = current.extensions ?? [];
        onChangeSlot(activeIndex, 'extensions', list.includes(option) ? list.filter((item) => item !== option) : [...list, option]);
    };

    return (
        <div>
            <div className="flex items-center justify-between">
                <p className="text-[13px] font-semibold text-neutral-400">Your Progression</p>
                {!answered && (
                    <button
                        type="button"
                        disabled={!hasAny}
                        onClick={() => {
                            onClear();
                            setActiveIndex(0);
                        }}
                        className="flex items-center gap-1 text-[13px] font-bold text-neutral-900 disabled:pointer-events-none disabled:opacity-35"
                    >
                        <UndoIcon className="h-3.5 w-3.5" />
                        Clear
                    </button>
                )}
            </div>

            <div className="mt-2.5 flex items-center gap-1">
                {indices.map((index) => {
                    const degree = degreeFor(index);
                    const quality = qualityFor(index);
                    const extensions = slots[index].extensions ?? [];
                    const isActive = !answered && index === activeIndex;
                    const right = answered && composedFor(index) === correctParts[index];

                    let classes;
                    if (answered) {
                        classes = right ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-rose-600 bg-rose-50 text-rose-700';
                    } else if (isActive) {
                        classes = 'border-neutral-900 bg-neutral-900 text-white';
                    } else {
                        classes = `bg-white text-neutral-900 ${degree || quality ? 'border-neutral-300' : 'border-neutral-200'}`;
                    }

                    return (
                        <Fragment key={index}>
                            <button
                                type="button"
                                disabled={answered}
                                onClick={() => setActiveIndex(index)}
                                aria-label={`Chord ${index + 1}`}
                                className={`flex min-h-[58px] flex-1 flex-col items-center justify-center gap-0.5 rounded-xl border-[1.5px] px-0.5 py-1.5 transition-colors duration-150 ${classes}`}
                            >
                                {!config.qualityOnly && <span className="text-xl font-extrabold">{degree ?? '?'}</span>}
                                <span className={`truncate text-[11px] font-semibold ${quality ? '' : 'opacity-50'}`}>{quality ?? 'Quality'}</span>
                                {extensions.length > 0 && <span className="truncate text-[10px] font-medium opacity-80">{extensions.join(', ')}</span>}
                            </button>
                            {index < indices.length - 1 && <ChevronRightIcon className="h-3 w-3 shrink-0 text-neutral-300" />}
                        </Fragment>
                    );
                })}
            </div>

            {!answered && (
                <div className="mt-2.5 rounded-2xl border border-black/10 p-3.5">
                    <div className="flex items-center justify-between">
                        <p className="text-[15px] font-extrabold text-neutral-900">Chord {activeIndex + 1}</p>
                        {config.extensionOptions && activeIndex < config.length - 1 && (
                            <button
                                type="button"
                                onClick={() => setActiveIndex(activeIndex + 1)}
                                className="flex items-center gap-0.5 text-[13px] font-bold text-neutral-900"
                            >
                                Next Chord
                                <ChevronRightIcon className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>

                    {!config.qualityOnly && !config.fixedDegrees && (
                        <div className="mt-3.5">
                            <p className="mb-2 text-xs font-semibold text-neutral-400">Number</p>
                            <div className="flex gap-1.5">
                                {config.degreeOptions.map((option) =>
                                    config.fixedQualities ? (
                                        <button
                                            key={option}
                                            type="button"
                                            onClick={() => chooseDegree(option)}
                                            className={`flex h-[58px] flex-1 flex-col items-center justify-center gap-0.5 rounded-xl transition-colors duration-150 ${
                                                currentDegree === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200'
                                            }`}
                                        >
                                            <span className="text-lg font-extrabold">{option}</span>
                                            <span className={`text-[10px] font-semibold ${currentDegree === option ? 'text-white/80' : 'text-neutral-400'}`}>
                                                {SHORT_QUALITY[config.fixedQualities[option]] ?? config.fixedQualities[option]}
                                            </span>
                                        </button>
                                    ) : (
                                        <button
                                            key={option}
                                            type="button"
                                            onClick={() => chooseDegree(option)}
                                            className={`flex aspect-square flex-1 items-center justify-center rounded-full text-base font-bold transition-colors duration-150 ${
                                                currentDegree === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200'
                                            }`}
                                        >
                                            {option}
                                        </button>
                                    ),
                                )}
                            </div>
                        </div>
                    )}

                    {!config.fixedQualities && (
                        <div className={config.qualityOnly && config.fixedDegrees ? '' : 'mt-3.5'}>
                            <p className="mb-2 text-xs font-semibold text-neutral-400">Quality</p>
                            <div className="flex flex-wrap gap-1.5">
                                {config.qualityOptions.map((option) => (
                                    <button
                                        key={option}
                                        type="button"
                                        onClick={() => chooseQuality(option)}
                                        style={{ width: config.qualityOptions.length === 4 ? '48%' : '31%' }}
                                        className={`flex min-h-[40px] flex-grow items-center justify-center rounded-lg text-sm transition-colors duration-150 ${
                                            currentQuality === option
                                                ? 'bg-neutral-900 font-bold text-white'
                                                : 'bg-neutral-100 font-medium text-neutral-700 hover:bg-neutral-200'
                                        }`}
                                    >
                                        {option}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {config.extensionOptions && (
                        <div className="mt-3.5">
                            <p className="mb-2 text-xs font-semibold text-neutral-400">
                                Degrees <span className="font-medium text-neutral-300">(optional)</span>
                            </p>
                            <div className="flex flex-wrap gap-1.5">
                                {config.extensionOptions.map((option) => {
                                    const on = (current.extensions ?? []).includes(option);
                                    return (
                                        <button
                                            key={option}
                                            type="button"
                                            onClick={() => toggleExtension(option)}
                                            className={`rounded-full px-3.5 py-2 text-sm transition-colors duration-150 ${
                                                on ? 'bg-neutral-900 font-bold text-white' : 'bg-neutral-100 font-medium text-neutral-700 hover:bg-neutral-200'
                                            }`}
                                        >
                                            {option}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// Tap-to-build melody answer for Melodic Dictation — matches the app's
// SequenceBoard + SolfegeKeyboard: the member taps notes in order to build
// the melody they heard, instead of picking one of a flat list of options.
// These three never put a chromatic note in the correct answer (only
// Dual-tonic/Chordal melodies do), so their black keys should look normal
// but simply do nothing when tapped — not grayed out, just inert.
const SEQUENCE_QUIZZES_WITHOUT_CHROMATIC_NOTES = new Set(['3-Note Stepwise', '3-Note with Skips', '5-Note Melodies']);

function SequenceChoices({ answered, length, sequence, correctAnswer, quizTitle, onTap, onUndo, onClear }) {
    const slots = Array.from({ length }, (_, index) => sequence[index] ?? null);
    const blackKeysInert = SEQUENCE_QUIZZES_WITHOUT_CHROMATIC_NOTES.has(quizTitle);
    const correctNotes = correctAnswer ? correctAnswer.split(' ') : [];

    const slotClasses = (note, index) => {
        if (!note) return 'border-dashed border-neutral-300 text-neutral-300';
        if (!answered) return 'border-sky-500/40 bg-sky-50 text-sky-700';
        return note === correctNotes[index]
            ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
            : 'border-rose-600 bg-rose-50 text-rose-700';
    };

    return (
        <div>
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex flex-wrap gap-2">
                    {slots.map((note, index) => (
                        <span
                            key={index}
                            className={`flex h-11 w-16 items-center justify-center rounded-lg border text-sm font-bold ${slotClasses(note, index)}`}
                        >
                            {note ?? index + 1}
                        </span>
                    ))}
                </div>
                <div className="flex shrink-0 gap-2">
                    <button
                        type="button"
                        disabled={answered || sequence.length === 0}
                        onClick={onUndo}
                        title="Undo"
                        aria-label="Undo"
                        className="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 transition-colors duration-150 hover:bg-neutral-200 hover:text-neutral-900 disabled:pointer-events-none disabled:opacity-30"
                    >
                        <UndoIcon className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        disabled={answered || sequence.length === 0}
                        onClick={onClear}
                        title="Clear"
                        aria-label="Clear"
                        className="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-100 text-neutral-600 transition-colors duration-150 hover:bg-rose-100 hover:text-rose-700 disabled:pointer-events-none disabled:opacity-30"
                    >
                        <TrashIcon className="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div className="relative mt-3 flex h-28 overflow-hidden rounded-2xl border border-black/10 bg-white">
                {KEYBOARD_WHITE_KEYS.map((note, index) => (
                    <button
                        key={index}
                        type="button"
                        disabled={answered || sequence.length >= length}
                        onClick={() => onTap(note)}
                        className={`flex flex-1 items-end justify-center pb-2.5 text-sm font-semibold text-neutral-600 transition-colors duration-100 hover:bg-neutral-50 active:bg-brand active:text-white disabled:pointer-events-none disabled:opacity-30 ${
                            index < KEYBOARD_WHITE_KEYS.length - 1 ? 'border-r border-neutral-200' : ''
                        }`}
                    >
                        {note}
                    </button>
                ))}
                {KEYBOARD_BLACK_KEYS.map(({ afterIndex, note }) => (
                    <button
                        key={note}
                        type="button"
                        disabled={answered || sequence.length >= length}
                        onClick={() => {
                            if (!blackKeysInert) onTap(note);
                        }}
                        style={{ left: blackKeyLeft(afterIndex, KEYBOARD_WHITE_KEYS.length), width: blackKeyWidth(KEYBOARD_WHITE_KEYS.length) }}
                        className={`absolute top-0 flex h-[62%] items-end justify-center rounded-b-md bg-neutral-900 pb-2 text-xs font-semibold text-white transition-colors duration-100 disabled:pointer-events-none disabled:opacity-30 ${
                            blackKeysInert ? '' : 'hover:bg-neutral-800 active:bg-brand'
                        }`}
                    >
                        {note}
                    </button>
                ))}
            </div>
        </div>
    );
}

const emptySlots = (config) => Array.from({ length: config?.length ?? 0 }, () => ({ degree: null, quality: null, extensions: [] }));

/**
 * Renders the right answer-input design for a quiz (matching the real app's
 * per-quiz-type UI — flat multiple choice, compound quality+inversion,
 * multi-select, chord-naming builder, progression builder, or a note
 * sequence board) and reports the composed answer string up to the parent,
 * or null while the member's answer is still incomplete.
 */
export default function AnswerBuilder({ answered, answerLength, choices, chosen, correctAnswer, keyboardAudio, onChange, quiz, referenceNote, resetToken }) {
    const type = answerTypeFor(quiz.category, quiz.title);

    // Fixed-length quizzes (3-Note Stepwise, Skips, 5-Note Melodies) always
    // use their own constant. Variable-length ones (Dual-tonic/Chordal
    // melodies) size the slot board to the real question's note count
    // (answerLength, from the server — just the count, not the notes
    // themselves) instead of asking the member to guess the length first.
    const lengthConfig = MELODIC_DICTATION_LENGTHS[quiz.title];
    const sequenceLength = Array.isArray(lengthConfig) ? (answerLength ?? lengthConfig[0]) : lengthConfig;
    const [sequence, setSequence] = useState([]);

    const compound = compoundFor(quiz.category, quiz.title);
    const [quality, setQuality] = useState(null);
    const [inversion, setInversion] = useState(null);

    const multiOptions = multiFor(quiz.category, quiz.title);
    const [selected, setSelected] = useState([]);

    const [namingQuality, setNamingQuality] = useState(null);
    const [namingDegrees, setNamingDegrees] = useState([]);
    const [namingExclude, setNamingExclude] = useState(null);
    const namedChord = namingQuality ? buildNamedChord(namingQuality, namingDegrees, namingExclude) : '';
    // What actually gets submitted/graded — a structured, order-independent
    // encoding (see encodeNamingAnswer), not the human-readable namedChord
    // above (which is lossy and can't always be reconstructed back into
    // separate quality/degrees/exclude for per-chip feedback afterwards).
    const namingAnswer = namingQuality && namingExclude ? encodeNamingAnswer(namingQuality, namingDegrees, namingExclude) : null;

    // Most progression quizzes have a fixed chord count; Chordal Progressions
    // varies 5-6 chords per question (same patterns as Chordal melodies), so
    // — like the variable-length sequence quizzes above — it lists the
    // lengths to pick from and the server tells us which one this question
    // actually is via answerLength (count only, never the chords themselves).
    const rawProgressionConfig = progressionFor(quiz.category, quiz.title);
    const progressionConfig = rawProgressionConfig
        ? { ...rawProgressionConfig, length: Array.isArray(rawProgressionConfig.length) ? (answerLength ?? rawProgressionConfig.length[0]) : rawProgressionConfig.length }
        : null;
    const [slots, setSlots] = useState(() => emptySlots(progressionConfig));
    const progressionPreview = progressionConfig ? composeProgression(progressionConfig, slots) : '';

    const [flatChoice, setFlatChoice] = useState(chosen ?? null);

    // A new question resets every widget's local state, regardless of type —
    // simpler than tracking which one is active and only resetting that one.
    useEffect(() => {
        setSequence([]);
        setQuality(null);
        setInversion(null);
        setSelected([]);
        setNamingQuality(null);
        setNamingDegrees([]);
        setNamingExclude(null);
        setSlots(emptySlots(progressionConfig));
        setFlatChoice(null);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [resetToken]);

    useEffect(() => {
        if (type === 'sequence') {
            onChange(sequence.length === sequenceLength ? sequence.join(' ') : null);
        } else if (type === 'compound') {
            onChange(quality && inversion ? `${quality} · ${inversion}` : null);
        } else if (type === 'multi') {
            onChange(selected.length ? selected.join(', ') : null);
        } else if (type === 'naming') {
            onChange(namingAnswer);
        } else if (type === 'progression') {
            onChange(progressionPreview || null);
        } else {
            onChange(flatChoice);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [type, sequence, sequenceLength, quality, inversion, selected, namingAnswer, progressionPreview, flatChoice]);

    if (type === 'sequence') {
        return (
            <div>
                <SequenceChoices
                    answered={answered}
                    length={sequenceLength}
                    sequence={sequence}
                    correctAnswer={correctAnswer}
                    quizTitle={quiz.title}
                    onTap={(note) => setSequence((prev) => (prev.length < sequenceLength ? [...prev, note] : prev))}
                    onUndo={() => setSequence((prev) => prev.slice(0, -1))}
                    onClear={() => setSequence([])}
                />
            </div>
        );
    }

    if (type === 'compound') {
        return (
            <CompoundChoices
                answered={answered}
                compound={compound}
                correctAnswer={correctAnswer}
                quality={quality}
                inversion={inversion}
                onQuality={setQuality}
                onInversion={setInversion}
            />
        );
    }

    if (type === 'multi') {
        return (
            <ExtensionCheckboxGrid
                answered={answered}
                options={multiOptions}
                selected={selected}
                correctPicks={correctAnswer ? correctAnswer.split(', ') : []}
                onToggle={(option) => setSelected((prev) => (prev.includes(option) ? prev.filter((item) => item !== option) : [...prev, option]))}
            />
        );
    }

    if (type === 'naming') {
        return (
            <NamingChoices
                answered={answered}
                correctAnswer={correctAnswer}
                quality={namingQuality}
                degrees={namingDegrees}
                exclude={namingExclude}
                onQuality={setNamingQuality}
                onToggleDegree={(option) => setNamingDegrees((prev) => (prev.includes(option) ? prev.filter((item) => item !== option) : [...prev, option]))}
                onExclude={setNamingExclude}
                preview={namedChord}
            />
        );
    }

    if (type === 'progression') {
        return (
            <ProgressionChoices
                answered={answered}
                config={progressionConfig}
                correctAnswer={correctAnswer}
                slots={slots}
                onChangeSlot={(index, field, value) => setSlots((prev) => prev.map((slot, i) => (i === index ? { ...slot, [field]: value } : slot)))}
                onClear={() => setSlots(emptySlots(progressionConfig))}
                resetToken={resetToken}
            />
        );
    }

    if (type === 'keyboard') {
        return (
            <NoteKeyboardChoices
                answered={answered}
                correctAnswer={correctAnswer}
                value={flatChoice}
                onChange={setFlatChoice}
                keyboardAudio={keyboardAudio}
                referenceNote={referenceNote}
            />
        );
    }

    return <FlatChoices answered={answered} choices={choices} correctAnswer={correctAnswer} value={flatChoice} onChange={setFlatChoice} />;
}
