# Bass-first candidate handoff for Claude

Please read:

1. `docs/composition/chordal-bass-line-150-candidates.json`
2. `docs/composition/chordal-bass-line-150-candidates.md`
3. `docs/composition/chordal-bass-line-150-validation.json`
4. `docs/reference-audio-handoff.md`

## Task and boundaries

The owner requested dedicated Chordal Bass Lines candidates because simply converting melody-first voicings rejects too many patterns under the bass register cap. These are 150 NEW review candidates, not imported questions. CBL-001–075 have five events; CBL-076–150 have six. All are in a C-major frame and have a strict diatonic majority.

Audit your current question count before choosing how many to append. The local database had **115**, not 150, when these candidates were generated. If your current count is 150, add 50 to reach 200 or all 150 to reach 300. For a balanced 50-candidate selection, consider CBL-001–025 and CBL-076–100. Counts are only achieved if enough candidates pass final review; do not duplicate rows to hit a target.

Start with ten for listening review. Preserve all existing rows, IDs, attempts and history. Never run a destructive “replicate/rebuild bass lines from melody” script as part of this task: the bass collection is now intended to have independent content. Do not modify Chordal Melodies or Chordal Progressions automatically.

## Exact voicing contract

- `steps[].voicing_midi` is the **final sounding drop-2 voicing**. Its lowest note is the bass answer.
- **Do not drop-2 that final array again.**
- `closed_source_midi` is the precursor close-position chord.
- `literal_step_before_drop2` is an exact input recipe for the current `chordalBuildVoicings()` helper; apply `chordalDrop2Voicings()` once to its result.
- Every recipe was reproduced and compared against the current PHP helpers, with all final arrays matching exactly.
- `inversion` names the final sounding bass inversion, not the source close-position inversion.
- The bass answer is stored in `answer` / `bass_solfege`, derived from `min(final voicing)` at each event.

## Range and instrument rules

The existing numeric qualification cap is unshifted bass MIDI 48. Some script comments call that app C4; in scientific pitch notation it is C3. These candidates use standard MIDI throughout: C4=60, bass within 36–48, actual selected minimum 38, upper chord limit 67. Check the current agreed numeric range with the owner if your branch has changed it.

All 150 have exact recorded Piano notes at zero shift. JSON includes per-sequence `exact_unshifted_instrument_coverage`, and the validation report summarizes it. This coverage is NOT a promise that every instrument can play every sequence, nor an approval for octave-shifting the bass beyond the agreed cap. Do not assume existing per-instrument melody drop-2 transpositions preserve this bass-specific range.

Use the actual current recorded-note resolver and octave mapping. Exclude unsupported combinations. Only apply a whole-sequence octave shift if it is explicitly consistent with the owner’s instrument-specific range agreement; then recalculate/verify actual bass and answer. Never shift individual notes, omit tones, synthesize replacements or change key silently. Keep the excluded Bass Guitar/Tuba/Upright Bass policy unless the owner changes it.

## Import and validation

- Use live mixing, not pre-rendered chord clips.
- Recheck duplicates against the CURRENT database and literal pool; both may have changed since this audit.
- New harmonies were compared against 281 literal patterns and the two earlier generated melody batches. Bass pitch-class answers were compared against the 281 drop-2 patterns and 115 stored question answers. All 150 are distinct by harmony and bass pitch-class answer.
- Preserve exact note membership, drop-2 construction, inversion, rhythm/event order and lowest-note answer.
- At most two chromatic events and one fully diminished seventh event per sequence; no dense exceptions in this set.
- Make appends idempotent and back up relevant data before writing. Do not reset attempts.
- If references change, retain lazy resolution and shuffle-bag playback.
- Mobile remains design-only; no native audio/API connections.
- Musical listening review is pending. Report corrections and exclusions instead of treating computational checks as musical approval.
