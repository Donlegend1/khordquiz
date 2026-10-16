import { useEffect, useState } from 'react';
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
    multiFor,
    progressionFor,
} from '@/lib/quizAnswerPresets';

// Which of the real app's several answer-input designs (app/practice.tsx) a
// quiz uses — the same detection the Audio Quiz admin editor runs when
// authoring a question, reused here so the member sees the matching input.
export function answerTypeFor(category, title) {
    if (MELODIC_DICTATION_LENGTHS[title]) return 'sequence';
    if (compoundFor(category, title)) return 'compound';
    if (multiFor(category, title)) return 'multi';
    if (title === 'Chord naming') return 'naming';
    if (progressionFor(category, title)) return 'progression';
    if (title === 'Find the Note') return 'keyboard';
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

function NoteKeyboardChoices({ answered, correctAnswer, value, onChange }) {
    const keyClass = (note, activeClass) => {
        const state = gradedClasses({ answered, choice: note, correctAnswer, value });
        if (state === 'selected' || state === 'correct') return activeClass.active;
        if (state === 'incorrect') return activeClass.incorrect;
        return activeClass.default;
    };

    return (
        <div className="relative flex h-36 overflow-hidden rounded-2xl border border-black/10 bg-white sm:h-44">
            {NOTE_WHITE_KEYS.map((note, index) => (
                <button
                    key={note}
                    type="button"
                    disabled={answered}
                    onClick={() => onChange(note)}
                    className={`flex flex-1 items-end justify-center pb-2.5 text-sm font-bold transition-colors duration-100 disabled:pointer-events-none ${keyClass(note, {
                        active: 'bg-emerald-600 text-white',
                        incorrect: 'bg-rose-600 text-white',
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
                    onClick={() => onChange(note)}
                    style={{ left: blackKeyLeft(afterIndex, NOTE_WHITE_KEYS.length), width: blackKeyWidth(NOTE_WHITE_KEYS.length) }}
                    className={`absolute top-0 flex h-[62%] flex-col items-center justify-end gap-0.5 rounded-b-md pb-2 text-[10px] font-bold leading-tight transition-colors duration-100 disabled:pointer-events-none ${keyClass(note, {
                        active: 'bg-emerald-600 text-white',
                        incorrect: 'bg-rose-600 text-white',
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

// Two independent single-select groups — matches the app's compound
// quality+inversion picker (app/practice.tsx), used where a chord's quality
// and inversion are both needed to be correct.
function CompoundChoices({ answered, compound, quality, inversion, onQuality, onInversion }) {
    return (
        <div className="space-y-4">
            <div>
                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Quality</p>
                <div className="flex flex-wrap gap-2">
                    {compound.qualities.map((option) => (
                        <ChipButton key={option} active={quality === option} disabled={answered} onClick={() => onQuality(option)}>
                            {option}
                        </ChipButton>
                    ))}
                </div>
            </div>
            <div>
                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Inversion</p>
                <div className="flex flex-wrap gap-2">
                    {compound.inversions.map((option) => (
                        <ChipButton key={option} active={inversion === option} disabled={answered} onClick={() => onInversion(option)}>
                            {option}
                        </ChipButton>
                    ))}
                </div>
            </div>
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

// Three-step chord-name builder for "Chord naming" — matches the app's naming
// flow (naming: { qualities, degrees, excludes } in app/practice.tsx).
function NamingChoices({ answered, quality, degrees, exclude, onQuality, onToggleDegree, onExclude, preview }) {
    return (
        <div className="space-y-4">
            <div>
                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Quality</p>
                <div className="flex flex-wrap gap-2">
                    {NAMING_QUALITIES.map((option) => (
                        <ChipButton key={option} active={quality === option} disabled={answered} onClick={() => onQuality(option)}>
                            {option}
                        </ChipButton>
                    ))}
                </div>
            </div>
            <div>
                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Degrees</p>
                <MultiChoices answered={answered} options={NAMING_DEGREES} selected={degrees} onToggle={onToggleDegree} />
            </div>
            <div>
                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Exclude</p>
                <div className="flex flex-wrap gap-2">
                    {NAMING_EXCLUDES.map((option) => (
                        <ChipButton key={option} active={exclude === option} disabled={answered} onClick={() => onExclude(option)}>
                            {option}
                        </ChipButton>
                    ))}
                </div>
            </div>
            {preview && (
                <div className="rounded-xl bg-neutral-50 px-4 py-3 text-sm">
                    <span className="text-neutral-400">Chord name: </span>
                    <span className="font-bold text-neutral-900">{preview}</span>
                </div>
            )}
        </div>
    );
}

// Slot-by-slot chord-progression builder — matches the app's progression
// builder (app/practice.tsx): each chord gets a degree and quality (either
// picked or fixed/derived), with an optional extensions multi-select.
function ProgressionChoices({ answered, config, slots, onChangeSlot, preview }) {
    return (
        <div>
            <div className="flex flex-wrap gap-3">
                {Array.from({ length: config.length }, (_, index) => {
                    const slot = slots[index];
                    const fixedDegree = config.fixedDegrees?.[index];
                    const degree = fixedDegree ?? slot.degree;
                    const fixedQuality = config.fixedQualities && degree ? config.fixedQualities[degree] : null;
                    const complete = Boolean(degree && (fixedQuality || slot.quality));

                    return (
                        <div
                            key={index}
                            className={`w-48 shrink-0 rounded-xl border p-3.5 ${complete ? 'border-brand/30 bg-brand/5' : 'border-black/10'}`}
                        >
                            <div className="mb-2 flex items-center justify-between">
                                <p className="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Chord {index + 1}</p>
                                {complete && <span className="h-1.5 w-1.5 rounded-full bg-brand" />}
                            </div>

                            <p className="mb-1 text-[11px] font-semibold text-neutral-400">Degree</p>
                            {fixedDegree ? (
                                <span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-neutral-900 text-xs font-bold text-white">
                                    {fixedDegree}
                                </span>
                            ) : (
                                <div className="flex flex-wrap gap-1">
                                    {config.degreeOptions.map((option) => (
                                        <button
                                            key={option}
                                            type="button"
                                            disabled={answered}
                                            onClick={() => onChangeSlot(index, 'degree', option)}
                                            className={`flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold transition-colors duration-100 disabled:pointer-events-none disabled:opacity-50 ${
                                                slot.degree === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                            }`}
                                        >
                                            {option}
                                        </button>
                                    ))}
                                </div>
                            )}

                            <p className="mb-1 mt-2.5 text-[11px] font-semibold text-neutral-400">Quality</p>
                            {fixedQuality ? (
                                <span className="inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                    {fixedQuality}
                                </span>
                            ) : config.fixedQualities ? (
                                <span className="text-xs text-neutral-400">Pick a degree first</span>
                            ) : (
                                <div className="flex flex-wrap gap-1">
                                    {config.qualityOptions.map((option) => (
                                        <button
                                            key={option}
                                            type="button"
                                            disabled={answered}
                                            onClick={() => onChangeSlot(index, 'quality', option)}
                                            className={`rounded-full px-2 py-0.5 text-[11px] font-semibold transition-colors duration-100 disabled:pointer-events-none disabled:opacity-50 ${
                                                slot.quality === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                            }`}
                                        >
                                            {option}
                                        </button>
                                    ))}
                                </div>
                            )}

                            {config.extensionOptions && (
                                <>
                                    <p className="mb-1 mt-2.5 text-[11px] font-semibold text-neutral-400">Extensions</p>
                                    <div className="flex flex-wrap gap-1">
                                        {config.extensionOptions.map((option) => {
                                            const active = slot.extensions.includes(option);
                                            return (
                                                <button
                                                    key={option}
                                                    type="button"
                                                    disabled={answered}
                                                    onClick={() =>
                                                        onChangeSlot(
                                                            index,
                                                            'extensions',
                                                            active ? slot.extensions.filter((item) => item !== option) : [...slot.extensions, option],
                                                        )
                                                    }
                                                    className={`rounded-full px-2 py-0.5 text-[11px] font-semibold transition-colors duration-100 disabled:pointer-events-none disabled:opacity-50 ${
                                                        active ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                                    }`}
                                                >
                                                    {option}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </>
                            )}
                        </div>
                    );
                })}
            </div>
            <div className="mt-3 rounded-xl bg-neutral-50 px-4 py-3 text-sm">
                <span className="text-neutral-400">Progression: </span>
                <span className="font-bold text-neutral-900">{preview || '—'}</span>
            </div>
        </div>
    );
}

// Tap-to-build melody answer for Melodic Dictation — matches the app's
// SequenceBoard + SolfegeKeyboard: the member taps notes in order to build
// the melody they heard, instead of picking one of a flat list of options.
function SequenceChoices({ answered, length, sequence, onTap, onUndo, onClear }) {
    const slots = Array.from({ length }, (_, index) => sequence[index] ?? null);

    return (
        <div>
            <div className="flex flex-wrap gap-2">
                {slots.map((note, index) => (
                    <span
                        key={index}
                        className={`flex h-11 w-16 items-center justify-center rounded-lg border text-sm font-bold ${
                            note ? 'border-brand/40 bg-brand/5 text-brand' : 'border-dashed border-neutral-300 text-neutral-300'
                        }`}
                    >
                        {note ?? index + 1}
                    </span>
                ))}
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
                        onClick={() => onTap(note)}
                        style={{ left: blackKeyLeft(afterIndex, KEYBOARD_WHITE_KEYS.length), width: blackKeyWidth(KEYBOARD_WHITE_KEYS.length) }}
                        className="absolute top-0 flex h-[62%] items-end justify-center rounded-b-md bg-neutral-900 pb-2 text-xs font-semibold text-white transition-colors duration-100 hover:bg-neutral-800 active:bg-brand disabled:pointer-events-none disabled:opacity-30"
                    >
                        {note}
                    </button>
                ))}
            </div>

            <div className="mt-3 flex gap-4">
                <button type="button" disabled={answered || sequence.length === 0} onClick={onUndo} className="text-sm font-semibold text-neutral-600 hover:text-neutral-900 disabled:opacity-40">
                    Undo
                </button>
                <button type="button" disabled={answered || sequence.length === 0} onClick={onClear} className="text-sm font-semibold text-neutral-600 hover:text-neutral-900 disabled:opacity-40">
                    Clear
                </button>
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
export default function AnswerBuilder({ answered, choices, chosen, correctAnswer, onChange, quiz, resetToken }) {
    const type = answerTypeFor(quiz.category, quiz.title);

    const lengthConfig = MELODIC_DICTATION_LENGTHS[quiz.title];
    const lengthOptions = Array.isArray(lengthConfig) ? lengthConfig : null;
    const [sequenceLength, setSequenceLength] = useState(lengthOptions ? lengthOptions[0] : lengthConfig);
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

    const progressionConfig = progressionFor(quiz.category, quiz.title);
    const [slots, setSlots] = useState(() => emptySlots(progressionConfig));
    const progressionPreview = progressionConfig ? composeProgression(progressionConfig, slots) : '';

    const [flatChoice, setFlatChoice] = useState(chosen ?? null);

    // A new question resets every widget's local state, regardless of type —
    // simpler than tracking which one is active and only resetting that one.
    useEffect(() => {
        setSequence([]);
        if (lengthOptions) setSequenceLength(lengthOptions[0]);
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
            onChange(namedChord || null);
        } else if (type === 'progression') {
            onChange(progressionPreview || null);
        } else {
            onChange(flatChoice);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [type, sequence, sequenceLength, quality, inversion, selected, namedChord, progressionPreview, flatChoice]);

    if (type === 'sequence') {
        return (
            <div>
                {lengthOptions && (
                    <div className="mb-3 flex items-center gap-2">
                        <span className="text-xs font-semibold text-neutral-500">Notes:</span>
                        {lengthOptions.map((option) => (
                            <button
                                key={option}
                                type="button"
                                disabled={answered}
                                onClick={() => {
                                    setSequenceLength(option);
                                    setSequence([]);
                                }}
                                className={`h-7 w-7 rounded-full text-xs font-bold transition-colors duration-100 disabled:pointer-events-none disabled:opacity-50 ${
                                    sequenceLength === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                                }`}
                            >
                                {option}
                            </button>
                        ))}
                    </div>
                )}
                <SequenceChoices
                    answered={answered}
                    length={sequenceLength}
                    sequence={sequence}
                    onTap={(note) => setSequence((prev) => (prev.length < sequenceLength ? [...prev, note] : prev))}
                    onUndo={() => setSequence((prev) => prev.slice(0, -1))}
                    onClear={() => setSequence([])}
                />
            </div>
        );
    }

    if (type === 'compound') {
        return <CompoundChoices answered={answered} compound={compound} quality={quality} inversion={inversion} onQuality={setQuality} onInversion={setInversion} />;
    }

    if (type === 'multi') {
        return (
            <MultiChoices
                answered={answered}
                options={multiOptions}
                selected={selected}
                onToggle={(option) => setSelected((prev) => (prev.includes(option) ? prev.filter((item) => item !== option) : [...prev, option]))}
            />
        );
    }

    if (type === 'naming') {
        return (
            <NamingChoices
                answered={answered}
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
                slots={slots}
                onChangeSlot={(index, field, value) => setSlots((prev) => prev.map((slot, i) => (i === index ? { ...slot, [field]: value } : slot)))}
                preview={progressionPreview}
            />
        );
    }

    if (type === 'keyboard') {
        return <NoteKeyboardChoices answered={answered} correctAnswer={correctAnswer} value={flatChoice} onChange={setFlatChoice} />;
    }

    return <FlatChoices answered={answered} choices={choices} correctAnswer={correctAnswer} value={flatChoice} onChange={setFlatChoice} />;
}
