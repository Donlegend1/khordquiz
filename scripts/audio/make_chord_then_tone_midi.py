#!/usr/bin/env python3
"""Write a minimal single-track Standard MIDI File: a chord sustained, then
released, immediately followed by one isolated note (the "highlighted"
chord tone) — for Chord Tone Recognition's "play the chord, then play one
of its tones alone" format.

Usage: make_chord_then_tone_midi.py out.mid <program> <velocity> <tone_note> <chord_note1> [chord_note2 ...]
Chord holds for CHORD_BEATS, then the isolated tone starts the instant the
chord notes release and holds for TONE_BEATS. Same MIDI shape convention as
make_note_midi.py (480 ticks/beat, no tempo meta -> FluidSynth's default 120bpm).
"""
import struct
import sys

CHORD_BEATS = 3.2
TONE_BEATS = 4.8


def write_var_len(value):
    buf = [value & 0x7F]
    value >>= 7
    while value:
        buf.insert(0, (value & 0x7F) | 0x80)
        value >>= 7
    return bytes(buf)


def main():
    out, program, velocity = sys.argv[1], int(sys.argv[2]), int(sys.argv[3])
    tone_note = int(sys.argv[4])
    chord_notes = [int(n) for n in sys.argv[5:]]
    ticks_per_beat = 480
    chord_duration = int(ticks_per_beat * CHORD_BEATS)
    tone_duration = int(ticks_per_beat * TONE_BEATS)

    events = b""
    events += write_var_len(0) + bytes([0xC0, program])
    for note in chord_notes:
        events += write_var_len(0) + bytes([0x90, note, velocity])

    first = True
    for note in chord_notes:
        delta = chord_duration if first else 0
        first = False
        events += write_var_len(delta) + bytes([0x80, note, 0])

    events += write_var_len(0) + bytes([0x90, tone_note, velocity])
    events += write_var_len(tone_duration) + bytes([0x80, tone_note, 0])
    events += write_var_len(ticks_per_beat // 2) + bytes([0xFF, 0x2F, 0x00])

    track = b"MTrk" + struct.pack(">I", len(events)) + events
    header = b"MThd" + struct.pack(">IHHH", 6, 0, 1, ticks_per_beat)

    with open(out, "wb") as f:
        f.write(header + track)


if __name__ == "__main__":
    main()
