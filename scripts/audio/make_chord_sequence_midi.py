#!/usr/bin/env python3
"""Write a minimal single-track Standard MIDI File playing a sequence of block
chords, one after another — each chord's notes start and stop together, unlike
make_sequence_midi.py's one-note-at-a-time scale runs.

Usage: make_chord_sequence_midi.py out.mid <program> <velocity> <beats_per_chord> <chord1> [chord2 ...]
Each chordN is a comma-separated list of MIDI note numbers, e.g. "60,64,67".
A small gap (10% of beats_per_chord) is left between chords for articulation,
same 480-ticks-per-beat / implied-120bpm convention as make_sequence_midi.py.
"""
import struct
import sys


def write_var_len(value):
    buf = [value & 0x7F]
    value >>= 7
    while value:
        buf.insert(0, (value & 0x7F) | 0x80)
        value >>= 7
    return bytes(buf)


def main():
    out, program, velocity, beats_per_chord = sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), float(sys.argv[4])
    chords = [[int(n) for n in group.split(",")] for group in sys.argv[5:]]
    ticks_per_beat = 480
    note_on_ticks = int(ticks_per_beat * beats_per_chord * 0.9)
    gap_ticks = int(ticks_per_beat * beats_per_chord * 0.1)

    events = b""
    events += write_var_len(0) + bytes([0xC0, program])
    for chord in chords:
        for i, note in enumerate(chord):
            events += write_var_len(0) + bytes([0x90, note, velocity])
        for i, note in enumerate(chord):
            delay = note_on_ticks if i == 0 else 0
            events += write_var_len(delay) + bytes([0x80, note, 0])
    events += write_var_len(gap_ticks) + bytes([0xFF, 0x2F, 0x00])

    track = b"MTrk" + struct.pack(">I", len(events)) + events
    header = b"MThd" + struct.pack(">IHHH", 6, 0, 1, ticks_per_beat)

    with open(out, "wb") as f:
        f.write(header + track)


if __name__ == "__main__":
    main()
