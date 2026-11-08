"""Build one illustrated category guide at a time from the quiz instruction data.

Run from the project root: python3 scripts/guides/build_category_guides.py melodic-dictation
Omit the slug to generate the HTML for every remaining category. Export PDFs separately.
"""
import html
import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DATA = ROOT / 'resources/js/data'
CATEGORIES = [
 ('Melodic Dictation', 'melodic-dictation', 'melodicDictation', 'Follow a melody and enter its notes in the order you hear them.', 'Learn to follow a musical line, including when other notes are sounding around it.'),
 ('Intervals', 'intervals', 'interval', 'Hear two notes and name the distance between them.', 'Recognise the gaps that give melodies and chords their shape.'),
 ('Basic Triads', 'basic-triads', 'basicTriad', 'Hear a three-note chord and recognise its type and, where requested, its position.', 'Learn the sound of the basic chord families before moving on to added notes.'),
 ('Add 9', 'add-9', 'addNine', 'Recognise a three-note chord with an added ninth.', 'Hear how one added note changes a familiar chord sound.'),
 ('7th Degree Chords', '7th-degree-chords', 'seventhChord', 'Recognise the sound of four-note seventh chords.', 'Tell the main seventh-chord families apart, even as their notes are rearranged.'),
 ('Secondary 7th Chords', 'secondary-7th-chords', 'secondarySeventh', 'Recognise seventh chords with different combinations of chord notes.', 'Hear the difference made by a changed fifth or a different kind of seventh.'),
 ('9th Degree Chords', '9th-degree-chords', 'ninthChord', 'Recognise seventh chords with an added ninth.', 'Build confidence with fuller chord sounds and the differences between their added notes.'),
 ('Secondary 9th Chords', 'secondary-9th-chords', 'secondaryNinth', 'Recognise ninth chords with less familiar combinations of chord notes.', 'Hear how a different fifth or seventh changes the colour of a ninth chord.'),
 ('11th Degree Chords', '11th-degree-chords', 'eleventhChord', 'Hear a fuller chord and recognise its eleventh-chord type.', 'Distinguish chord sounds with an eleventh or a raised eleventh.'),
 ('Secondary 11th Chords', 'secondary-11th-chords', 'secondaryEleventh', 'Recognise eleventh chords with different combinations of chord notes.', 'Notice the changes that distinguish closely related extended chord sounds.'),
 ('13th Degree Chords', '13th-degree-chords', 'thirteenthChord', 'Recognise chords that include a thirteenth.', 'Hear the overall shape of a rich chord without having to name every note separately.'),
 ('Major Chord Extensions', 'major-chord-extensions', 'majorExtension', 'Hear the notes added to a major chord and select all their numbers.', 'Learn which added notes create each major-chord colour.'),
 ('Minor Chord Extensions', 'minor-chord-extensions', 'minorExtension', 'Hear the notes added to a minor chord and select all their numbers.', 'Learn which added notes create each minor-chord colour.'),
 ('Dominant Chord Extensions', 'dominant-chord-extensions', 'dominantExtension', 'Hear the extra notes above a dominant seventh chord.', 'Tell natural and altered extensions apart while the dominant seventh stays given.'),
 ('Chord Naming', 'chord-naming', 'chordNaming', 'Build a chord name from its quality, degrees and any missing note.', 'Connect the sound of a chord with the parts of its name.'),
 ('Chord Progressions', 'chord-progressions', 'progression', 'Follow the chords as they change and choose the answer each quiz asks for.', 'Hear endings, chord roles, chord types and added notes in a musical sequence.'),
 ('Modal Harmony', 'modal-harmony', 'modalHarmony', 'Hear a bass note and a triad together, then choose their full modal-colour label.', 'Recognise the sound of an upper chord in relation to a steady bass note.'),
 ('Modal Voicings', 'modal-voicings', 'modalVoicing', 'Hear the bass and higher chord notes together, then choose the modal name.', 'Learn the differences between a modal family and its related colours.'),
 ('Scales', 'scales', 'scale', 'Follow the notes up and down, then choose the scale name.', 'Hear the notes that distinguish related scales and modes.'),
]

def esc(value):
 return html.escape(str(value), quote=True)

def display(title):
 return title.replace('Extentions', 'Extensions')

def paragraph(text, css=''):
 return f'<p class="{css}">{esc(text)}</p>'

def chips(values, selected=()):
 return '<div class="chips">'+''.join(f'<span class="chip {"selected" if v in selected else ""}">{esc(v)}</span>' for v in values)+'</div>'

def notes(values, sequence=False, selected=True):
 return '<div class="notes">'+''.join(f'<div class="note {"selected" if selected else ""}">{esc(n)}<small>{str(i+1) if sequence else ""}</small></div>' for i,n in enumerate(values))+'</div>'

def answer(value):
 return '<div class="answer"><span class="label">Example answer</span>'+paragraph(value,'answer-name')+'</div>'

def diagram(g, family):
 if family=='melodicDictation':
  focus=g['focus'];upper=focus in ['upper-pair','upper-chord'];bass=focus=='bass';layered=upper or bass
  cells=[]
  for i,n in enumerate(g['exampleNotes']):
   other='<div class="muted-layer">'+('━<br>━' if focus!='upper-pair' else '━')+'</div>'
   cells.append(f'<div class="sequence-cell"><small>{i+1}</small>'+ (other if bass else '')+f'<div class="note selected">{esc(n)}</div>'+ (other if upper else '')+'</div>')
  caption='Follow the lowest notes' if bass else 'Follow the higher notes' if layered else 'Enter from left to right'
  return paragraph(caption,'label')+'<div class="sequence">'+''.join(cells)+'</div>'+paragraph('Green: notes to enter. Grey: the other notes sounding with them.' if layered else 'Each numbered box is the next note in your answer.','caption')
 if 'exampleSlots' in g:
  prefix=paragraph('Before the answer: home major chord → home note.','anchor') if g.get('anchor') else ''
  cells=''.join(f'<div class="slot"><small>Chord {i+1}</small><b>{esc(s.get("degree", ""))}</b><span>{esc(s["quality"])}</span><small>{esc(", ".join(s.get("extensions", [])))}</small></div>' for i,s in enumerate(g['exampleSlots']))
  return prefix+'<div class="sequence">'+cells+'</div>'+ (answer(g['exampleAnswer']) if g.get('exampleAnswer') else '')
 if 'pair' in g:
  pair=g['pair'];return paragraph('At the same time' if pair['together'] else 'One after the other','label')+'<div class="pair">'+notes([pair['low']],selected=False)+f'<span class="arrow">{"+" if pair["together"] else "→"}</span>'+notes([pair['high']],selected=False)+'</div>'+answer(g['example'])
 if 'bass' in g:
  return paragraph(g.get('upperTriad',g.get('upperLabel','Upper chord notes')),'label')+notes(g['upperNotes'],selected=False)+'<div class="bass">'+esc(g['bass'])+'<small>Bass note · played with all the notes above</small></div>'+answer(g['example'])
 if isinstance(g.get('example'),dict):
  ex=g['example'];return paragraph('Notes played together','label')+notes(ex['notes'],selected=False)+'<div class="fields">'+''.join(f'<div><span>{label}</span><b>{esc(value)}</b></div>' for label,value in [('Quality',ex['quality']),('Degrees',', '.join(ex['degrees'])),('Exclude',ex['exclude'])])+'</div>'
 if family=='scale':
  return paragraph('Played up, then back down','label')+notes(g['exampleNotes'],sequence=True,selected=False).replace('<div class="notes">','<div class="notes scale-notes">',1)+paragraph('↑ Go up through the notes. ↓ Return to the starting note.','caption')+answer(g['example'])
 if 'selected' in g:
  return paragraph(g['exampleChord'],'label')+notes(g['exampleNotes'],selected=False)+paragraph('Select every added note shown here:','caption')+chips(g['choices'],g['selected'])
 if 'exampleQuality' in g:
  result=paragraph('Notes played together · low to high','label')+notes(g['exampleNotes'],selected=False)+answer(g['exampleQuality'])
  if g.get('exampleInversion'): result+=paragraph('Also choose: '+g['exampleInversion'],'inversion')
  return result
 raise ValueError('No diagram for '+family)

STYLE='''
@page{size:A4;margin:0}*{box-sizing:border-box}body{margin:0;background:#e9e7e4;color:#202020;font-family:Arial,Helvetica,sans-serif;font-size:10pt;line-height:1.5;-webkit-print-color-adjust:exact;print-color-adjust:exact}.page{width:210mm;height:297mm;padding:15mm 17mm 20mm;background:#fff;position:relative;margin:8mm auto;break-after:page;overflow:hidden}.page:last-child{break-after:auto}header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e4e2de;padding-bottom:4mm;margin-bottom:6mm}.logo{font-size:17pt;font-weight:800;letter-spacing:-.8px}.logo span{font-family:Georgia,serif;color:#c32720}.edition{font-size:7.5pt;color:#666;text-align:right;max-width:95mm}.eyebrow,.label{font-size:8pt;font-weight:700;letter-spacing:.7px;color:#666;margin:0 0 2.5mm}.eyebrow{color:#b52620;text-transform:uppercase}h1{font-size:30pt;line-height:1.12;letter-spacing:-.6px;margin:0 0 5mm}h2{font-size:22pt;line-height:1.18;margin:0 0 3mm}h3{font-size:12pt;line-height:1.35;margin:0 0 2mm}p{margin:0 0 3mm}.lead{font-size:12pt;line-height:1.5;color:#555}.panel{border:1px solid #e1dfdc;border-radius:11px;padding:4mm}.warm{background:#f7f5f2}.two{display:grid;grid-template-columns:1fr 1fr;gap:5mm;align-items:start}.two>*{min-width:0}section{margin-top:5mm}.steps{display:grid;gap:4mm;counter-reset:steps}.step{display:grid;grid-template-columns:7mm 1fr;gap:2mm}.step:before{counter-increment:steps;content:counter(steps);width:6.5mm;height:6.5mm;display:flex;align-items:center;justify-content:center;border:1px solid #ddd;border-radius:50%;font-weight:700;font-size:9pt}.step h3{font-size:10pt;margin-bottom:1mm}.step p{font-size:9.5pt;margin:0}.notes{display:flex;flex-wrap:wrap;gap:2mm}.note{flex:1;min-width:9mm;text-align:center;padding:3mm 1.5mm;border:1px solid #ddd;border-radius:8px;background:#f3f3f1;font-size:15pt;font-weight:700;line-height:1.3}.note small{display:block;font-size:7pt;font-weight:400;color:#666;margin-top:1mm}.selected{background:#eaf6ef!important;border-color:#8fc6a5!important;color:#127946!important}.caption{font-size:8pt;color:#626262;margin:3mm 0 0}.answer{border:1px solid #a3cdb2;border-radius:8px;padding:3mm;background:#eaf6ef;margin-top:4mm;color:#127946}.answer .label{display:block;color:#127946}.answer-name{font-size:12pt;font-weight:700;margin:0}.explanation{font-size:9pt;margin-top:3mm}.chips{display:flex;gap:2mm;flex-wrap:wrap}.chip{display:inline-block;border:1px solid #deddd9;background:#fff;border-radius:7px;padding:1.5mm 2.5mm;font-size:8.5pt}.fields{margin-top:4mm;display:grid;gap:2.5mm}.fields>div{display:flex;justify-content:space-between;gap:3mm;font-size:9pt}.fields b{text-align:right}.bass{text-align:center;padding:3mm;background:#fff4de;border:1px solid #e4ca8b;margin-top:4mm;border-radius:8px;font-size:19pt;font-weight:700}.bass small{display:block;font-size:8pt;font-weight:400}.pair{display:flex;gap:3mm;align-items:center}.pair .notes{flex:1}.arrow{color:#888;font-size:20pt}.sequence{display:flex;gap:2mm}.sequence-cell{flex:1;min-width:0;text-align:center}.sequence-cell>small{font-size:8pt;display:block;margin-bottom:2mm}.muted-layer{border:1px solid #e1dfdc;border-radius:7px;background:#f1f1ef;color:#b6b6b2;line-height:1.2;padding:3mm;margin:2mm 0}.slot{flex:1;min-width:0;text-align:center;border:1px solid #a3cdb2;background:#eaf6ef;border-radius:8px;padding:3mm 1mm;display:flex;flex-direction:column;gap:1mm;color:#127946}.slot small{font-size:7pt}.slot b{font-size:16pt}.slot span{font-size:8pt;font-weight:700}.anchor{border:1px solid #ddd;border-radius:7px;padding:2mm;font-size:8pt;margin-bottom:3mm}.callout{border-left:3px solid #c32720;background:#fcf1ef;border-radius:0 8px 8px 0;padding:3mm 4mm}.callout p{margin:0;font-size:9pt}.about p{font-size:9.5pt;line-height:1.5;margin:0}table{border-collapse:collapse;width:100%;table-layout:fixed;font-size:9pt}th{text-align:left;color:#666;font-size:8pt;padding:2mm;border-bottom:1px solid #d9d7d3}td{padding:3mm 2mm;vertical-align:top;border-bottom:1px solid #e8e6e2}td:first-child{font-weight:700;width:36%}th:first-child{width:36%}th:last-child{width:13%}footer{position:absolute;bottom:8mm;left:17mm;right:17mm;display:flex;justify-content:space-between;gap:5mm;border-top:1px solid #e4e2de;padding-top:3mm;font-size:7.5pt;color:#777}.pool{font-size:9.5pt;columns:2;column-gap:8mm;padding-left:5mm}.pool li{break-inside:avoid;padding:2mm 0}.scale-notes{flex-wrap:nowrap;gap:1mm}.scale-notes .note{min-width:0;padding:2mm .3mm;font-size:11pt}.inversion{font-size:9pt;font-weight:700;margin-top:3mm}.note-keyboard{position:relative;height:27mm;display:flex;border:1px solid #ddd;border-radius:8px;overflow:hidden;margin-top:4mm}.white-key{flex:1;display:flex;align-items:flex-end;justify-content:center;padding-bottom:2mm;border-right:1px solid #ddd;font-size:8.5pt;font-weight:700}.black-key{position:absolute;top:0;height:60%;width:8%;background:#222;color:#fff;border-radius:0 0 4px 4px;text-align:center;padding-top:6mm;font-size:7pt}.compact h2{font-size:20pt}.compact .lead{font-size:11pt}.compact section{margin-top:4mm}.compact .step p{font-size:9pt}.compact .panel{padding:3mm}.compact .about p{font-size:9pt}.compact .chip{font-size:8pt}.compact .explanation{font-size:8.5pt}.index-row{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e5e3df;padding:2.5mm 0;gap:5mm;font-size:10pt}.index-row small{white-space:nowrap;color:#666} @media print{body{background:white}.page{margin:0}}
'''

def generate(category):
 title,slug,family,purpose,benefit=category
 guides=json.loads((DATA / (family+'Guides.json')).read_text())
 items=list(guides.items());chunks=[items[i:i+8] for i in range(0,len(items),8)]
 pages=[]
 # A short route at the front makes the lesson differences easy to locate.
 for c,chunk in enumerate(chunks):
  rows=''.join(f'<div class="index-row"><b>{esc(display(name))}</b><small>Page {len(chunks)+items.index((name,g))+1}</small></div>' for name,g in chunk)
  body=f'<p class="eyebrow">Your category guide{(" · continued" if c else "")}</p><h1>{esc(title)}</h1>'+paragraph(purpose,'lead')
  if c==0:
   body+='<section class="two"><div><h3>What you are practising</h3>'+paragraph(purpose)+'</div><div><h3>Why it helps</h3>'+paragraph(benefit)+'</div></section>'
  body+='<section class="panel warm"><h3>Find your quiz</h3>'+rows+'</section>'
  if c==0:
   body+='<section class="callout"><p><b>Start with the page for your quiz.</b><br>It explains what to listen for, what to choose, and what changes at that level. Green selections are worked examples, not answers to your next question.</p></section>'
   if family=='melodicDictation':
    body+='<section class="panel"><h3>Your sequence</h3>'+notes(['Do','Re','Mi'],True)+'<div class="fields"><div><b>Undo</b><span>Remove your last note.</span></div><div><b>Clear</b><span>Start your answer again.</span></div></div>'+paragraph('Tap the keyboard once for each note, in the order you hear it.','caption')+'</section>'
  pages.append(body)
 for name,g in items:
  body=f'<p class="eyebrow">Quiz instructions</p><h2>{esc(display(name))}</h2>'+paragraph(g['intro'],'lead')
  steps=''.join('<div class="step"><div><h3>'+esc(step['title'])+'</h3>'+paragraph(step['description'])+'</div></div>' for step in g['steps'])
  body+='<section class="two"><div class="steps">'+steps+'</div><div class="panel warm">'+diagram(g,family)+paragraph(g['exampleExplanation'],'explanation')+'</div></section>'
  body+='<section class="panel about"><h3>What changes in this quiz?</h3>'+paragraph(g['about'])+'</section>'
  choices=g.get('qualities',g.get('choices',[]))
  # Extension choices are already illustrated as selected buttons above.
  if choices and 'selected' not in g:
   body+='<section><h3>Answer choices</h3>'+chips(choices,[g.get('exampleQuality',g.get('example',''))] if isinstance(g.get('example',''),str) else [])+'</section>'
  if g.get('inversions'):
   body+='<section><h3>Also choose the position</h3>'+chips(g['inversions'],[g.get('exampleInversion')])+'</section>'
  if family=='melodicDictation':
   whites=''.join('<div class="white-key">'+n+'</div>' for n in ['Do','Re','Mi','Fa','So','La','Ti'])
   blacks=''.join(f'<div class="black-key" style="left:{pos}%">{n}</div>' for pos,n in [(10.3,'Di'),(24.6,'Ri'),(53.1,'Fi'),(67.4,'Si'),(81.7,'Li')])
   body+='<section><h3>Enter the notes on the keyboard</h3><div class="note-keyboard">'+whites+blacks+'</div>'+paragraph('Use the numbered sequence to check the order. Use Undo or Clear to change it before you submit.','caption')+'</section>'
  pages.append(body)
 # Show each extension group's complete chord pool once, rather than repeating it at every level.
 pools={}
 for name,g in items:
  if g.get('availableChords') and g.get('group') and g['group'] not in pools:pools[g['group']]=g['availableChords']
 for group,chords in pools.items():
  pages.append(f'<p class="eyebrow">Chord pool · Group {group}</p><h2>Chords in Group {group}</h2>'+paragraph('These are the chord options used by the lessons for this group. Check the lesson page for its key range and note arrangement.','lead')+'<section class="panel warm"><ul class="pool">'+''.join('<li>'+esc(chord)+'</li>' for chord in chords)+'</ul></section>')
 total=len(pages)
 articles=''.join(f'<article class="page {"compact" if i>=len(chunks) else ""}"><header><div class="logo">Khord<span>Quiz</span></div><div class="edition">{esc(title.upper())} · ILLUSTRATED GUIDE</div></header><main>{content}</main><footer><span>KhordQuiz · {esc(title)}</span><span>{i+1} / {total}</span></footer></article>' for i,content in enumerate(pages))
 output=f'<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{esc(title)} · Illustrated guide · KhordQuiz</title><style>{STYLE}</style></head><body>{articles}</body></html>'
 (ROOT/'docs/guides'/f'{slug}.html').write_text(output)
 return {'title':title,'slug':slug,'pages':total,'lessons':len(items)}

if __name__=='__main__':
 chosen=[c for c in CATEGORIES if not sys.argv[1:] or c[1]==sys.argv[1]]
 if not chosen:raise SystemExit('Unknown category slug')
 for category in chosen:print(json.dumps(generate(category)))
