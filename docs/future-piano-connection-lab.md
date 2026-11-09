# Piano Connection Lab — developing product brief

Status: planning only, for a separate piano website and another Codex account. No simulator implementation is authorized yet. The owner will provide the musical scope, approved connections, replacements, substitutions, scales and restrictions incrementally. Do not treat the existing KhordQuiz palette as a complete simulator specification.

## Keyboard visualizer — confirmed requirement

The workspace must include an on-screen piano keyboard that shows:

- The actual voicing of any selected chord, including inversions, register, doubled notes and omissions if permitted.
- The suggested set of chords, one chord at a time, synchronized to playback.
- Chords from a progression entered or imported by the student.
- Original and suggested versions so students can compare them.

Highlight sounding keys, not just the chord's root or an abstract pitch-class list. Distinguish the bass and highest melody note with labels as well as colour. Keep note names and octave placement consistent with the sound. When possible, show shared notes and small movements between successive chords.

Recommended controls to review later: select a chord card to inspect its keyboard shape, previous/next chord, play/pause, replay/loop a connection, and a clear original-versus-variation switch. The keyboard should remain readable on desktop, tablet and mobile, with a defined register-navigation approach rather than tiny unusable keys. Consider an accompanying text list of notes for accessibility.

Do not connect audio, MIDI devices, imports or backend services from this planning note alone.

## Existing concept to preserve

- Two entry points: explore a start-to-destination connection, or edit a song progression.
- Separate tools for inserting passing chords, replacing a chord, changing a voicing, exploring scales and transforming a passage.
- Possible techniques include chromatic approaches, secondary dominants, modal interchange, tritone substitution and negative harmony. Each needs its own approved rules and contexts; they are not interchangeable operations.
- Allow multiple compatible quality choices along routes such as 7–3–6. Choices must depend on neighbouring chords, melody and intended destination.
- Explain why each suggestion works: simple listening guidance first, actual note movements next, and optional advanced theory. Explanations must match the actual voicing.
- Preserve or explicitly unlock the melody, bass, destination, home key and available beats. Do not silently change the song's length or destination.
- Use reviewed musical rules, clear exceptions, original/variation comparison, undo and progressive disclosure. Do not present AI-generated claims as guaranteed musical correctness.

## Next step

Collect one technique family at a time. For each rule obtain: home key/style, starting context, target, permitted route/replacement, variants, actual voicing examples, melody/bass conditions, timing, why it works, when not to use it, student level and approval status. Calculate transpositions and note checks later; the owner need not dictate every key manually.

## Musical ideas beyond block chords — owner’s developing direction

Added 6 November 2026. Planning only; details and musical rules will be supplied incrementally.

The lab should not be limited to displaying and playing complete block chords. The owner wants to explore melodic lines, individual-note patterns and alternative ways of expressing a harmonic concept.

### Triad-pair exploration

- A selected harmonic situation may offer several approved pairs of triads as alternative sounds.
- Explain which two triads are being used and how they relate to the selected harmony, bass and tonal context.
- Do not assume every pair is a literal replacement for the entire original chord or contains every defining chord tone. State what it preserves, adds or omits.
- Distinguish two triads sounded together from alternating triads, broken-chord patterns and melodic lines built from their notes. The owner has not chosen the permitted treatments yet.
- Allow comparison of approved pair choices over the same underlying musical context.
- Specific triad pairs, rhythms, registers, patterns, resolutions, style labels and restrictions are not yet supplied. Do not invent an approved library from this note.

### Melodic lines and note-based demonstrations

- A chord or connection may be demonstrated as a block chord, an arpeggiated pattern, a melodic phrase or another owner-approved treatment.
- Keep the underlying harmony and the surface treatment separate. A different note pattern is not automatically a different chord progression.
- Future controls could compare treatments while retaining the same harmony, tempo and phrase location. These controls are suggestions to review, not finalized requirements.
- The keyboard visualizer should be able to show individual notes in playback order, held notes and their relationship to the current harmony, in addition to full-chord shapes.
- “Why it works” explanations should account for the actual pattern, note placement and resolution—not just the chord names.

### MIDI as an authoring/reference format

The owner may provide MIDI files to communicate musical examples accurately. Treat them as source demonstrations for review, not automatic authorization to build public file upload or import every example into the product.

For each future MIDI example, collect the intended key, chord/bass context, technique, relevant bars or time range, musical purpose and approval status. Identify which parts represent melody, bass, accompaniment or the two triads.

MIDI can communicate pitches, note timing, duration, velocity and track/channel structure when present. It does not reliably supply the intended harmonic analysis, enharmonic spelling, pedagogical purpose, hand assignment or a recording of the owner’s exact sound. Ask for those annotations or review interpretations explicitly.

Preserve the original MIDI. Any extracted notes, inferred chords, proposed spelling or rhythmic cleanup should be reviewable before becoming an approved rule/example. Exact import formats, handling of pedal/overlapping notes and playback behaviour remain future decisions.

### Working approach

Keep collecting ideas in this document without treating them as implementation instructions. Develop one musical concept and its examples at a time; the owner will later supply the detailed scope, connections and constraints. No lab code, MIDI importer, audio service or public upload feature has been added.

## Melody input alongside progression input — owner’s requirement

The lab should accept an actual melodic line, not only chord progressions. This may be the melody of a known song, an unfamiliar song, or a freely composed line. Do not rely on recognizing a song title to supply its melody; use the version the student provides and confirms.

The input method is explicitly undecided. MIDI, note entry on the visual keyboard, notation entry or other approaches can be evaluated later; none is authorized for implementation by this note.

Store melody as a separate musical part, with actual pitches/register, durations, rests and timing relative to the progression. Chord symbols alone cannot supply this information. Support discussion of melody-first composition as well as adding a melody to an existing progression; whether melody-only input should trigger automatic harmonization remains an open product decision.

Use the provided melody to evaluate passing chords, substitutions, triad-pair treatments, voicings and scale suggestions in context. Account for held notes across chord changes and their placement in the phrase. A non-chord melody note is not automatically an error; its rhythmic role and intended resolution matter.

Preserve the original melody by default when the learner requests accompaniment/reharmonization. Any proposed change to the tune should be explicit, separately previewable and reversible. If melody is missing, state that compatibility with the tune has not been checked; do not imply chord-only suggestions are guaranteed to fit it.

Later decisions: how melody is entered or imported, how it is aligned to bars/beats and chords, how the learner confirms/corrects it, and which melody changes are permitted. Planning only; no input or analysis feature has been built.

## Confirmed musical scope and working arrangement — owner update

This section supersedes earlier suggestions where they conflict. Current phase: **collect, clarify and organize the owner’s musical data only.** The owner will explicitly request UI/UX design and implementation later. No simulator UI, engine, public upload flow or audio connection is authorized at this stage.

| Area | Confirmed direction |
| --- | --- |
| Home keys | Author musical material in C major; the system will transpose approved material to other major keys. |
| Style | Gospel only initially. Other styles may be added later. |
| Student levels | Beginner, intermediate and advanced. The owner will assign each lesson, structure or rule; do not infer assignments. |
| Chord vocabulary | All chord types may be considered. Actual available vocabulary comes from the owner’s supplied structures, including separate left-hand and right-hand parts. Do not interpret “all types” as permission to invent unreviewed choices. |
| Tonal freedom | Stay in the original key. Chromatic approaches may occur within the owner’s vocabulary, but must not establish a new home key. |
| Phrase lengths | No fixed musical limit. Only attainable connections supported by the supplied data may be suggested. Earlier one/two-passing-chord proposals are not a hard limit. A later implementation still needs explicit handling of cycles and practical playback/navigation; these are engineering decisions, not a new musical restriction. |
| Destinations | Preserve the original destination by default. Replace it only when the user explicitly chooses chord substitution. |
| Musical priorities | Derive the applicable priorities and exceptions from each supplied lesson and example. No universal weighting, ranking or voicing policy is approved yet. |

### How the owner will supply the vocabulary

The owner will provide lessons or structures incrementally: skill level, harmonic context, chord(s), options, connections, hand arrangements, explanations and restrictions as relevant to each item. MIDI files may communicate a specific preferred way of playing. Do not force every lesson into a single passing-chord pattern: material can describe harmonies, left/right-hand structures, substitutions, triad pairs, melodic lines or other concepts.

For each contribution, preserve the original musical intention, record the clarified interpretation, distinguish confirmed details from open questions and map it into the developing library. Calculate pitch/interval details where possible, but do not silently fill musical gaps with invented rules. Ask focused questions only when interpretation affects the stored musical meaning.

Left- and right-hand labels describe the owner’s intended arrangement. Preserve their actual notes, timing, register and relationship to melody/bass where supplied; do not collapse a two-hand structure into an assumed generic chord name.

### Who will work on it

The owner now intends **this Codex conversation/account** to help collect the data and later build the piano-site feature when explicitly instructed. This supersedes the earlier expectation that another Codex account would necessarily implement it. The target remains the separate piano-site concept; do not add it to KhordQuiz automatically.

### Immediate next step

Organize contributions one by one, starting with the lesson below. No need for the owner to complete the entire earlier checklist before starting, and no UI/UX work should begin merely because enough examples have accumulated.

## Lesson 1 — harmonizing a melody with diatonic fifths and sixths

Owner explanation recorded 6 November 2026. **Student level: Beginner**, explicitly confirmed by the owner for this harmonic-accompaniment concept. Data collection only. The local video `Melodic Triads.mkv` is a source reference, but this record comes from the owner’s written explanation, not a complete transcription of the video.

### Musical intention

Keep the song’s melody as the highest note. Add a lower note a diatonic fifth or sixth below it, then fill the space with a note that completes a triad. Use only C-major notes in this lesson: C, D, E, F, G, A and B. “Diatonic” means using those notes; a fifth or sixth here is not a fixed number of semitones for every melody note.

The sixth approach is the main treatment. The fifth approach is an alternative the learner can choose. The learner can compare different harmonizations of the same unchanged melody, including interchanging the approaches. No preferred automatic ranking, rhythm, hand division or register has been specified.

The owner initially said “make a major triad,” but the supplied examples explicitly include minor and diminished triads. Record the intention as completing a **diatonic triad**, not restricting this lesson to major quality.

### Confirmed sixth examples

All note lists below run from lowest to highest. Octave numbers are omitted; the written pairs describe the intended ascending sixth, not arbitrary pitch-class placement.

| Melody on top | Outer notes | Inserted note | Completed voicing | Chord |
| --- | --- | --- | --- | --- |
| La (A) | Do–La (C–A) | Mi (E) | C–E–A | A minor |
| La (A) | Do–La (C–A) | Fa (F) | C–F–A | F major |
| Do (C) | Mi–Do (E–C) | So (G) | E–G–C | C major |
| Do (C) | Mi–Do (E–C) | La (A) | E–A–C | A minor |
| Re (D) | Fa–Re (F–D) | La (A) | F–A–D | D minor |
| Re (D) | Fa–Re (F–D) | Ti (B) | F–B–D | B diminished |

For the melody La–Do–Ti, the owner supplied outer pairs C–A, E–C, D–B. The middle pair may instead use F–C, a fifth. This illustrates choosing an interval treatment independently for each melody note.

For fixed outer notes a sixth apart, this lesson offers two diatonic triad completions. For fixed outer notes a fifth apart, it offers one: insert the diatonic third above the lower note. This statement concerns completing the three-note triad inside those fixed endpoints, not every possible harmony or register arrangement.

### Melody Mi–Re–Do–La: examples and confirmed Mi choices

The owner proposed C major or A minor for melody Mi, and D minor or B diminished for melody Re. Preserve those quality choices. Exact voicings explicitly supplied were G–C–E for C major, F–A–D for D minor and F–B–D for B diminished.

The owner subsequently confirmed that **Mi offers all three choices**, with E remaining the highest note:

| Approach | Completed voicing, lowest to highest | Chord |
| --- | --- | --- |
| Sixth: G–E | G–C–E | C major |
| Sixth: G–E | G–B–E | E minor |
| Fifth: A–E | A–C–E | A minor |

E minor is approved for this lesson. A–C–E is the fifth option, not a sixth. These are alternative harmonizations of the same melody note, not three chords that must be played in sequence.

Do not infer the remaining Mi–Re–Do–La voicings, a full seven-note option library, or playback details as approved from this partial demonstration. The melody stays on top throughout; smooth movement between chosen shapes should be reviewed against the owner’s examples when exact registers are supplied.

## Lesson 2 — suspended and related shapes resolving to major chords

Owner contribution recorded 6 November 2026. **Student level: Beginner**, explicitly confirmed by the owner for this suspended-chord concept. Planning/data only. This concept uses full two-hand chords, mainly sus4 sounds, followed by a major-chord resolution. The approved major destinations in C major are **I (C major), IV (F major) and V (G major)**. The owner described root-position destinations and also explicitly allowed different inversions; preserve both possibilities without fixing a single inversion for every example.

### Owner’s C-major examples

Notes are listed in the supplied order within each hand. Exact octaves, rhythms and durations have not been supplied. Do not collapse the hands into one generic triad or add an independent bass part.

| Approach | Left hand before resolution | Right hand before resolution | Destination |
| --- | --- | --- | --- |
| F-major shape over C with a major right hand | C–F–A | C–E–G–C | C major |
| F-major shape over C with a sus4 right hand | C–F–A | C–F–G–C | C major |
| Sus4 left hand with an F-major shape over C in the right | C–F–G | C–F–A–C | C major |

The supplied full C-major destination is **left hand C–E–G; right hand C–E–G–C**. Other inversions may be used as the owner supplies or approves their exact arrangements. Do not assume every voice moves in parallel or replace these examples with a single F-to-E motion: some supplied shapes also contain A and/or an already sounding E.

### Other destinations and inversions

- Apply the same concept to **F major**. The owner explicitly mentioned a **B-flat-major approach** or **F sus4**, resolving to F major. Exact two-hand shapes remain to be supplied; do not assume an extra non-diatonic B-flat chord is approved outside this particular approach.
- Apply the concept to **G major** as well. G sus4 contains G–C–D and resolves to G major, G–B–D. The equivalent two-hand variants have not yet been dictated; any derived voicings must remain distinguishable from owner-supplied examples.
- Offer different inversions rather than presenting one fixed shape as the only way to play the connection. Keep adjacent chords close and easy to follow, consistent with the owner’s broader voicing direction. Exact register and voice movement need review before playback is implemented.

### Teaching language and naming

Keep the learner-facing instruction simple: **“Play this shape, then resolve to the major chord.”** Show each hand on the keyboard and let the learner compare the approach with its destination.

Use **sus4** accurately for a chord whose third is replaced by the fourth. C–F–A is an F-major inversion (F/C), not a plain C sus4 triad. The owner nevertheless approves it as part of this resolution concept, including the mixed two-hand examples above. Preserve the actual notes and explain their sound simply rather than relabelling every combined shape “sus4.”

This is a contextual addition to the earlier diatonic-triad lesson: B-flat is specifically allowed in the stated approach to F major. It does not authorize unrestricted chromatic harmonization or a change of home key. No UI, audio, MIDI processing or suggestion engine has been built from this contribution.

## Lesson 3 — simple passing chords

**Student level: Beginner**, explicitly assigned by the owner. Planning/data collection only. Suggested learner-facing title: **Simple Passing Chords**. These are short major/minor triad connections that add movement before a destination while retaining C major as the home key. Do not describe every connection as a secondary dominant; this lesson includes other approaches as well.

### Confirmed connections in C major

| Passing chord | Destination | Degrees in C major |
| --- | --- | --- |
| D major | G major | II major → V major |
| D major | F major | II major → IV major |
| E major | A minor | III major → vi minor |
| G minor | F major | v minor → IV major |
| E major | F major | III major → IV major |
| A major | D minor | VI major → ii minor |
| B-flat major | F major | ♭VII major → IV major |

Each row is an approved local connection, not permission to insert that passing chord anywhere in any song. Preserve the melody and consider neighbouring harmony when applying it. Use close voicings consistent with the owner’s existing direction; exact hand assignments, register, timing and preferred inversions for this lesson remain to be supplied. Do not introduce seventh chords or other extensions from these triad examples alone.

### First example: clarification pending

The owner said **“A major pass to G major”**, followed by **“the 2 major pass to the 5 major.”** In the established C-major context, II major → V major is **D major → G major**, whereas **A major → G major** is VI major → V major. The table records the explicitly stated degree connection. Keep the literal A-major → G-major example pending clarification rather than silently approving it as an additional route or labelling A major as II in C.
