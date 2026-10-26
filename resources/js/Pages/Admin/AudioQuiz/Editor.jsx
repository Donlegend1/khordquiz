import AppShell from '@/Layouts/AppShell';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    CHORD_TONE_QUALITIES,
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
    presetFor,
    progressionFor,
} from '@/lib/quizAnswerPresets';

const TABS = ['Video', 'Difficulty', 'Reference Audio'];

function QuizIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="8.5" />
            <path d="M10 9.3l5.5 2.7-5.5 2.7z" fill="currentColor" stroke="none" />
        </svg>
    );
}

function TrashIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 7h14M9 7V5.5A1.5 1.5 0 0110.5 4h3A1.5 1.5 0 0115 5.5V7m-7 0v12a2 2 0 002 2h4a2 2 0 002-2V7" />
        </svg>
    );
}

function LessonList({ category, categorySlug, lessons, selectedSlug }) {
    return (
        <div className="w-full shrink-0 lg:w-72">
            <p className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-neutral-400">{category}</p>
            <div className="max-h-[70vh] space-y-1.5 overflow-y-auto rounded-2xl border border-black/5 bg-white p-2">
                {lessons.map((lesson) => {
                    const active = lesson.slug === selectedSlug;
                    return (
                        <Link
                            key={lesson.slug}
                            href={route('admin.audio-quiz.lesson', [categorySlug, lesson.slug])}
                            className={`flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm transition-colors duration-150 ${
                                active ? 'bg-neutral-900 text-white' : lesson.configured ? 'text-neutral-900 hover:bg-neutral-50' : 'text-neutral-400 hover:bg-neutral-50'
                            }`}
                        >
                            <span className="min-w-0 truncate font-semibold">{lesson.title}</span>
                            <span className={`shrink-0 text-xs ${active ? 'text-white/60' : 'text-neutral-400'}`}>
                                {lesson.configured ? lesson.question_count : '—'}
                            </span>
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}

function SettingsTabs({ quiz, difficulties }) {
    const [tab, setTab] = useState('Video');
    const { data, setData, post, processing, errors } = useForm({
        difficulty: quiz.difficulty,
        description: quiz.description ?? '',
        video_embed: quiz.video_embed ?? '',
        reference_audio: null,
        keep_reference_audio: quiz.keep_reference_audio,
        is_published: quiz.is_published,
    });

    const save = () => {
        post(route('admin.audio-quiz.settings.update', quiz.id), { forceFormData: true, preserveScroll: true });
    };

    return (
        <div className="rounded-2xl border border-black/5 bg-white p-5">
            <div className="flex flex-wrap gap-1 rounded-xl bg-neutral-100 p-1">
                {TABS.map((item) => (
                    <button
                        key={item}
                        type="button"
                        onClick={() => setTab(item)}
                        className={`rounded-lg px-3.5 py-2 text-sm font-semibold transition-colors duration-150 ${
                            tab === item ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:text-neutral-900'
                        }`}
                    >
                        {item}
                    </button>
                ))}
            </div>

            <div className="mt-5">
                {tab === 'Video' && (
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-neutral-700">Embed video</label>
                        <p className="mb-2 text-xs text-neutral-400">Paste a Wistia, Vimeo, or other iframe/script embed code.</p>
                        <textarea
                            rows={6}
                            value={data.video_embed}
                            onChange={(e) => setData('video_embed', e.target.value)}
                            placeholder='<iframe src="..."></iframe>'
                            className="w-full rounded-lg border border-neutral-300 px-3.5 py-2.5 font-mono text-xs text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                        />
                        {errors.video_embed && <p className="mt-1.5 text-sm text-brand">{errors.video_embed}</p>}
                        <button type="button" disabled={processing} onClick={save} className="mt-3 rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60">
                            Save Video
                        </button>
                    </div>
                )}

                {tab === 'Difficulty' && (
                    <div className="space-y-4">
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-neutral-700">Difficulty</label>
                            <select
                                value={data.difficulty}
                                onChange={(e) => setData('difficulty', e.target.value)}
                                className="w-full max-w-xs rounded-lg border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                            >
                                {difficulties.map((d) => (
                                    <option key={d} value={d}>
                                        {d}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-neutral-700">Description</label>
                            <textarea
                                rows={3}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="What should the ear be listening for?"
                                className="w-full rounded-lg border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                            />
                        </div>
                        <button type="button" disabled={processing} onClick={save} className="rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60">
                            Save Difficulty
                        </button>
                    </div>
                )}

                {tab === 'Reference Audio' && (
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-neutral-700">Reference audio</label>
                        {data.keep_reference_audio && quiz.reference_audio_url && !data.reference_audio && (
                            <audio controls src={quiz.reference_audio_url} className="mb-3 w-full" />
                        )}
                        <input
                            type="file"
                            accept="audio/*"
                            onChange={(e) => setData('reference_audio', e.target.files?.[0] ?? null)}
                            className="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-neutral-800"
                        />
                        {errors.reference_audio && <p className="mt-1.5 text-sm text-brand">{errors.reference_audio}</p>}
                        {(data.reference_audio || data.keep_reference_audio) && (
                            <button
                                type="button"
                                onClick={() => {
                                    setData('reference_audio', null);
                                    setData('keep_reference_audio', false);
                                }}
                                className="mt-2 text-sm font-semibold text-brand hover:text-brand-dark"
                            >
                                Remove reference audio
                            </button>
                        )}
                        <div>
                            <button type="button" disabled={processing} onClick={save} className="mt-3 rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60">
                                Save Reference Audio
                            </button>
                        </div>

                        <div className="mt-6 border-t border-neutral-100 pt-5">
                            <ReferenceAudioTracks quiz={quiz} />
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

// A quiz can have several labeled reference clips — e.g. one per chord the
// quiz covers — shown to members as a "Choose a sound" style player with a
// Next button, not just the single file above. Added/removed one at a time
// (own routes) rather than batch-saved with the rest of this form.
function ReferenceAudioTracks({ quiz }) {
    const { data, setData, post, processing, errors, reset } = useForm({ label: '', name: '', audio: null });

    const addTrack = (e) => {
        e.preventDefault();
        post(route('admin.audio-quiz.reference-audio.store', quiz.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const removeTrack = (trackId) => {
        router.delete(route('admin.audio-quiz.reference-audio.destroy', [quiz.id, trackId]), { preserveScroll: true });
    };

    return (
        <div>
            <label className="mb-1.5 block text-sm font-medium text-neutral-700">Reference audio tracks</label>
            <p className="mb-3 text-xs text-neutral-400">
                Members can play these one at a time, with a title and a Next button — add one per sound the quiz covers.
            </p>

            {quiz.reference_audio_tracks.length > 0 && (
                <ul className="mb-4 space-y-2">
                    {quiz.reference_audio_tracks.map((track) => (
                        <li key={track.id} className="flex items-center gap-3 rounded-lg border border-neutral-200 px-3 py-2">
                            <span className="min-w-0 flex-1 truncate text-sm font-semibold text-neutral-800">
                                {track.label}
                                {track.name && <span className="ml-1.5 font-normal text-neutral-400">· {track.name}</span>}
                            </span>
                            <audio controls src={track.url} className="h-8 max-w-[220px]" />
                            <button
                                type="button"
                                onClick={() => removeTrack(track.id)}
                                className="shrink-0 text-sm font-semibold text-brand hover:text-brand-dark"
                            >
                                Remove
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            <form onSubmit={addTrack} className="flex flex-wrap items-start gap-2">
                <input
                    type="text"
                    value={data.label}
                    onChange={(e) => setData('label', e.target.value)}
                    placeholder="Label, e.g. Sus4"
                    className="w-40 rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                />
                <input
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Name of the audio"
                    className="w-48 rounded-lg border border-neutral-300 px-3 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                />
                <input
                    type="file"
                    accept="audio/*"
                    onChange={(e) => setData('audio', e.target.files?.[0] ?? null)}
                    className="text-sm text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-neutral-800"
                />
                <button
                    type="submit"
                    disabled={processing || !data.label || !data.name || !data.audio}
                    className="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-bold text-white disabled:opacity-60"
                >
                    Add track
                </button>
            </form>
            {(errors.label || errors.name || errors.audio) && <p className="mt-1.5 text-sm text-brand">{errors.label || errors.name || errors.audio}</p>}
        </div>
    );
}

// Tap-to-build melody answer for Melodic Dictation questions, mirroring the
// app's SequenceBoard + SolfegeKeyboard: admin taps notes in order to build the
// exact correct sequence instead of picking one of a flat list of options.
// Plain single-select box grid — used for both the key and the sound pickers
// below, rather than a piano-style visual.
function BoxPicker({ options, value, onChange, columns = 4 }) {
    return (
        <div className="grid gap-2" style={{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }}>
            {options.map((option) => (
                <button
                    key={option}
                    type="button"
                    onClick={() => onChange(option)}
                    className={`rounded-lg border px-3 py-2.5 text-sm font-semibold transition-colors duration-100 ${
                        value === option ? 'border-brand bg-brand text-white' : 'border-neutral-200 bg-neutral-50 text-neutral-600 hover:bg-neutral-100'
                    }`}
                >
                    {option}
                </button>
            ))}
        </div>
    );
}

// Matches the app's own fixed sound categories for Find the Key (src/lib/quiz-catalog.ts
// KEY_FINDING_SOUNDS) minus "All" — that option means "pull from every sound below,"
// not a distinct bucket of audio to upload, so there's nothing to tag questions with.
const KEY_FINDING_SOUNDS = ['Monophonic', 'Vocal Stems', 'Instruments', 'Ensemble', 'Full Song'];

// Lets the admin pick a key and a sound once, then drop every audio file for
// that key+sound combination in one go — each file becomes its own question
// with that key as the answer, instead of repeating the single-question form
// for every song.
function BulkKeyUploader({ quizId, choices }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        choices,
        answer: null,
        sound: null,
        audio: [],
    });

    const upload = () => {
        post(route('admin.audio-quiz.questions.bulk-store', quizId), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset('audio'),
        });
    };

    return (
        <div>
            <p className="mb-1.5 text-xs font-semibold text-neutral-500">Key</p>
            <BoxPicker options={choices} value={data.answer} onChange={(note) => setData('answer', note)} columns={4} />
            {errors.answer && <p className="mt-1.5 text-sm text-brand">{errors.answer}</p>}

            <p className="mb-1.5 mt-4 text-xs font-semibold text-neutral-500">Sound</p>
            <BoxPicker options={KEY_FINDING_SOUNDS} value={data.sound} onChange={(sound) => setData('sound', sound)} columns={3} />
            {errors.sound && <p className="mt-1.5 text-sm text-brand">{errors.sound}</p>}

            <div className="mt-4">
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Audio files for this key and sound</p>
                <input
                    type="file"
                    accept="audio/*"
                    multiple
                    onChange={(e) => setData('audio', Array.from(e.target.files ?? []))}
                    className="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-neutral-800"
                />
                {data.audio.length > 0 && <p className="mt-1.5 text-xs text-neutral-500">{data.audio.length} file(s) selected.</p>}
                {errors.audio && <p className="mt-1.5 text-sm text-brand">{errors.audio}</p>}
            </div>

            <button
                type="button"
                disabled={processing || !data.answer || !data.sound || data.audio.length === 0}
                onClick={upload}
                className="mt-4 rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white transition-opacity duration-150 disabled:opacity-60"
            >
                {processing ? 'Uploading…' : `Upload ${data.audio.length || ''} Question${data.audio.length === 1 ? '' : 's'}`}
            </button>
        </div>
    );
}

function SequenceKeyboard({ length, sequence, onTap, onUndo, onClear }) {
    const slots = Array.from({ length }, (_, index) => sequence[index] ?? null);

    return (
        <div>
            <div className="flex flex-wrap gap-2">
                {slots.map((note, index) => (
                    <span
                        key={index}
                        className={`flex h-11 w-16 items-center justify-center rounded-lg border text-sm font-bold ${
                            note ? 'border-brand/40 bg-rose-50 text-brand' : 'border-dashed border-neutral-300 text-neutral-300'
                        }`}
                    >
                        {note ?? index + 1}
                    </span>
                ))}
            </div>

            <div className="relative mt-3 flex h-28 overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                {KEYBOARD_WHITE_KEYS.map((note, index) => (
                    <button
                        key={index}
                        type="button"
                        disabled={sequence.length >= length}
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
                        disabled={sequence.length >= length}
                        onClick={() => onTap(note)}
                        style={{ left: blackKeyLeft(afterIndex, KEYBOARD_WHITE_KEYS.length), width: blackKeyWidth(KEYBOARD_WHITE_KEYS.length) }}
                        className="absolute top-0 flex h-[62%] items-end justify-center rounded-b-md bg-neutral-900 pb-2 text-xs font-semibold text-white transition-colors duration-100 hover:bg-neutral-800 active:bg-brand disabled:pointer-events-none disabled:opacity-30"
                    >
                        {note}
                    </button>
                ))}
            </div>

            <div className="mt-3 flex gap-4">
                <button type="button" disabled={sequence.length === 0} onClick={onUndo} className="text-sm font-semibold text-neutral-600 hover:text-neutral-900 disabled:opacity-40">
                    Undo
                </button>
                <button type="button" disabled={sequence.length === 0} onClick={onClear} className="text-sm font-semibold text-neutral-600 hover:text-neutral-900 disabled:opacity-40">
                    Clear
                </button>
            </div>
        </div>
    );
}

// Two independent single-select groups for quizzes that need both a chord
// quality and an inversion to be marked correct, matching the app's compound
// quality+inversion picker (app/practice.tsx).
function CompoundPicker({ qualities, inversions, quality, inversion, onQuality, onInversion }) {
    return (
        <div className="space-y-4">
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Quality</p>
                <div className="flex flex-wrap gap-2">
                    {qualities.map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onQuality(option)}
                            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                quality === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Inversion</p>
                <div className="flex flex-wrap gap-2">
                    {inversions.map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onInversion(option)}
                            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                inversion === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

// Multi-select chip grid — several extensions can be the correct answer at
// once, matching the app's multi-select (multi: true in app/practice.tsx).
function MultiPicker({ options, selected, onToggle }) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((option) => {
                const active = selected.includes(option);
                return (
                    <button
                        key={option}
                        type="button"
                        onClick={() => onToggle(option)}
                        className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                            active ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                        }`}
                    >
                        {option}
                    </button>
                );
            })}
        </div>
    );
}

// Three-step chord-name builder for "Chord naming": quality (single-select),
// degrees (multi-select, including split sharp/flat variants), and an
// exclusion (single-select) — composed live into the final chord name below.
function NamingPicker({ quality, degrees, exclude, onQuality, onToggleDegree, onExclude, preview }) {
    return (
        <div className="space-y-4">
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Quality</p>
                <div className="flex flex-wrap gap-2">
                    {NAMING_QUALITIES.map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onQuality(option)}
                            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                quality === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Degrees</p>
                <MultiPicker options={NAMING_DEGREES} selected={degrees} onToggle={onToggleDegree} />
            </div>
            <div>
                <p className="mb-1.5 text-xs font-semibold text-neutral-500">Exclude</p>
                <div className="flex flex-wrap gap-2">
                    {NAMING_EXCLUDES.map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onExclude(option)}
                            className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition-colors duration-100 ${
                                exclude === option ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'
                            }`}
                        >
                            {option}
                        </button>
                    ))}
                </div>
            </div>
            {preview && (
                <div className="rounded-lg bg-neutral-50 px-3.5 py-2.5 text-sm">
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
function ProgressionBuilder({ config, slots, onChangeSlot, preview }) {
    return (
        <div>
            <div className="flex flex-wrap items-start gap-2">
                {Array.from({ length: config.length }, (_, index) => {
                    const slot = slots[index];
                    const fixedDegree = config.fixedDegrees?.[index];
                    const degree = fixedDegree ?? slot.degree;
                    const fixedQuality = config.fixedQualities && degree ? config.fixedQualities[degree] : null;
                    const complete = Boolean(degree && (fixedQuality || slot.quality));

                    return (
                        <div key={index} className="flex items-start gap-2">
                            <div className={`w-44 shrink-0 rounded-xl border p-3 ${complete ? 'border-brand/30 bg-rose-50/40' : 'border-neutral-200'}`}>
                                <div className="mb-2 flex items-center justify-between">
                                    <p className="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Chord {index + 1}</p>
                                    {complete && <span className="h-1.5 w-1.5 rounded-full bg-brand" />}
                                </div>

                                <p className="mb-1 text-[11px] font-semibold text-neutral-400">Degree</p>
                                {fixedDegree ? (
                                    <span className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-neutral-900 text-xs font-bold text-white">{fixedDegree}</span>
                                ) : (
                                    <div className="flex flex-wrap gap-1">
                                        {config.degreeOptions.map((option) => (
                                            <button
                                                key={option}
                                                type="button"
                                                onClick={() => onChangeSlot(index, 'degree', option)}
                                                className={`flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold transition-colors duration-100 ${
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
                                    <span className="inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">{fixedQuality}</span>
                                ) : config.fixedQualities ? (
                                    <span className="text-xs text-neutral-400">Pick a degree first</span>
                                ) : (
                                    <select
                                        value={slot.quality ?? ''}
                                        onChange={(e) => onChangeSlot(index, 'quality', e.target.value || null)}
                                        className="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-xs focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900/10"
                                    >
                                        <option value="">Select…</option>
                                        {config.qualityOptions.map((option) => (
                                            <option key={option} value={option}>
                                                {option}
                                            </option>
                                        ))}
                                    </select>
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
                                                        onClick={() =>
                                                            onChangeSlot(
                                                                index,
                                                                'extensions',
                                                                active ? slot.extensions.filter((item) => item !== option) : [...slot.extensions, option],
                                                            )
                                                        }
                                                        className={`rounded-full px-2 py-0.5 text-[11px] font-semibold transition-colors duration-100 ${
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
                            {index < config.length - 1 && <span className="mt-8 shrink-0 text-neutral-300">&rarr;</span>}
                        </div>
                    );
                })}
            </div>
            <div className="mt-3 rounded-lg bg-neutral-50 px-3.5 py-2.5 text-sm">
                <span className="text-neutral-400">Progression: </span>
                <span className="font-bold text-neutral-900">{preview || '—'}</span>
            </div>
        </div>
    );
}

// Display-only, same as Settings.jsx's INSTRUMENT_LABELS — the stored sound value
// and every audio path stay "Guitar"; this just clarifies the pill's label now
// that Harpejji and Bass Guitar sit alongside it.
const SOUND_LABELS = { EP: 'Rhodes', Guitar: 'Acoustic Guitar', Saxophone: 'Tenor Saxophone' };

// Same families as the member-facing sound pickers (QuizCategory.jsx's
// SOUND_CATEGORIES, Settings.jsx's INSTRUMENT_GROUPS) — every category and
// instrument shows here regardless of what's built for this quiz (see
// builtSounds in QuestionsPanel, which greys out exactly the unbuilt ones from
// this quiz's own real data, not a hardcoded list).
const SOUND_CATEGORIES = [
    { name: 'Piano', sounds: ['Piano', 'EP', 'Upright Piano', 'Organ'] },
    { name: 'Guitar', sounds: ['Guitar', 'Electric Guitar', 'Harpejji', 'Bass Guitar'] },
    { name: 'Strings', sounds: ['Violin', 'Strings', 'Cello', 'Upright Bass'] },
    { name: 'Brass', sounds: ['Saxophone', 'Trombone', 'Tuba', 'Trumpet'] },
    { name: 'Vocals', sounds: ['Voice', 'Audience Choir', 'Ensemble Choir', 'Sustained Choir', 'Bulgarian Choir'] },
    { name: 'Percussion', sounds: ['Marimba', 'Steel Drum', 'Celesta', 'Vibraphone'] },
    { name: 'Woodwinds', sounds: ['Flute', 'Clarinet', 'Oboe', 'Bassoon'] },
];

function QuestionsPanel({ quiz }) {
    const [activeSound, setActiveSound] = useState('Piano');
    const [activeCategory, setActiveCategory] = useState(
        SOUND_CATEGORIES.find((category) => category.sounds.includes('Piano'))?.name ?? null
    );
    // Once the question pool has generated audio attached (the rendering pipeline's
    // job), manually uploading one file per question is no longer how content gets
    // added here — hide that form rather than leaving it sitting there unused.
    const hasGeneratedAudio = quiz.questions.some((question) => question.audio_variants.length > 0);
    // Derived from this quiz's actual attached audio, not a hand-maintained list —
    // a hardcoded SOUNDS array here kept going stale every time a new instrument
    // was generated (Bass Guitar and Strings both sat grayed-out after being
    // fully built, because this array wasn't updated alongside them). This can't
    // go stale the same way.
    const builtSounds = new Set(quiz.questions.flatMap((question) => question.audio_variants.map((variant) => variant.sound)));
    const preset = presetFor(quiz.category, quiz.title);
    const isBulkKey = quiz.title === 'Find the Key #1' || quiz.title === 'Find the Key #2';
    const lengthConfig = MELODIC_DICTATION_LENGTHS[quiz.title];
    const isSequence = Boolean(lengthConfig);
    const lengthOptions = Array.isArray(lengthConfig) ? lengthConfig : null;
    const [sequenceLength, setSequenceLength] = useState(lengthOptions ? lengthOptions[0] : lengthConfig);
    const [sequence, setSequence] = useState([]);

    const compound = compoundFor(quiz.category, quiz.title);
    const isCompound = Boolean(compound);
    const [quality, setQuality] = useState(null);
    const [inversion, setInversion] = useState(null);

    const multiOptions = multiFor(quiz.category, quiz.title);
    const isMulti = Boolean(multiOptions);
    const [selectedExtensions, setSelectedExtensions] = useState([]);

    const isNaming = quiz.title === 'Chord naming';
    const [namingQuality, setNamingQuality] = useState(null);
    const [namingDegrees, setNamingDegrees] = useState([]);
    const [namingExclude, setNamingExclude] = useState(null);
    const namedChord = isNaming && namingQuality ? buildNamedChord(namingQuality, namingDegrees, namingExclude) : '';

    const isChordTone = quiz.title === 'Chord Tone Recognition #1' || quiz.title === 'Chord Tone Recognition #2';
    const [chordQuality, setChordQuality] = useState(null);

    const progressionConfig = progressionFor(quiz.category, quiz.title);
    const isProgression = Boolean(progressionConfig);
    const emptySlots = (config) => Array.from({ length: config?.length ?? 0 }, () => ({ degree: null, quality: null, extensions: [] }));
    const [progressionSlots, setProgressionSlots] = useState(() => emptySlots(progressionConfig));
    const progressionPreview = isProgression ? composeProgression(progressionConfig, progressionSlots) : '';

    const { data, setData, post, processing, errors, reset } = useForm({
        choices: preset,
        correct: 0,
        quality: null,
        audio: null,
    });

    useEffect(() => {
        if (!isChordTone) return;
        setData('quality', chordQuality);
    }, [chordQuality]);

    useEffect(() => {
        if (!isProgression) return;
        setData('choices', progressionPreview ? [progressionPreview] : []);
        setData('correct', 0);
    }, [progressionPreview]);

    useEffect(() => {
        if (!isSequence) return;
        setData('choices', sequence.length ? [sequence.join(' ')] : []);
        setData('correct', 0);
    }, [sequence]);

    useEffect(() => {
        if (!isCompound) return;
        setData('choices', quality && inversion ? [`${quality} · ${inversion}`] : []);
        setData('correct', 0);
    }, [quality, inversion]);

    useEffect(() => {
        if (!isMulti) return;
        setData('choices', selectedExtensions.length ? [selectedExtensions.join(', ')] : []);
        setData('correct', 0);
    }, [selectedExtensions]);

    useEffect(() => {
        if (!isNaming) return;
        setData('choices', namedChord ? [namedChord] : []);
        setData('correct', 0);
    }, [namedChord]);

    const chooseLength = (value) => {
        setSequenceLength(value);
        setSequence([]);
    };

    const toggleExtension = (option) => {
        setSelectedExtensions((prev) => (prev.includes(option) ? prev.filter((item) => item !== option) : [...prev, option]));
    };

    const toggleNamingDegree = (option) => {
        setNamingDegrees((prev) => (prev.includes(option) ? prev.filter((item) => item !== option) : [...prev, option]));
    };

    const changeProgressionSlot = (index, field, value) => {
        setProgressionSlots((prev) => prev.map((slot, i) => (i === index ? { ...slot, [field]: value } : slot)));
    };

    const addQuestion = () => {
        post(route('admin.audio-quiz.questions.store', quiz.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('audio');
                setData('correct', 0);
                setSequence([]);
                if (lengthOptions) setSequenceLength(lengthOptions[0]);
                setQuality(null);
                setInversion(null);
                setSelectedExtensions([]);
                setNamingQuality(null);
                setNamingDegrees([]);
                setNamingExclude(null);
                setChordQuality(null);
                setProgressionSlots(emptySlots(progressionConfig));
            },
        });
    };

    const deleteQuestion = (id) => {
        if (confirm('Delete this question? This also removes its audio.')) {
            router.delete(route('admin.audio-quiz.questions.destroy', id), { preserveScroll: true });
        }
    };

    return (
        <div className="mt-5 space-y-5">
            {/* Manual question entry is retired — audio-quiz questions and their audio are
                generated and attached directly (see scripts/audio/), not uploaded here. */}

            {quiz.questions.length > 0 && (
                <div className="rounded-2xl border border-black/5 bg-white p-5">
                    <div className="flex items-center justify-between gap-3">
                        <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">
                            {quiz.questions.length} question{quiz.questions.length === 1 ? '' : 's'} configured
                        </p>
                    </div>

                    {/* Every planned category and instrument shows here, not just the ones
                        with generated audio — an instrument with nothing yet renders as a
                        dimmer pill and falls through to the "audio not generated yet"
                        message below, so this list doubles as a what's-left checklist.
                        Grouped in its own labeled panel (Category above Sound) so the two
                        rows read as one filter, not two unrelated pill lists. */}
                    <div className="mt-3 rounded-xl border border-black/5 bg-neutral-50 p-3.5">
                        <div>
                            <p className="mb-1.5 text-[10px] font-bold uppercase tracking-wide text-neutral-400">Category</p>
                            <div className="flex flex-wrap gap-1.5">
                                {SOUND_CATEGORIES.map((category) => (
                                    <button
                                        key={category.name}
                                        type="button"
                                        onClick={() => {
                                            setActiveCategory(category.name);
                                            if (!category.sounds.includes(activeSound)) setActiveSound(category.sounds[0]);
                                        }}
                                        className={`rounded-full px-3 py-1.5 text-xs font-semibold transition-colors duration-150 ${
                                            activeCategory === category.name
                                                ? 'bg-neutral-900 text-white'
                                                : 'bg-white text-neutral-600 ring-1 ring-inset ring-black/10 hover:bg-neutral-100'
                                        }`}
                                    >
                                        {category.name}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="mt-3 border-t border-black/5 pt-3">
                            <p className="mb-1.5 text-[10px] font-bold uppercase tracking-wide text-neutral-400">Sound</p>
                            <div className="flex flex-wrap gap-1.5">
                                {(SOUND_CATEGORIES.find((category) => category.name === activeCategory)?.sounds ?? []).map((sound) => {
                                    const built = builtSounds.has(sound);
                                    return (
                                        <button
                                            key={sound}
                                            type="button"
                                            onClick={() => setActiveSound(sound)}
                                            className={`rounded-full px-3 py-1.5 text-xs font-semibold transition-colors duration-150 ${
                                                activeSound === sound
                                                    ? 'bg-brand text-white'
                                                    : built
                                                      ? 'bg-white text-neutral-600 ring-1 ring-inset ring-black/10 hover:bg-neutral-100'
                                                      : 'bg-white text-neutral-300 ring-1 ring-inset ring-black/5 hover:bg-neutral-50'
                                            }`}
                                        >
                                            {SOUND_LABELS[sound] ?? sound}
                                            {!built && <span className="ml-1 text-neutral-300">·</span>}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    <ul className="mt-3 divide-y divide-black/5">
                        {quiz.questions.map((question) => {
                            const variant = question.audio_variants.find((item) => item.sound === activeSound);
                            return (
                                <li key={question.id} className="flex items-start justify-between gap-4 py-3">
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-semibold text-neutral-900">
                                            {question.prompt}
                                            {question.sound && <span className="ml-2 rounded-full bg-neutral-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">{question.sound}</span>}
                                        </p>
                                        <span className="mt-1 inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                            Answer: {question.answer}
                                        </span>
                                        {question.audio_url && <audio controls src={question.audio_url} className="mt-2 block h-8 w-full max-w-xs" />}
                                        <div className="mt-2">
                                            {variant ? (
                                                <audio controls src={variant.url} className="h-8 w-full max-w-xs" />
                                            ) : (
                                                <p className="text-xs text-neutral-400">{SOUND_LABELS[activeSound] ?? activeSound} audio not generated yet.</p>
                                            )}
                                        </div>
                                    </div>
                                    <button type="button" onClick={() => deleteQuestion(question.id)} className="shrink-0 text-neutral-300 hover:text-brand">
                                        <TrashIcon className="h-4 w-4" />
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            )}
        </div>
    );
}

export default function Editor({ category, categorySlug, categories, difficulties, lessons, selectedSlug, quiz }) {
    return (
        <AppShell eyebrow="Audio Quiz" title={category} intro="Choose a lesson to manage its questions.">
            <Head title={`${category} — Audio Quiz`} />

            <div className="mb-4 flex items-center justify-between">
                <Link href={route('admin.audio-quiz.index')} className="text-sm font-semibold text-neutral-500 hover:text-neutral-900">
                    &larr; Audio Quiz
                </Link>
                <select
                    value={categorySlug}
                    onChange={(e) => router.visit(route('admin.audio-quiz.show', e.target.value))}
                    className="rounded-full border border-neutral-200 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 focus:border-neutral-900 focus:outline-none"
                >
                    {categories.map((c) => (
                        <option key={c.slug} value={c.slug}>
                            {c.name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="flex flex-col gap-5 lg:flex-row">
                <LessonList category={category} categorySlug={categorySlug} lessons={lessons} selectedSlug={selectedSlug} />

                <div className="min-w-0 flex-1">
                    {!selectedSlug && (
                        <div className="flex flex-col items-center rounded-2xl border border-dashed border-neutral-300 bg-white px-5 py-16 text-center">
                            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                                <QuizIcon className="h-5 w-5" />
                            </span>
                            <p className="mt-3 text-sm text-neutral-500">Select a lesson from the list to edit it.</p>
                        </div>
                    )}

                    {quiz && (
                        <>
                            <div className="mb-4">
                                <h2 className="text-xl font-bold text-neutral-900">{quiz.title}</h2>
                                <p className="text-sm text-neutral-500">{quiz.questions.length} question(s) configured</p>
                            </div>

                            <SettingsTabs key={`settings-${quiz.id}`} quiz={quiz} difficulties={difficulties} />
                            <QuestionsPanel key={`questions-${quiz.id}`} quiz={quiz} />
                        </>
                    )}
                </div>
            </div>
        </AppShell>
    );
}
