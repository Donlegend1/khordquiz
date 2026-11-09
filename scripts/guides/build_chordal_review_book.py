"""Build the printable review book from the generated, validated candidate JSON.

Run from the project root: python3 scripts/guides/build_chordal_review_book.py
Export the resulting HTML with the browser's Print / Save as PDF.
"""
from pathlib import Path
from html import escape
import json
import argparse

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser()
parser.add_argument('--batch', type=int, choices=[1, 2], default=1)
batch = parser.parse_args().batch
tag = '' if batch == 1 else '-batch-2'
stem = 'chordal-melody-100-candidates' + tag
data = json.loads((ROOT / ('docs/composition/' + stem + '.json')).read_text())
audit = json.loads((ROOT / ('docs/composition/chordal-melody-100-validation' + tag + '.json')).read_text())
records = data['sequences']
def h(value):
    return escape(str(value))
def join(values, separator=' · '):
    return h(separator.join(map(str, values)))
def card(record):
    rows = ''.join('<tr><td>'+str(step['position'])+'</td><td><b>'+h(step['chord'])+'</b><small> · '+h({'Root position':'root','1st inversion':'1st','2nd inversion':'2nd','3rd inversion':'3rd'}[step['inversion']])+'</small></td><td>'+join(step['voicing_notes'])+'</td><td>'+join(step['voicing_midi'], ' ')+'</td></tr>' for step in record['steps'])
    return '<article><div class="heading"><h2>'+h(record['id'])+'</h2><span>'+str(record['length'])+' chord events · '+str(record['diatonic_events'])+' diatonic / '+str(record['non_diatonic_events'])+' chromatic</span></div><p class="progression">'+join(record['chords'], ' → ')+'</p><p><b>Melody:</b> '+join(record['melody_notes'], ' → ')+'<br><b>Solfege:</b> '+join(record['melody_solfege'])+'</p><table><thead><tr><th>#</th><th>Chord / bass position</th><th>Play together, low → high</th><th>MIDI</th></tr></thead><tbody>'+rows+'</tbody></table><p class="explanation">'+h(record['harmonic_explanation'])+'</p>' + ('<p class="exception">Full-chain exception: three chromatic events; C remains the intended home.</p>' if record['dense_exception'] else '')+'</article>'
cover='''<section class="page cover"><div class="eyebrow">KHORDQUIZ · COMPOSITION REVIEW</div><h1>100 chordal<br>melody sequences.</h1><p class="lead">C major · explicit notes · connected voices</p><div class="stats"><div><strong>50</strong><span>Five-event phrases</span></div><div><strong>50</strong><span>Six-event phrases</span></div></div><h2>How to use this book</h2><ol><li>Play each row as a block chord, in the stated octave.</li><li>Follow the highest note of each chord as the melody.</li><li>Listen to the complete phrase. Check that C still feels like home.</li><li>Review the first ten before approving any import into the quizzes.</li></ol><div class="note"><b>Checked by calculation</b><p>All 100 progressions and top-note pitch-class sequences are distinct, including comparison against the 81 existing literal patterns. Every chord retains its defining notes. Inversions and spellings match the MIDI pitches. All pitches lie between C3 and C5; melody steps/leaps are no larger than four semitones, and bass and individual-voice movement no larger than seven.</p></div><div class="note"><b>Two explicit exceptions</b><p>CM-051 includes FmMaj7 → FdimMaj7 → Fdim7 → C. CM-052 includes Dm7♭5 → Am7♭5 → Dm7♭5 → C. Each has three chromatic and three diatonic events. The other 98 phrases contain a strict majority of diatonic events.</p></div><p class="small">Four voices per chord: triads double a root or fifth; seventh chords contain all four defining pitch classes. Dm7♭5 replaces the Fm6 label. Theory spellings such as C♭ and E♭♭ are intentional; the melody summary uses familiar sounding names. C4 = MIDI 60.</p><p class="small"><b>Review draft.</b> Pitch checks do not prove musical quality. Unusual routes follow the owner’s supplied palette, not universal functional-harmony rules. Listening approval is pending. No quiz records, audio attachments or mobile content were changed. Per-instrument recorded-range checks belong to a later approved import step.</p></section>'''
css='''@page{size:A4;margin:0}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#222;font-size:10pt;line-height:1.4;margin:0;background:#eee}.page{width:210mm;height:297mm;background:white;padding:15mm 16mm 20mm;position:relative;break-after:page}.page:last-child{break-after:auto}.eyebrow{font-size:8pt;font-weight:700;letter-spacing:1.4px;color:#bb2720;margin-bottom:5mm}h1{font-size:36pt;line-height:1.1;letter-spacing:-1px;margin:8mm 0 4mm}h2{font-size:14pt;margin:0}p{margin:2mm 0}small,.small{font-size:8pt;color:#666}.lead{font-size:14pt;color:#666}.stats{display:flex;gap:5mm;margin:8mm 0}.stats div{background:#f7f3ef;border-radius:4mm;flex:1;padding:5mm}.stats strong{display:block;font-size:28pt}.stats span{font-size:10pt;color:#666}ol{padding-left:6mm;margin:4mm 0 6mm}li{margin:3mm 0}.note{background:#f1f8fc;border:1px solid #d6e9f5;padding:4mm;border-radius:3mm;margin:4mm 0}.note p{font-size:9.5pt}.heading{display:flex;align-items:center;justify-content:space-between;gap:3mm}.heading span{font-size:8pt;color:#666}.progression{font-size:12pt;font-weight:700;margin:3mm 0}article{border:1px solid #ddd;border-radius:3mm;padding:4mm;margin-bottom:5mm;break-inside:avoid}article p{font-size:9pt}table{border-collapse:collapse;width:100%;font-size:9pt;margin:3mm 0}th{text-align:left;font-size:7.5pt;color:#666;background:#f7f7f7;padding:2mm}td{padding:2mm;border-bottom:1px solid #eee;vertical-align:top}td:nth-child(1){width:5%}td:nth-child(2){width:28%}td:nth-child(3){width:43%}.explanation{color:#666;font-size:8pt!important;line-height:1.4}.exception{font-size:8pt!important;color:#8e5600;background:#fff7e6;padding:2mm;border-radius:2mm}footer{position:absolute;bottom:8mm;left:16mm;right:16mm;border-top:1px solid #ddd;padding-top:3mm;display:flex;justify-content:space-between;color:#777;font-size:8pt}@media screen{.page{margin:6mm auto;box-shadow:0 2px 10px #ccc}}@media print{body{background:white}.page{margin:0}}'''
cover = cover.replace('C major · explicit notes · connected voices', f'Batch {batch} · C major · {records[0]["id"]}–{records[-1]["id"]}')
cover = cover.replace('the 81 existing literal patterns', f'the {audit["existing_literal_pool_compared"]} existing literal patterns' + (' and the first 100 generated candidates' if batch == 2 else ''))
cover = cover.replace('four semitones', f'{audit["max_melody_leap"]} semitones').replace('seven.', f'{max(audit["max_bass_leap"], audit["max_individual_voice_leap"])} semitones.')
cover = cover.replace('CM-051', audit['dense_exception_ids'][0]).replace('CM-052', audit['dense_exception_ids'][1])
pages=[cover]
for i in range(0,len(records),2):
    pages.append('<section class="page"><div class="eyebrow">C MAJOR · '+str(records[i]['length'])+'-EVENT SEQUENCES · REVIEW DRAFT</div>'+card(records[i])+card(records[i+1])+'</section>')
body=''.join(page.replace('</section>',f'<footer><span>KhordQuiz · Chordal melody candidates · 6 November 2026</span><span>{i+1} / {len(pages)}</span></footer></section>') for i,page in enumerate(pages))
(ROOT/('docs/composition/' + stem + '.html')).write_text('<!doctype html><html lang="en"><head><meta charset="utf-8"><title>100 chordal-melody candidates — KhordQuiz</title><style>'+css+'</style></head><body>'+body+'</body></html>')
print('Created review book:',len(pages),'pages')
