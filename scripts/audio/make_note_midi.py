#!/usr/bin/env python3
"""Write a minimal single-track Standard MIDI File with one sustained note or chord.

Usage: make_note_midi.py out.mid <program> <velocity> <beats> <note1> [note2 ...]
Same shape used throughout the sound-selection listening tests, kept here so the
real rendering pipeline uses the exact same MIDI content those decisions were made
from — nothing re-tuned or re-guessed at generation time.
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
    out, program, velocity, beats = sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), float(sys.argv[4])
    notes = [int(n) for n in sys.argv[5:]]
    ticks_per_beat = 480
    duration = int(ticks_per_beat * beats)

    events = b""
    events += write_var_len(0) + bytes([0xC0, program])
    for note in notes:
        events += write_var_len(0) + bytes([0x90, note, velocity])
    first = True
    for note in notes:
        delta = duration if first else 0
        first = False
        events += write_var_len(delta) + bytes([0x80, note, 0])
    events += write_var_len(ticks_per_beat // 2) + bytes([0xFF, 0x2F, 0x00])

    track = b"MTrk" + struct.pack(">I", len(events)) + events
    header = b"MThd" + struct.pack(">IHHH", 6, 0, 1, ticks_per_beat)

    with open(out, "wb") as f:
        f.write(header + track)


if __name__ == "__main__":
    main()
