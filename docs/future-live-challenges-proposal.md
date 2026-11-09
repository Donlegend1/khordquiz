# Future live challenges — product proposal

Recorded: 6 November 2026.

**Status: future planning only. Do not implement, connect realtime services, add timers or request camera access based on this document.** The owner will authorize development separately. This is separate from the existing challenge preview; it does not claim that multiplayer already works.

## Purpose

Let two learners challenge each other in a chess-style invitation and acceptance flow. Support two distinct ways to compete: a shared, synchronized race through questions, and independent progress through the same questions during a live session.

## Shared invitation flow — owner’s intended direction

1. Challenger chooses a quiz/lesson and invites a specific person.
2. They can propose a date/time; the recipient accepts before the match begins. Show each participant the time in their own timezone.
3. Both agree on settings before starting, including question count. Start with 25; longer matches such as 60 or 70 questions are a desired future option, not finalized preset choices.
4. Both participants are present and ready to start the live session.
5. A later expansion could offer public/open invitations that another available user can accept, rather than requiring a named friend.

Public matchmaking, camera/video and advanced length/time controls are later possibilities, not launch requirements.

## Mode A — synchronized question race

### Owner’s proposed behaviour

- Both players compete on the same current question in a shared live round.
- “One window” means one synchronized match/question state. Remote players still need their own device/view of that shared state; it does not mean screen-sharing or sharing an account.
- The first player to submit determines progression to the next question under the rule to be confirmed below.
- A wrong submitted answer costs that player **one point (−1)**, discouraging random submission.
- The intent is to test recognition speed and accuracy across the agreed number of questions.

### Unresolved scoring/advancement decision — confirm before implementation

The description mentions the first person who gets the answer and submits moving the round forward, and also a penalty for wrong submissions. These leave two possible rules:

- **First submission ends the round:** a correct first answer earns a point; a wrong first answer loses a point, and both move on immediately. This is closest to the literal “first submit moves to next” description, but someone can skip questions for the other player by intentionally answering wrongly.
- **First correct submission ends the round:** a wrong answer loses a point and uses that player’s one attempt for that round; the other player still has an opportunity to answer. If both answer wrongly, end the round and advance. This better preserves the chance to answer, but changes the immediate-advance rule.

**Recommendation, not yet approved:** first correct submission wins the round, +1 for a correct winning answer, −1 for a wrong answer, one submission per player per round. Confirm the point for a correct answer, whether total scores can be negative, what a player who did not submit receives, and whether the owner instead prefers first-submission advancement. Do not silently decide these during development.

### Other decisions needed

- What happens if neither player submits? A synchronized race needs a round-ending rule (agreed timeout, skip agreement or another explicit mechanism).
- How close submissions are ordered and how true ties are treated.
- Whether audio must finish before submitting and whether replay is allowed. Use matching timing and audio settings to avoid an instrument's attack or clip length changing the race unfairly.
- Decide how much opponent activity is shown; never reveal their answer before the round closes.

## Mode B — independent progress, live session

### Owner’s proposed behaviour

- Both accept and start the challenge together at an agreed time.
- Each has a separate interface and progresses independently. One player’s Submit does not advance the other player.
- Both receive the **same question sequence**, not just questions from the same category.
- There is no required countdown/time limit in this proposal. “Live” means both participate in the same session, not necessarily that they answer each question simultaneously.
- Once both have finished and submitted their results, reveal the comparison and who achieved the higher score.
- Allow both to review answers afterward. Exact visibility of the opponent’s individual answers remains to be decided.

### Recommendations to confirm

- Use ordinary correct-answer totals/percentages for this mode; do not automatically carry the race's −1 penalty into it.
- Do not use speed as a hidden tie-break in an untimed mode. Equal scores can be a draw unless another rule is agreed.
- Keep correct-answer feedback and full review hidden until both finish, so the early finisher cannot obtain answers while the opponent is still playing.
- Show “Waiting for your opponent” after the first finisher submits; do not falsely declare a win before both results exist.
- Agree how an unfinished match expires or becomes abandoned; no question timer does not mean storing a pending match forever.

## Fairness and reliability — requirements for later design

- Freeze quiz version, exact question order, correct-answer definitions, count and agreed rules at match start. Later admin edits must not change an ongoing or archived match.
- Choose a shared playable instrument/audio configuration or another explicitly fair setting. Confirm both devices are ready before starting the first question; preload a bounded amount of upcoming audio.
- Validate the requested question count against the playable pool. If too few distinct questions exist, reduce the count or explain a repetition policy before acceptance.
- The server must authorize participants, grade submissions and decide the round outcome. Do not trust client scores, clocks, answer keys or client-declared winners.
- Reject duplicate/stale submissions, enforce one allowed attempt per player/round where applicable, and resolve concurrent submissions atomically.
- Define disconnect, reconnection, pause, withdrawal and no-show behaviour. A late join must not silently restart or skip rounds.
- Internet latency affects a speed race; a server-controlled submission order is necessary but does not eliminate the disadvantage of a slow connection. Do not advertise the race as perfectly latency-independent.
- A −1 penalty discourages guessing but is not a guarantee against cheating. Decide answer disclosure, replay, rate limits and abuse handling before public matchmaking.
- Keep durable match and per-player answer history for comparison/review without changing solo attempt history unexpectedly. Decide whether challenges contribute to goals, streaks, achievements and leaderboards.
- Start with private invitations. Public invitations later need visibility controls, blocking/reporting, invitation rate limits and a clear accept/withdraw lifecycle.

## Cameras / seeing the other person

The owner raised optional camera access as a future idea, not a requirement.

**Recommendation:** leave cameras out of the first implementation. Avatars, player names, ready indicators and live score/progress feedback are sufficient to communicate presence. Optional voice/video could be evaluated later as a separate, explicit opt-in feature, with its own permission, mute, privacy and moderation design. Camera access is not an anti-cheating guarantee.

## Timer recommendation — not an implementation instruction

- **Normal learning quizzes:** keep untimed by default. Learners should be able to replay audio and think. Do not add countdown pressure now solely for this proposal.
- **Optional future timed practice:** consider an explicitly selected mode with timing visible before starting; keep timed and untimed comparisons distinct. Decide whether preparation/playback time counts.
- **Mode A:** settle the shared-round timeout/skip policy when designing the race, even if a visible countdown is optional. This is a match rule both players must accept.
- **Mode B:** keep untimed initially, matching the owner’s description. An elapsed-time display may later be informational, without affecting score or ranking.

## Suggested future rollout

1. Confirm unresolved rules and design private invitations/readiness.
2. Build Mode B first: identical question sequence, independent progress, both-finished result comparison and reliable reconnect/history.
3. Add Mode A with server-controlled rounds, concurrency handling and clearly agreed scoring/timing.
4. Evaluate public invitations and optional timed variants after private challenges work reliably.
5. Consider optional voice/video separately, only if there is a clear learner benefit.

These phases are recommendations, not authorized work or committed delivery dates.
