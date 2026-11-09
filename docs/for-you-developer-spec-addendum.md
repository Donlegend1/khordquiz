# For You — developer spec addendum

Target document: https://claude.ai/artifact/5HkjroBbvWji44JUrkHTwE

Confirmed requirements, 5 November 2026. This addendum has not been merged into the external Claude artifact.

## Confirmed initial skill levels

- **Beginner:** Relative Pitch, Melodic Dictation, Intervals and Basic Triads.
- **Intermediate:** Add 9 through 13th Degree Chords, including all secondary chord categories, plus Chord Tone Recognition.
- **Advanced:** Major Chord Extensions through Scales, including Minor and Dominant Chord Extensions, Chord Naming, Chord Progressions, Modal Harmony and Modal Voicings.

Apply these assignments to the current quizzes. Later individual changes in admin still determine the quiz's learning path and displayed skill level.

## Learning paths and Up next

- Build separate Beginner, Intermediate and Advanced paths using each quiz's difficulty assigned in admin. Do not assign difficulty solely by category or hardcode it by quiz name.
- Within each difficulty, follow the established category and lesson order.
- Up next shows the first unfinished quiz in that path. Its Start quiz button opens the selected lesson's Quick Guide and sound-selection page, where the user can start the quiz. Do not open the removed standalone preview page.
- Finishing all questions counts as completion regardless of score. A zero score still advances the path.
- On returning to For You after completion, promote the next unfinished lesson into Up next and refill Coming up with the next nine unfinished lessons, or fewer if fewer remain.
- For a new path, Coming up displays lessons 2–10. After completing lesson 1, Up next displays lesson 2 and Coming up displays lessons 3–11. Skip lessons already completed out of order and retain their original path positions.
- Retrying a previously completed quiz must not erase its earlier completion or put it back into Up next. Check completion history as well as the current attempt status.
- Completion and recommendations must be scoped to the signed-in user.

## Practise again — separate state for each skill level

Place Practise again below Up next and above Coming up, matching the approved mobile design.

For the selected skill level:

1. Find this user's most recently **completed** quiz in that difficulty. Consider completed retries as well as first completions.
2. If no completed quiz exists in that difficulty, hide Practise again.
3. If that latest result is **below 40%**, show Practise again for that quiz only.
4. If that latest result is **40% or higher**, hide the entire section. Exactly 40% does not qualify.
5. A newer completed quiz in the same difficulty replaces the previous result. Never fall back to an older low score after a newer result reaches 40% or higher.
6. Starting, resuming or abandoning an unfinished quiz does not replace the last completed result.
7. Completing a quiz in another difficulty does not replace or hide this difficulty's result. Switching tabs displays the independently calculated state for that tab.

Use the quiz's admin-assigned difficulty for grouping, consistent with its learning path. Difficulty changes must be reflected consistently in both places when real data is connected.

The section contains:

- The heading **Practise again** and the supporting label **From your results**.
- A **Review your mistakes** row showing the number of incorrect answers from the qualifying attempt. It opens review for that attempt.
- One quiz row with its title, category, score percentage and navigation arrow. It opens that lesson's Quick Guide page to practise again.

Calculate the threshold from the completed attempt's correct answers divided by its total questions, before display rounding. Use completion time to select the latest result, with a stable record ID as the tie-breaker. Do not use average scores, best scores, lowest scores or the latest unfinished attempt.

## Acceptance examples

Assume the named quizzes are assigned to the difficulties shown.

| Event | Beginner tab | Intermediate tab | Advanced tab |
| --- | --- | --- | --- |
| Finish Beginner Find the Note with 32% | Show Find the Note; advance Up next | Unchanged | Unchanged |
| Finish Beginner Relative Tone Pitch #1 with 28% | Replace with Relative Tone Pitch #1; advance Up next | Unchanged | Unchanged |
| Finish the next Beginner quiz with 40% | Hide Practise again; advance Up next | Unchanged | Unchanged |
| Finish an Intermediate quiz with 36% | Remains hidden | Show that Intermediate quiz | Unchanged |
| Finish an Advanced quiz with 24% | Remains hidden | Keep the Intermediate result | Show that Advanced quiz |
| Finish another Advanced quiz with 80% | Remains hidden | Keep the Intermediate result | Hide Practise again |

A completed retry affects Practise again according to its new score, but does not undo the quiz's earlier completion in the learning path.

## Current implementation boundary

- Web Up next uses real completion data, including archived completions preserved when retrying. Dashboard and Progress now also use shared completion history, so retrying does not erase completed counts, scores or achievements.
- Web Practise again now uses real completed results independently per skill level. The raw below-40% threshold, latest-result replacement, stable completion ordering, archived retries and current admin difficulty are implemented. Review links open the exact saved result; historical records without individual answers show an honest summary-only notice.
- Mobile remains design-only. It uses local completion state and a bundled copy of current admin difficulty labels. No automatic admin synchronization or audio connection is authorized by this addendum.


## Progress/history implementation update — 5 November 2026

- `App\Support\QuizHistory` merges current completed attempts with archived sessions without counting the same run twice. Quiz completion counts are distinct quizzes; accuracy/recent results retain completed runs. Stored session totals remain authoritative even if quiz length later changes.
- Submission events persist in `quiz_answers` using a unique `web_answer_id`. Goal and week counts use actual `answered_at` timestamps, and survive retry/cancellation. Existing retained answer rows were backfilled with their original timestamps; deleted historical timestamps cannot be recreated.
- Completed answer reviews and the selected sound are saved in `quiz_sessions.review_answers` / `sound`. The authenticated `quiz.history-review` route enforces ownership. Stable answer IDs distinguish two completions of the same quiz in the same second.
- Reset Progress clears attempts, archived sessions and persistent answer events for only that user.
- Schema updates: `2026_11_05_215504_track_web_answers_in_persistent_quiz_history.php` and `2026_11_05_220613_preserve_completed_quiz_reviews.php`. Both have been applied locally.
- Mobile remains design-only. Its Chord Naming odd-level Quick Guides and bundled illustrated PDF now use C; no mobile audio/API connection was added.

## Attempt history and question reports — 6 November 2026

### Approved experience

- Progress shows the five most recent completed attempts, including retries. A full-width outlined **View attempt history** button sits beneath the list. Results also link to history; answer review places an outlined history button alongside **Back to results**.
- Full history includes every completed run, newest first, with a stable tie-break for runs finished at the same time. Each row shows quiz title, category, short local date/time, selected instrument, percentage and correct/total. Preserve original totals and instrument when a quiz or user preference changes.
- Use 20 attempts per page. Show the displayed range, **Previous**, **Page X of Y**, **Next**; disable unavailable directions and hide pagination for a single page. Opening another page starts at the top. Only load the authenticated member's history.
- **Review answers** opens that exact completed run, not the latest retry. Preserve question prompt, question ID, chosen answer, correct answer, position, answer submission timestamp, instrument and review presentation/audio metadata at completion, before any retry resets the active run. Retain separate runs even when completed in the same second.
- Older summary-only results must say the individual answers are unavailable. Never invent historical answers or claim sample answers are real.
- The report action reads **Report a problem with this question**, uses readable sky blue, and sits below Submit/Next during practice and below Previous/Next during review. Keep it available for all quiz categories.
- Web expands the report form and scrolls it into view, focusing the reason field. Mobile opens an in-app bottom sheet immediately, with a scrollable body, keyboard-aware layout and explicit Close/Cancel controls.
- Reasons: **Audio does not play**, **Audio and answer do not match**, **Instructions are unclear**, **Something else**. Optional message, maximum 2,000 characters. Include authenticated user, quiz, exact question, selected instrument and submission time automatically. Archived review reports must use the archived instrument/question, not current settings.
- On success show a clear confirmation; on failure retain the entered text and allow retry. Do not interrupt playback/answer state merely by opening or cancelling a report. Validate membership/ownership server-side, validate reason/message and rate-limit submissions. Never trust a client-supplied instrument as the authoritative source.
- Admin has **Question reports** with the saved question context, reason, message, time and Open/Resolved state, plus Mark resolved/Reopen actions. Members cannot view other users' reports or admin controls.

### Implemented web

- `GET /attempt-history` (`quiz.history`): authenticated history list. `GET /quiz/history/{quizSession}/review` opens owner-only archived review.
- `POST /quiz/play/{quiz:slug}/report` (`quiz.report`): validates question against the member's saved attempt or owned archived session; throttle 10/minute. Payload: `question_id`, optional `session_id`, `reason`, optional `message`. Instrument and prompt are derived on the server.
- `GET /admin/question-reports` and `PATCH /admin/question-reports/{questionReport}` require admin access. Update accepts `status`: `open` or `resolved`.
- New table: `question_reports`; migration `2026_11_06_075212_create_question_reports_table.php`, applied locally. Completed reviews already use `quiz_sessions.review_answers` and `sound`; individual snapshots now include `question_id` and `answered_at`.
- Temporary history and Strongest-card sample data have been removed at the user’s request. Web displays real saved results only; `?sample=1` no longer creates a preview. No real attempt data was deleted.

### Mobile implementation boundary / work for the integrating developer

**Mobile is design only. Do not interpret the preview as a working backend integration.** No report submission, account/history API, persistence, or audio connection was added.

- `app/attempt-history.tsx`: design-only empty state; the temporary 100-row fixture and example reviews have been removed. Implement authenticated history, true per-attempt review and the approved 20-per-page pagination on integration. Do not show fake completed results.
- `src/components/attempt-history-button.tsx`: shared outlined navigation button, added to Progress, Results and answer review.
- `src/components/question-report.tsx`: sky-blue action and bottom-sheet form, optional message, reason choices, cancel and a clearly labelled preview-confirmation state. It makes no request and never claims that a report was saved. Replace the preview action with the real submit flow only when mobile backend work is authorized.
- Report form is added below the practice action and below review Previous/Next. Reset form state when moving to another question. Review uses the recorded instrument where available.
- Existing mobile progress storage is not full reliable attempt history: it has capped answer/session arrays and lacks a durable per-attempt answer link. The integrating developer must introduce durable attempt IDs, original totals/instrument and complete answer snapshots before claiming full history. Do not infer archived answers by combining the latest answers for a quiz slug.
- Implement native endpoints/authentication and backend pagination as needed; the existing web endpoints are session/Inertia routes, not a ready-made mobile token API. Keep user ownership, validation, failure states and privacy checks equivalent to the web.

## Future challenge proposal — planning only

See [Future live challenges](future-live-challenges-proposal.md) for the owner’s two proposed modes: synchronized question race and independent-progress live matches, private/scheduled invitations, variable question counts, possible public challenges and camera discussion. It distinguishes owner intent from recommendations and unresolved rules, including race advancement, scoring and timers. **No implementation is authorized by that proposal.**
