#!/usr/bin/env python3
"""Write a minimal single-track Standard MIDI File playing notes one after another.

Unlike make_note_midi.py (all notes held together as a chord), each note here
gets its own note-on/note-off pair in sequence — used for reference audio like
a scale run, where notes must be heard one at a time.

Usage: make_sequence_midi.py out.mid <program> <velocity> <beats_per_note> <note1> [note2 ...]
A small gap (10% of beats_per_note) is left between notes for articulation,
same 480-ticks-per-beat / implied-120bpm convention as make_note_midi.py.
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
    out, program, velocity, beats_per_note = sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), float(sys.argv[4])
    notes = [int(n) for n in sys.argv[5:]]
    ticks_per_beat = 480
    note_on_ticks = int(ticks_per_beat * beats_per_note * 0.9)
    gap_ticks = int(ticks_per_beat * beats_per_note * 0.1)

    events = b""
    events += write_var_len(0) + bytes([0xC0, program])
    for note in notes:
        events += write_var_len(0) + bytes([0x90, note, velocity])
        events += write_var_len(note_on_ticks) + bytes([0x80, note, 0])
    events += write_var_len(gap_ticks) + bytes([0xFF, 0x2F, 0x00])

    track = b"MTrk" + struct.pack(">I", len(events)) + events
    header = b"MThd" + struct.pack(">IHHH", 6, 0, 1, ticks_per_beat)

    with open(out, "wb") as f:
        f.write(header + track)


if __name__ == "__main__":
    main()
