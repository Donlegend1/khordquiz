<?php

// Pure pool-definition helpers for "Chordal Bass Lines" bass-first content —
// independent from "Chordal melodies"/chordal_melodies_pool.php's pool. Each
// entry is a literal recipe (via the shared chordalLit() helper, requiring
// chordal_melodies_pool.php for CHORDAL_QUALITY_INTERVALS/chordalLit()/
// chordalBuildVoicings()/chordalDrop2Voicings()) whose every step fixes an
// EXPLICIT shift, so chordalBuildVoicings() always reproduces the exact
// close-position voicing these sequences were composed with (no smoothest-
// voice-leading search ambiguity) — chordalDrop2Voicings() applied ONCE then
// gives the final sounding voicing whose lowest note is the bass answer.
//
// Source: docs/composition/chordal-bass-line-150-candidates.{md,json} and
// docs/composition/chordal-bass-line-handoff.md (added 6 Nov 2026). These are
// NEW bass-first content, not derived from (and not shared with) the Chordal
// melodies pool — do not feed this array into the melody quiz's resolve()
// branch or any "replicate bass lines from melody" script.

require_once __DIR__.'/chordal_melodies_pool.php';

function chordalBassLineLiteralProgressions(): array
{
    return [
        // CBL-001: Fa Fa So Ti Do
        [chordalLit(5, 'maj7', 2, 1, -12), chordalLit(5, 'dimMaj7', 2, 1, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-002
        [chordalLit(2, 'min7', 3, 2, -12), chordalLit(1, 'maj7b5', 0, 3, 0), chordalLit(0, 'maj7', 0, 3, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-003
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'dim7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-004
        [chordalLit(2, 'min7', 3, 2, -12), chordalLit(10, 'dom7', 0, 3, -12), chordalLit(0, 'maj7', 0, 3, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-005
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(5, 'min7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-006
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(4, 'm7b5', 3, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-007
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(2, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(8, 'minMaj7', 2, 1, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-008
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(2, 'maj', 2, 2, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-009
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(3, 'maj7', 0, 3, 0), chordalLit(2, 'min', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-010
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(5, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],

        // --- CBL-011 through CBL-075, remaining five-event sequences from
        // docs/composition/chordal-bass-line-150-candidates.{md,json}
        // (docs/composition/chordal-bass-line-handoff.md), added 6 Nov 2026.
        // Same validation as CBL-001-010: closed-position/drop2/bass/label all
        // reproduce exactly against the real engine, 0 duplicates. ---
        // CBL-011: So Si La La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(10, 'dom7', 1, 0, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-012: So La Do Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(3, 'maj7b5', 0, 3, 0), chordalLit(2, 'min7', 1, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-013: So La So Si Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(11, 'dim7', 1, 0, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-014: Ti Ti La Do Do
        [chordalLit(0, 'maj7', 1, 0, 0), chordalLit(4, 'min7', 0, 3, 0), chordalLit(3, 'dimMaj7', 0, 3, 0), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-015: La Si Ti Do Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(4, 'min7', 0, 3, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-016: Mi Fa Fi La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(7, 'min7', 1, 0, -12), chordalLit(6, 'm7b5', 2, 1, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-017: So Fa Fi La Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(5, 'maj7', 2, 1, -12), chordalLit(6, 'm7b5', 2, 1, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-018: La Fa La Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(10, 'maj', 0, 0, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-019: So La La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(10, 'maj7', 1, 0, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-020: La Li La La Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'min', 2, 2, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-021: So Fa So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7b5', 2, 1, -12), chordalLit(0, 'maj7', 0, 3, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-022: So Fi So Ti Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(0, 'dim7', 0, 3, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-023: La La So La Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(5, 'dom7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-024: So Fa So Si Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-025: Ti Do Li Do Do
        [chordalLit(0, 'maj7', 1, 0, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(10, 'dimMaj7', 2, 1, -12), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-026: Ti Li Do Ti Do
        [chordalLit(4, 'min7', 0, 3, 0), chordalLit(4, 'm7b5', 0, 3, 0), chordalLit(9, 'min', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-027: So Fi La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(3, 'min7', 3, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-028: La Mi Fa So Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(9, 'maj', 0, 0, -12), chordalLit(2, 'min', 2, 2, -12), chordalLit(7, 'dom7', 2, 1, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-029: So Si So Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-030: Do Li Do Ti Do
        [chordalLit(5, 'maj', 0, 0, 0), chordalLit(10, 'maj7', 2, 1, -12), chordalLit(5, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-031: So So La La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'dim7', 3, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-032: So Ti Do Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(11, 'dom7', 2, 1, -12), chordalLit(5, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-033: Fa Mi Fa La Do
        [chordalLit(5, 'maj7', 2, 1, -12), chordalLit(10, 'maj7b5', 0, 3, -12), chordalLit(5, 'maj7', 2, 1, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-034: La Si La La Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(9, 'min7', 2, 1, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-035: So So La Ti Do
        [chordalLit(4, 'min7', 3, 2, -12), chordalLit(4, 'm7b5', 3, 2, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-036: So Fi Fi La Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(0, 'dimMaj7', 0, 3, 0), chordalLit(0, 'dim7', 0, 3, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-037: La Li Li Do Do
        [chordalLit(2, 'min7', 0, 3, 0), chordalLit(7, 'min7', 3, 2, -12), chordalLit(0, 'dom7', 1, 0, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-038: La La Do Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'maj', 0, 0, 0), chordalLit(5, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-039: So Li Do Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'min', 2, 2, -12), chordalLit(5, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-040: So Li La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(3, 'maj7', 0, 3, 0), chordalLit(2, 'min', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-041: La Si La Ti Do
        [chordalLit(2, 'min7', 0, 3, 0), chordalLit(10, 'dom7', 1, 0, -12), chordalLit(9, 'min7', 2, 1, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-042: So Fi So La Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(0, 'dim7', 0, 3, 0), chordalLit(9, 'min7', 1, 0, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-043: So Fi La La Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(0, 'maj7b5', 0, 3, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-044: So Fi La Do Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(6, 'm7b5', 2, 1, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-045: Mi Ri Mi So Do
        [chordalLit(9, 'min7', 0, 3, -12), chordalLit(8, 'minMaj7', 0, 3, -12), chordalLit(0, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 2, 1, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-046: Mi Mi So La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(10, 'dimMaj7', 0, 3, -12), chordalLit(9, 'min7', 1, 0, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-047: Mi Fa Fi So Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(7, 'min7', 1, 0, -12), chordalLit(6, 'm7b5', 2, 1, -12), chordalLit(7, 'dom7', 2, 1, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-048: So Fa La La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(10, 'maj', 0, 0, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-049: Mi Fa So Ti Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(10, 'dom7', 0, 3, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-050: La Si La Do Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(5, 'dim7', 3, 2, -12), chordalLit(6, 'm7b5', 3, 2, -12), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-051: La So So La Do
        [chordalLit(2, 'min7', 0, 3, 0), chordalLit(1, 'maj7b5', 0, 3, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-052: Mi Fa So La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(5, 'minMaj7', 2, 1, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-053: Mi Mi Fa La Do
        [chordalLit(9, 'min7', 0, 3, -12), chordalLit(1, 'm7b5', 3, 2, -12), chordalLit(2, 'min7', 3, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-054: So Si Ti Do Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'dimMaj7', 3, 2, -12), chordalLit(4, 'min7', 0, 3, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-055: Mi Mi Fi La Do
        [chordalLit(0, 'maj7', 3, 2, -12), chordalLit(4, 'min7', 2, 1, -12), chordalLit(3, 'min7', 3, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-056: So La La Do Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'maj', 0, 0, 0), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-057: So So La Do Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(0, 'dom7', 0, 3, 0), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-058: Ti La La Ti Do
        [chordalLit(0, 'maj7', 1, 0, 0), chordalLit(0, 'dim7', 1, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-059: So La So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7b5', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-060: La Si Li Do Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'm7b5', 0, 3, 0), chordalLit(4, 'm7b5', 0, 3, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-061: Mi Mi Fa Si Do
        [chordalLit(0, 'maj7', 3, 2, -12), chordalLit(4, 'min7', 2, 1, -12), chordalLit(5, 'maj7', 2, 1, -12), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-062: So So Fi La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'min7', 3, 2, -12), chordalLit(3, 'dimMaj7', 3, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-063: So So Li La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'min7', 2, 1, -12), chordalLit(0, 'dom7', 1, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-064: So Fa La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(10, 'maj', 0, 0, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-065: So Si La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'dom7', 3, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-066: La Li Do Ti Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(10, 'maj7b5', 2, 1, -12), chordalLit(5, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-067: So La Li Do Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(3, 'dim7', 0, 3, 0), chordalLit(0, 'dom7', 1, 0, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-068: La So La La Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(4, 'm7b5', 3, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-069: So La So Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(8, 'minMaj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-070: Fa Mi So La Do
        [chordalLit(5, 'maj7', 2, 1, -12), chordalLit(10, 'dimMaj7', 0, 3, -12), chordalLit(9, 'min7', 1, 0, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-071: Do Li La Ti Do
        [chordalLit(5, 'maj', 0, 0, 0), chordalLit(7, 'min7', 3, 2, -12), chordalLit(6, 'm7b5', 3, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-072: Mi Mi Fa So Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(9, 'dom7', 0, 3, -12), chordalLit(2, 'min', 2, 2, -12), chordalLit(7, 'dom7', 2, 1, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-073: La La Ti Do Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'maj7b5', 3, 2, -12), chordalLit(4, 'min', 0, 0, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-074: Ti La La Do Do
        [chordalLit(0, 'maj7', 1, 0, 0), chordalLit(0, 'dim7', 1, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-075: Fa Fi La La Do
        [chordalLit(5, 'maj7', 2, 1, -12), chordalLit(6, 'm7b5', 2, 1, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],

        // --- CBL-076 through CBL-150, the six-event sequences from the same
        // 150-candidate set, added 6 Nov 2026. Same validation as above. ---
        // CBL-076: La Si Ti Do Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(8, 'maj7', 2, 1, -12), chordalLit(7, 'maj', 2, 2, -12), chordalLit(9, 'min', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-077: So Li Si So Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'min', 2, 2, -12), chordalLit(5, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-078: So Si So Ti Do Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(8, 'maj7', 2, 1, -12), chordalLit(7, 'dom7', 2, 1, -12), chordalLit(0, 'maj7', 1, 0, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-079: So Li Si So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'min', 2, 2, -12), chordalLit(5, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-080: So Si So So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(8, 'maj7', 2, 1, -12), chordalLit(7, 'dom7', 2, 1, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-081: La Li La La Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'min', 2, 2, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-082: So So Si Do Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'min', 2, 2, -12), chordalLit(4, 'maj', 2, 2, -12), chordalLit(9, 'min', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-083: So Si Ti So Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(8, 'maj7', 2, 1, -12), chordalLit(7, 'maj', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-084: La Si So La Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(5, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-085: So La Ti La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'maj', 0, 0, 0), chordalLit(7, 'maj', 2, 2, -12), chordalLit(9, 'min7', 2, 1, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-086: Mi Ri Fa So La Do
        [chordalLit(9, 'min7', 0, 3, -12), chordalLit(8, 'minMaj7', 0, 3, -12), chordalLit(5, 'm7b5', 2, 1, -12), chordalLit(4, 'min', 2, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-087: La Si Si So Ti Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'dimMaj7', 3, 2, -12), chordalLit(5, 'dim7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-088: La Si So So La Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'min7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'min', 2, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-089: So Li La So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(3, 'maj7', 0, 3, 0), chordalLit(2, 'min', 0, 0, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-090: La Li Si So Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'min', 2, 2, -12), chordalLit(5, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-091: So So Fi Fa La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'min', 2, 2, -12), chordalLit(11, 'dom7', 0, 3, -12), chordalLit(5, 'maj7', 2, 1, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-092: So So La La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'min7', 3, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(6, 'm7b5', 3, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-093: So So Si So Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 2, 1, -12), chordalLit(11, 'dim7', 1, 0, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-094: Ti La La Do Ti Do
        [chordalLit(4, 'min7', 0, 3, 0), chordalLit(3, 'maj7b5', 0, 3, 0), chordalLit(2, 'min', 0, 0, 0), chordalLit(5, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-095: La Si Si So La Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(5, 'dimMaj7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-096: Mi Fa So So La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(5, 'dimMaj7', 2, 1, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(9, 'min7', 1, 0, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-097: Mi Mi Fa So La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(9, 'maj', 0, 0, -12), chordalLit(2, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-098: Mi Fa Mi Fi La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 1, 0, -12), chordalLit(0, 'maj', 2, 2, -12), chordalLit(3, 'min7', 3, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-099: Do Li Do Do Ti Do
        [chordalLit(5, 'maj', 0, 0, 0), chordalLit(10, 'maj7', 2, 1, -12), chordalLit(5, 'maj', 0, 0, 0), chordalLit(2, 'min7', 1, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-100: So La Si So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj', 2, 2, -12), chordalLit(5, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-101: So La Ti Li Do Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 1, 0, 0), chordalLit(0, 'dom7', 1, 0, 0), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-102: Fa Fa So La La Do
        [chordalLit(2, 'min7', 3, 2, -12), chordalLit(2, 'm7b5', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-103: So So Si So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(1, 'dim7', 0, 3, 0), chordalLit(2, 'm7b5', 0, 3, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-104: Mi Re Fa So La Do
        [chordalLit(9, 'min7', 0, 3, -12), chordalLit(8, 'maj7b5', 0, 3, -12), chordalLit(2, 'm7b5', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-105: Ti La Si So Ti Do
        [chordalLit(0, 'maj7', 1, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-106: So La So La La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(10, 'dimMaj7', 1, 0, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-107: So Ti Do La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'maj', 0, 0, 0), chordalLit(9, 'min', 2, 2, -12), chordalLit(2, 'min', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-108: La Si So Do Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(5, 'min7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(9, 'min', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-109: La Si Ti Ti Do Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(8, 'maj7', 2, 1, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 1, 0, 0), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-110: So Li La La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'min', 2, 2, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-111: So La Si La La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(10, 'dom7', 1, 0, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-112: Fa Fi Fa So La Do
        [chordalLit(5, 'maj7', 2, 1, -12), chordalLit(6, 'm7b5', 2, 1, -12), chordalLit(2, 'm7b5', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-113: So La So Li Do Do
        [chordalLit(0, 'maj7', 0, 3, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(1, 'dim7', 1, 0, 0), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-114: So Fi La La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(0, 'maj7b5', 0, 3, 0), chordalLit(10, 'maj7', 1, 0, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-115: So Fi Fi So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(0, 'dimMaj7', 0, 3, 0), chordalLit(0, 'dim7', 0, 3, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-116: La Si So La Do Do
        [chordalLit(9, 'min7', 2, 1, -12), chordalLit(8, 'minMaj7', 2, 1, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(9, 'min7', 2, 1, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-117: La La Ti La Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'maj', 0, 0, 0), chordalLit(7, 'maj', 2, 2, -12), chordalLit(9, 'min7', 2, 1, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-118: La Li Li La Ti Do
        [chordalLit(2, 'min7', 0, 3, 0), chordalLit(7, 'min7', 3, 2, -12), chordalLit(0, 'dom7', 1, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-119: So Si Ti La Do Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(8, 'maj7', 2, 1, -12), chordalLit(7, 'maj', 2, 2, -12), chordalLit(9, 'min7', 2, 1, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-120: La Li Si So La Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'min', 2, 2, -12), chordalLit(5, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-121: Mi Re Mi Mi La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(7, 'maj', 0, 0, -12), chordalLit(4, 'dom7', 2, 1, -12), chordalLit(9, 'min', 0, 0, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-122: La La Si So La Do
        [chordalLit(9, 'min7', 2, 1, -12), chordalLit(9, 'm7b5', 2, 1, -12), chordalLit(2, 'm7b5', 0, 3, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-123: So So Fa So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(1, 'dim7', 0, 3, 0), chordalLit(2, 'min', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-124: Mi Fa Mi Mi So Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(1, 'maj7b5', 3, 2, -12), chordalLit(0, 'maj', 2, 2, -12), chordalLit(9, 'min', 0, 0, -12), chordalLit(7, 'dom7', 2, 1, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-125: Ti La Si La Do Do
        [chordalLit(0, 'maj7', 1, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(9, 'min7', 2, 1, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-126: Fa Fa So La Ti Do
        [chordalLit(5, 'maj7', 2, 1, -12), chordalLit(5, 'dimMaj7', 2, 1, -12), chordalLit(4, 'min7', 3, 2, -12), chordalLit(9, 'min7', 2, 1, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-127: La Mi Fa La Ti Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(9, 'maj', 0, 0, -12), chordalLit(2, 'min', 2, 2, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-128: Fa Fa Fi La La Do
        [chordalLit(2, 'min7', 3, 2, -12), chordalLit(7, 'min7', 1, 0, -12), chordalLit(6, 'm7b5', 2, 1, -12), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-129: La Li La La Do Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(3, 'maj7', 0, 3, 0), chordalLit(2, 'min', 0, 0, 0), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-130: Fa Fa So So La Do
        [chordalLit(5, 'maj7', 2, 1, -12), chordalLit(5, 'm7b5', 2, 1, -12), chordalLit(4, 'min', 2, 2, -12), chordalLit(9, 'min7', 1, 0, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-131: So Si So La La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'dom7', 3, 2, -12), chordalLit(9, 'min7', 1, 0, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-132: So So Fi So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'min', 2, 2, -12), chordalLit(3, 'dim7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-133: Mi Ri Fa Fa La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(3, 'maj7b5', 2, 1, -12), chordalLit(2, 'min7', 3, 2, -12), chordalLit(5, 'maj7', 2, 1, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-134: La Si Ti La Do Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(4, 'min7', 0, 3, 0), chordalLit(9, 'min7', 2, 1, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-135: Mi Fa Fa So La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(5, 'dimMaj7', 2, 1, -12), chordalLit(5, 'dim7', 2, 1, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-136: So Si Mi Fa La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'maj', 2, 2, -12), chordalLit(9, 'min', 0, 0, -12), chordalLit(2, 'min7', 3, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-137: So So Fi La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'min7', 3, 2, -12), chordalLit(3, 'min7', 3, 2, -12), chordalLit(2, 'min7', 0, 3, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-138: So Fa So Si Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(8, 'maj7', 2, 1, -12), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-139: So So Mi La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'm7b5', 3, 2, -12), chordalLit(9, 'maj', 0, 0, -12), chordalLit(2, 'min', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-140: So Si So La Ti Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'dom7', 3, 2, -12), chordalLit(9, 'dom7', 1, 0, -12), chordalLit(2, 'min', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-141: Mi Mi Fa So Ti Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(4, 'dim7', 2, 1, -12), chordalLit(2, 'm7b5', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(7, 'dom7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-142: La La So So La Do
        [chordalLit(5, 'maj7', 3, 2, -12), chordalLit(5, 'maj7b5', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(4, 'min7', 3, 2, -12), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-143: So Si Si So La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'minMaj7', 3, 2, -12), chordalLit(5, 'dimMaj7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-144: Fa Fa So La Do Do
        [chordalLit(5, 'maj7', 2, 1, -12), chordalLit(5, 'dimMaj7', 2, 1, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(9, 'min7', 2, 1, -12), chordalLit(5, 'maj7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-145: La La Ti So La Do
        [chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'maj', 0, 0, 0), chordalLit(7, 'maj', 2, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-146: So Si So La Do Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'min7', 3, 2, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj', 2, 2, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-147: Mi Ri Re So La Do
        [chordalLit(0, 'maj', 2, 2, -12), chordalLit(8, 'maj7', 0, 3, -12), chordalLit(7, 'maj', 0, 0, -12), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-148: So La Ti La Do Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(11, 'dom7', 1, 0, -12), chordalLit(7, 'maj', 2, 2, -12), chordalLit(9, 'min7', 2, 1, -12), chordalLit(2, 'min7', 1, 0, 0), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-149: Ti Li Si So La Do
        [chordalLit(4, 'min7', 0, 3, 0), chordalLit(4, 'm7b5', 0, 3, 0), chordalLit(2, 'm7b5', 0, 3, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
        // CBL-150: So Fi So La La Do
        [chordalLit(0, 'maj', 0, 0, 0), chordalLit(0, 'dim7', 0, 3, 0), chordalLit(0, 'maj', 0, 0, 0), chordalLit(2, 'min7', 0, 3, 0), chordalLit(5, 'maj7', 3, 2, -12), chordalLit(0, 'maj7', 2, 1, 0)],
    ];
}
