#!/usr/bin/env python3
"""Rough fundamental-frequency estimator via autocorrelation, stdlib only (no numpy).
Usage: detect_pitch.py file.wav [start_sec] [dur_sec]
Prints the estimated frequency (Hz) and nearest MIDI note number.
"""
import sys
import wave
import array
import math

def midi_from_freq(freq):
    if freq <= 0:
        return None
    return 69 + 12 * math.log2(freq / 440.0)

def main():
    path = sys.argv[1]
    start = float(sys.argv[2]) if len(sys.argv) > 2 else 0.1
    dur = float(sys.argv[3]) if len(sys.argv) > 3 else 0.3

    wf = wave.open(path, 'rb')
    rate = wf.getframerate()
    channels = wf.getnchannels()
    sampwidth = wf.getsampwidth()

    wf.setpos(int(start * rate))
    nframes = int(dur * rate)
    raw = wf.readframes(nframes)

    if sampwidth != 2:
        print("only 16-bit supported")
        return

    samples = array.array('h', raw)
    if channels == 2:
        samples = samples[0::2]  # left channel only

    n = len(samples)
    if n < 100:
        print("not enough samples")
        return

    # Autocorrelation over lags corresponding to 60-1200 Hz.
    min_lag = int(rate / 1200)
    max_lag = int(rate / 60)
    best_lag = None
    best_val = 0
    mean = sum(samples) / n
    centered = [s - mean for s in samples]

    for lag in range(min_lag, min(max_lag, n - 1)):
        s = 0
        count = n - lag
        for i in range(0, count, 4):  # stride for speed
            s += centered[i] * centered[i + lag]
        if s > best_val:
            best_val = s
            best_lag = lag

    if not best_lag:
        print("no pitch found")
        return

    freq = rate / best_lag
    midi = midi_from_freq(freq)
    print(f"freq={freq:.1f}Hz midi={midi:.2f} (~{round(midi)})")

if __name__ == '__main__':
    main()
