import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function ReportQuestion({ quizSlug, questionId, sessionId }) {
    const { questionReportReasons: reasons } = usePage().props;
    const [sent, setSent] = useState(false);
    const details = useRef(null);
    const reason = useRef(null);
    const scrollFrame = useRef(null);
    useEffect(() => () => cancelAnimationFrame(scrollFrame.current), []);
    function revealForm(event) {
        cancelAnimationFrame(scrollFrame.current);
        if (!event.currentTarget.open) return;
        scrollFrame.current = requestAnimationFrame(() => {
            if (!details.current?.open) return;
            reason.current?.focus({ preventScroll: true });
            details.current.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
                block: 'start',
            });
        });
    }
    const form = useForm({ question_id: questionId, session_id: sessionId ?? null, reason: reasons[0], message: '' });
    if (!questionId) return null;
    return <details ref={details} onToggle={revealForm} className="mt-4 scroll-mt-6 text-sm text-neutral-600">
        <summary className="inline-flex min-h-11 cursor-pointer items-center rounded-lg px-2 font-medium text-sky-700 hover:text-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700">⋯ Report a problem with this question</summary>
        <div className="mt-2 rounded-xl border border-neutral-200 bg-white p-4 text-left">
            {sent ? <p role="status" className="text-emerald-800">Thank you. Your report has been saved.</p> : <form onSubmit={event => { event.preventDefault(); form.post(route('quiz.report', quizSlug), { preserveScroll: true, onSuccess: () => setSent(true) }); }} className="space-y-3">
                <label className="block font-semibold">What went wrong?
                    <select ref={reason} value={form.data.reason} onChange={event => form.setData('reason', event.target.value)} className="mt-1 block w-full rounded-lg border-neutral-300 text-sm">
                        {reasons.map(reason => <option key={reason}>{reason}</option>)}
                    </select>
                </label>
                <label className="block font-semibold">Tell us more (optional)
                    <textarea maxLength={2000} rows={3} value={form.data.message} onChange={event => form.setData('message', event.target.value)} className="mt-1 block w-full rounded-lg border-neutral-300 text-sm" />
                </label>
                <p className="text-xs text-neutral-500">The quiz, question and instrument are included automatically.</p>
                {Object.values(form.errors).map(error => <p role="alert" key={error} className="text-red-700">{error}</p>)}
                <div className="flex gap-3"><button disabled={form.processing} className="min-h-11 rounded-lg bg-neutral-900 px-4 font-semibold text-white disabled:opacity-50">{form.processing ? 'Sending…' : 'Send report'}</button><button type="button" onClick={() => { details.current.open = false; details.current.querySelector('summary')?.focus({ preventScroll: true }); }} className="min-h-11 px-3">Cancel</button></div>
            </form>}
        </div>
    </details>;
}
