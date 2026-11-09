// Shared score-color thresholds, so a given percentage reads the same
// "needs work" / "okay" / "strong" way everywhere it's shown — Dashboard,
// Progress and AttemptHistory's badge pills, and QuizResults' ring. This is
// purely a display convention; it's independent of ProgressController's
// RANKING_THRESHOLD, which decides Practice Again vs. Strongest and must
// keep its own separate value.
const LOW_MAX = 40;
const MID_MAX = 65;

export function scoreTone(score) {
    if (score < LOW_MAX) return 'bg-rose-50 text-rose-700';
    if (score < MID_MAX) return 'bg-amber-50 text-amber-700';
    return 'bg-emerald-50 text-emerald-700';
}

export function scoreStrokeColor(score) {
    if (score < LOW_MAX) return '#E00000';
    if (score < MID_MAX) return '#D9A400';
    return '#1E8E3E';
}
