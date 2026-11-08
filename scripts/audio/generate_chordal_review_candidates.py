import json,itertools,random,subprocess,csv,argparse
from functools import lru_cache
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2] if 'scripts/audio' in str(Path(__file__).resolve()) else Path('/Users/khord/Documents/GitHub/Khordquizwebsite/khordquiz')
OUT=ROOT/'docs/composition'
parser=argparse.ArgumentParser(description='Generate review candidates without changing quiz content.')
parser.add_argument('--batch', type=int, choices=[1,2], default=1)
BATCH=parser.parse_args().batch
TAG='' if BATCH==1 else '-batch-2'
STEM='chordal-melody-100-candidates'+TAG
RNG=random.Random(20261105+BATCH)
FORM={'maj':[0,4,7],'min':[0,3,7],'dim':[0,3,6],'aug':[0,4,8],'maj7':[0,4,7,11],'min7':[0,3,7,10],'dom7':[0,4,7,10],'m7b5':[0,3,6,10],'dim7':[0,3,6,9],'aug7':[0,4,8,10],'minMaj7':[0,3,7,11],'dimMaj7':[0,3,6,11],'dom7b5':[0,4,6,10],'maj7aug':[0,4,8,11],'maj7b5':[0,4,6,11]}
ROOTS={'C':0,'C#':1,'Db':1,'D':2,'Eb':3,'E':4,'F':5,'F#':6,'G':7,'Ab':8,'A':9,'Bb':10,'B':11}
SUFFIX={'maj':'','min':'m','dim':'dim','aug':'aug','maj7':'maj7','min7':'m7','dom7':'7','m7b5':'m7b5','dim7':'dim7','aug7':'aug7','minMaj7':'mMaj7','dimMaj7':'dimMaj7','dom7b5':'7b5','maj7aug':'maj7#5','maj7b5':'maj7b5'}
CH={}
# Explicit letter-correct chord spellings for every chord used.
DEFS=[('C','maj','C E G'),('D','min','D F A'),('E','min','E G B'),('F','maj','F A C'),('G','maj','G B D'),('A','min','A C E'),('B','dim','B D F'),('C','maj7','C E G B'),('D','min7','D F A C'),('E','min7','E G B D'),('F','maj7','F A C E'),('G','dom7','G B D F'),('A','min7','A C E G'),('B','m7b5','B D F A'),('D','maj','D F# A'),('E','maj','E G# B'),('A','maj','A C# E'),('Bb','maj','Bb D F'),('F','min','F Ab C'),('G','min','G Bb D'),('Eb','maj7','Eb G Bb D'),('Ab','maj7','Ab C Eb G'),('Bb','maj7','Bb D F A'),('Bb','dom7','Bb D F Ab'),('C','dom7','C E G Bb'),('B','dom7','B D# F# A'),('E','dom7','E G# B D'),('F','dom7','F A C Eb'),('A','dom7','A C# E G'),('D','dom7','D F# A C'),('Eb','min7','Eb Gb Bb Db'),('F','min7','F Ab C Eb'),('G','min7','G Bb D F'),('C#','m7b5','C# E G B'),('D','m7b5','D F Ab C'),('E','m7b5','E G Bb D'),('F','m7b5','F Ab Cb Eb'),('F#','m7b5','F# A C E'),('A','m7b5','A C Eb G'),('C','dim7','C Eb Gb Bbb'),('C#','dim7','C# E G Bb'),('Eb','dim7','Eb Gb Bbb Dbb'),('E','dim7','E G Bb Db'),('F','dim7','F Ab Cb Ebb'),('B','dim7','B D F Ab'),('C','dimMaj7','C Eb Gb B'),('Eb','dimMaj7','Eb Gb Bbb D'),('F','dimMaj7','F Ab Cb E'),('Bb','dimMaj7','Bb Db Fb A'),('F','minMaj7','F Ab C E'),('Ab','minMaj7','Ab Cb Eb G'),('C','maj7b5','C E Gb B'),('Db','maj7b5','Db F Abb C'),('Eb','maj7b5','Eb G Bbb D'),('F','maj7b5','F A Cb E'),('Ab','maj7b5','Ab C Ebb G'),('Bb','maj7b5','Bb D Fb A')]
NAT={'C':0,'D':2,'E':4,'F':5,'G':7,'A':9,'B':11}
def raw(note):return NAT[note[0]]+note[1:].count('#')-note[1:].count('b')
for root,q,spell in DEFS:
 key=root+SUFFIX[q];notes=spell.split();pcs=[(ROOTS[root]+x)%12 for x in FORM[q]]
 assert [raw(x)%12 for x in notes]==pcs,(root,q,notes,pcs)
 CH[key]={'root':root,'quality':q,'root_pc':ROOTS[root],'spellings':notes,'pcs':pcs}
DIAT=set(k for k,v in CH.items() if set(v['pcs'])<=set([0,2,4,5,7,9,11]))
DIA_EDGES={
 'C':['Dm','Dm7','Em','Em7','F','Fmaj7','G','G7','Am','Am7'],
 'Cmaj7':['Dm7','Em7','Fmaj7','G7','Am7'],
 'Dm':['G','G7','F','C'], 'Dm7':['G7','Fmaj7','C','Cmaj7'],
 'Em':['Am','Am7','F','Fmaj7'], 'Em7':['Am7','Fmaj7'],
 'F':['G','G7','Dm','Dm7','C'], 'Fmaj7':['G7','Dm7','C','Cmaj7'],
 'G':['C','Am','Am7'], 'G7':['C','Cmaj7'],
 'Am':['Dm','Dm7','F','Fmaj7','G7'], 'Am7':['Dm7','Fmaj7','G7'],
 'Bdim':['C'], 'Bm7b5':['C','Cmaj7']}
PALETTE={
 'D':['G','F'],'E':['Am'],'A':['Dm'],'Bb':['F'],'Fm':['C'],'Gm':['F','Fm'],
 'Ebmaj7':['Dm'],'Abmaj7':['G','G7'],'Bbmaj7':['F'],
 'Bb7':['F','Fmaj7','C','Cmaj7','Am7'],'C7':['F','Fmaj7','Dm7b5'],
 'B7':['G','G7','F','Fmaj7','Dm7b5'],'E7':['Am','Am7','A7','Fmaj7'],'F7':['C'],'A7':['Dm','Dm7b5'],
 'Ebm7':['Dm7'],'Fm7':['C'],'Gm7':['C7','F#m7b5'],
 'C#m7b5':['Dm7'],'Dm7b5':['C','Em7b5','Am7b5'],
 'Em7b5':['Dm7b5','A','A7','Am','Fmaj7','F'],'Fm7b5':['Em'],
 'F#m7b5':['Fmaj7','Dm7b5','G7','F'],'Am7b5':['Dm7b5'],
 'Cdim7':['C','Fmaj7','Am7','Dm7'],'C#dim7':['Dm','Dm7','Dm7b5','D7'],
 'Ebdim7':['C','C7'],'Edim7':['Dm7','Dm7b5','D7'],'Fdim7':['C','F#m7b5'],'Bdim7':['C','Cmaj7'],
 'CdimMaj7':['Cdim7'],'EbdimMaj7':['Dm7'],'FdimMaj7':['Em7','C','Fdim7'],
 'BbdimMaj7':['Am7','Fmaj7'],'FmMaj7':['FdimMaj7','Em7','C','Cmaj7','Am7'],
 'AbmMaj7':['C','Cmaj7','Fm7b5'],'Cmaj7b5':['Dm7','Bbmaj7'],
 'Dbmaj7b5':['C','Cmaj7'],'Ebmaj7b5':['Dm','Dm7'],'Fmaj7b5':['Em','C','Cmaj7'],
 'Abmaj7b5':['Dm7b5'],'Bbmaj7b5':['Fmaj7','F']}
# Defined, repeatable routes into the chromatic approach; they are context choices,
# not a new claim that every adjacent diatonic/chromatic pair has dominant function.
ENTRY={
 'D':['C','Am','F'],'E':['C','G','Em'],'A':['C','F','Am'],
 'Bb':['C','F'],'Fm':['C','F'],'Gm':['C','F'],
 'Ebmaj7':['C','F'],'Abmaj7':['C','F'],'Bbmaj7':['C','F'],
 'Bb7':['C','Dm7','F'],'C7':['C','Cmaj7','Gm7'],
 'B7':['C','Am','Em'],'E7':['C','Em','G'],'F7':['F','Fmaj7','C'],
 'A7':['C','Am','F'],'Ebm7':['C','Em7','F'],
 'Fm7':['F','Fmaj7','C'],'Gm7':['C','Dm7','F'],
 'C#m7b5':['C','Am7'],'Dm7b5':['Dm7','F','C7'],
 'Em7b5':['Em7','C','F'],'Fm7b5':['Fm7','Fmaj7','C'],
 'F#m7b5':['Fmaj7','C','Gm7'],'Am7b5':['Am7','F','C'],
 'Cdim7':['C','Cmaj7'],'C#dim7':['C','Cmaj7'],
 'Ebdim7':['Em','C'],'Edim7':['Em','C'],'Fdim7':['F','C'],
 'Bdim7':['C','G7'],'CdimMaj7':['C','Cmaj7'],'EbdimMaj7':['Em7','C'],
 'FdimMaj7':['Fmaj7','C'],'BbdimMaj7':['Fmaj7','C'],
 'FmMaj7':['Fmaj7','C'],'AbmMaj7':['Am7','C'],
 'Cmaj7b5':['Cmaj7','C'],'Dbmaj7b5':['Dm7','C'],
 'Ebmaj7b5':['Em7','C'],'Fmaj7b5':['Fmaj7','C'],
 'Abmaj7b5':['C','Am7'],'Bbmaj7b5':['C','Fmaj7']}
ALL={k:list(v) for k,v in DIA_EDGES.items()}
for k,v in PALETTE.items():ALL[k]=list(dict.fromkeys(ALL.get(k,[])+v))
# Named motif families keep the collection varied, rather than 100 inversion swaps.
MOTIFS=[]
for source,targets in PALETTE.items():
 for target in targets:
  MOTIFS.append((source+' → '+target,[source,target]))
# Explicit longer owner-confirmed chains.
MOTIFS += [('minor-plagal chain',['Gm','Fm','C']),('diminished-major chain',['CdimMaj7','Cdim7','Dm7']),('secondary-dominant chain',['E7','A7','Dm']),('half-diminished chain',['Dm7b5','Am7b5','Dm7b5','C']),('half-diminished exchange',['Em7b5','Dm7b5','C']),('major-flat-five colour',['Cmaj7b5','Bbmaj7','F']),('minor-major descent',['FmMaj7','FdimMaj7','Fdim7','C'])]
@lru_cache(None)
def voicings(key):
 c=CH[key];pcs=set(c['pcs']);possible=[m for m in range(48,73) if m%12 in pcs];out=[]
 for v in itertools.combinations(possible,4):
  if set(x%12 for x in v)!=pcs:continue
  if v[-1]-v[0]>16 or not 60<=v[-1]<=72 or v[0]>60:continue
  if max(b-a for a,b in zip(v,v[1:]))>9:continue
  if len(pcs)==3:
   doubled=[pc for pc in pcs if sum(x%12==pc for x in v)==2]
   if doubled and doubled[0]==c['pcs'][1]:continue # avoid doubled thirds for triads
  out.append(v)
 assert out,key
 return out

def transcost(a,b):
 bass=b[0]-a[0];top=b[-1]-a[-1]
 if abs(bass)>7 or abs(top)>7:return None
 motion=[abs(x-y) for x,y in zip(a,b)]
 if max(motion)>9:return None
 return sum(motion)+abs(top)*1.7+abs(bass)*.5+(6 if top==0 else 0)+(max(0,abs(top)-4)*4)+max(0,abs(bass)-5)*2

def phrase_voicings(seq,used):
 # Dynamic programming, with several top-note contour preferences. All versions
 # undergo the same independent checks; only one version of a harmony is selected.
 best_options=[]
 for attempt in range(5):
  target=[RNG.choice([64,65,67,69])]
  for _ in seq[1:]:target.append(max(60,min(72,target[-1]+RNG.choice([-3,-2,-1,1,2,3]))))
  last={}
  for v in voicings(seq[0]):last[v]=(abs(v[-1]-target[0])*2+abs(sum(v)/4-59)*.7,[v])
  for j,key in enumerate(seq[1:],1):
   current={}
   for b in voicings(key):
    opt=None
    for a,(score,path) in last.items():
     cost=transcost(a,b)
     if cost is None:continue
     cand=(score+cost+abs(b[-1]-target[j])*1.3,path+[b])
     if opt is None or cand[0]<opt[0]:opt=cand
    if opt:current[b]=opt
   last=current
  for score,path in sorted(last.values(),key=lambda x:x[0])[:20]:
   mel=tuple(v[-1] for v in path)
   # Unique by pitch-class melody as well as register; avoid octave-only duplicates.
   signature=tuple(x%12 for x in mel)
   if signature in used or len(set(mel))<3:continue
   if any(mel[i]==mel[i+1]==mel[i+2] for i in range(len(mel)-2)):continue
   best_options.append((score,path,signature))
  if best_options:break
 return min(best_options,key=lambda x:x[0]) if best_options else None

def dia_prefix(length,last):
 if length==0:return [[]]
 out=[]
 for seq in itertools.product(['C','Cmaj7','Dm7','Em7','F','Fmaj7','G7','Am7'],repeat=length):
  if seq[0] not in ['C','Cmaj7'] and length>1:continue
  if any(b not in DIA_EDGES.get(a,[]) for a,b in zip(seq,seq[1:])):continue
  if last not in ENTRY or seq[-1] not in ENTRY[last]:continue
  out.append(list(seq))
 return out

def suffixes(start,length):
 if length==0:return [[]] if start in ['C','Cmaj7'] else []
 out=[]
 def walk(current,path):
  if len(path)==length:
   if current in ['C','Cmaj7']:out.append(path)
   return
  for nxt in ALL.get(current,[]):
   if nxt==current:continue
   walk(nxt,path+[nxt])
 walk(start,[])
 return out

CAND={5:[],6:[]}
for length in [5,6]:
 seen=set()
 for label,motif in MOTIFS:
  for plen in range(1,min(3,length-len(motif))+1):
   slen=length-len(motif)-plen
   for pre in dia_prefix(plen,motif[0]):
    for suf in suffixes(motif[-1],slen):
     seq=pre+motif+suf
     if tuple(seq) in seen:continue
     if seq[0] not in ['C','Cmaj7','F','Fmaj7','Dm7','Em7','Am7','G7']:continue
     if seq[-1] not in ['C','Cmaj7']:continue
     if any(a==b for a,b in zip(seq,seq[1:])):continue
     non=sum(k not in DIAT for k in seq)
     dense=motif==['FmMaj7','FdimMaj7','Fdim7','C']
     if dense:
      if length!=6 or non!=3:continue
     elif non>2 or non>=length/2:continue
     if sum(CH[k]['quality']=='dim7' for k in seq)>1:continue
     # Only the entry into the named motif may be a new contextual approach.
     seen.add(tuple(seq));CAND[length].append({'seq':seq,'device':label,'non':non,'dense':dense,'entry_index':len(pre)-1,'motif_start':len(pre),'motif_length':len(motif)})
print('Candidates:',{k:len(v) for k,v in CAND.items()})
# Prefer an equal family spread, including all listed starting-quality groups.
RESERVED={
 5:[
  (['C','Cmaj7b5','Bbmaj7','F','C'],'major-flat-five colour'),
  (['C','F','Em7b5','Dm7b5','C'],'half-diminished exchange'),
  (['C','FmMaj7','Em7','Fmaj7','Cmaj7'],'FmMaj7 → Em7'),
  (['C','Ebm7','Dm7','G7','C'],'Ebm7 → Dm7'),
  (['C','Bb7','Am7','G7','C'],'Bb7 → Am7'),
  (['C','Bb7','Cmaj7','Fmaj7','C'],'Bb7 → Cmaj7'),
  (['C','Bbmaj7','F','G7','C'],'Bbmaj7 → F'),
  (['C','F','A7','Dm7b5','C'],'A7 → Dm7b5'),
 ],
 6:[
  (['C','F','FmMaj7','FdimMaj7','Fdim7','C'],'minor-major descent'),
  (['C','F','Dm7b5','Am7b5','Dm7b5','C'],'half-diminished round-trip'),
 ]}
# Compare against the existing literal pool by root pitch class and quality,
# so enharmonic spellings cannot disguise an existing progression.
legacy_output=subprocess.run(['php','-r', 'require "scripts/audio/chordal_melodies_pool.php"; $rows=chordalLiteralProgressions(); echo json_encode(["steps"=>$rows,"melodies"=>array_map(fn($s)=>array_map(fn($v)=>max($v),chordalBuildVoicings($s)),$rows)]);'],cwd=ROOT,capture_output=True,text=True,check=True)
LEGACY_DATA=json.loads(legacy_output.stdout)
LEGACY=LEGACY_DATA['steps']
LEGACY_MELODIES={tuple(x%12 for x in melody) for melody in LEGACY_DATA['melodies']}
LEGACY_SIGNATURES={tuple((x['root'],x['quality']) for x in seq) for seq in LEGACY}
def harmonic_signature(seq):return tuple((CH[k]['root_pc'],CH[k]['quality']) for k in seq)
PRIOR=[]
if BATCH==2:
 PRIOR=json.loads((OUT/'chordal-melody-100-candidates.json').read_text())['sequences']
 assert len(PRIOR)==100, 'Batch one must be available before generating batch two.'
 LEGACY_SIGNATURES.update(harmonic_signature(row['chords']) for row in PRIOR)
 LEGACY_MELODIES.update(tuple(x%12 for x in row['melody_midi']) for row in PRIOR)
def family(row):
 q=CH[row['seq'][row['motif_start']]]['quality']
 return q
SELECTED=[];usedmel=set(LEGACY_MELODIES);usedharmony={tuple(row['chords']) for row in PRIOR};quality_counts={};device_counts={}
for length in [5,6]:
 pool=CAND[length][:];RNG.shuffle(pool);chosen=[]
 for original,label in RESERVED[length]:
  if label in ['minor-major descent','half-diminished round-trip']:
   start=2;motiflength=4
  elif label=='major-flat-five colour':start=1;motiflength=3
  elif label=='half-diminished exchange':start=2;motiflength=2
  elif label=='A7 → Dm7b5':start=2;motiflength=2
  else:start=1;motiflength=2
  motif=original[start:start+motiflength]
  seq=list(original)
  if tuple(seq) in usedharmony:
   if label in ['minor-major descent','half-diminished round-trip']:
    seq[0]='Cmaj7'
   else:
    alternatives=[row['seq'] for row in pool if any(row['seq'][j:j+motiflength]==motif for j in range(len(row['seq'])-motiflength+1)) and tuple(row['seq']) not in usedharmony and harmonic_signature(row['seq']) not in LEGACY_SIGNATURES]
    if not alternatives:
     continue # Do not recycle a previous harmony merely to fill a motif quota.
    seq=list(alternatives[0])
  start=next(j for j in range(len(seq)-motiflength+1) if seq[j:j+motiflength]==motif)
  row={'seq':seq,'device':label,'non':sum(k not in DIAT for k in seq),'dense':label in ['minor-major descent','half-diminished round-trip'],'entry_index':start-1,'motif_start':start,'motif_length':motiflength}
  assert harmonic_signature(seq) not in LEGACY_SIGNATURES
  result=phrase_voicings(seq,usedmel)
  assert result,seq
  score,path,signature=result
  chosen.append(dict(row,voicings=path,score=round(score,2)))
  usedmel.add(signature);usedharmony.add(tuple(seq))
  quality_counts[family(row)]=quality_counts.get(family(row),0)+1
  device_counts[label]=device_counts.get(label,0)+1
 while len(chosen)<50:
  available=[x for x in pool if tuple(x['seq']) not in usedharmony and harmonic_signature(x['seq']) not in LEGACY_SIGNATURES and not x['dense'] and device_counts.get(x['device'],0)<3]
  if not available:raise RuntimeError('Not enough distinct candidates')
  available.sort(key=lambda x:(0 if x['dense'] and not any(y['dense'] for y in chosen) else 1,quality_counts.get(family(x),0),device_counts.get(x['device'],0),sum(k not in DIAT for k in x['seq'])))
  found=False
  for row in available[:200]:
   result=phrase_voicings(row['seq'],usedmel)
   if not result:
    pool.remove(row);continue
   score,path,signature=result
   row=dict(row,voicings=path,score=round(score,2));chosen.append(row)
   usedmel.add(signature);usedharmony.add(tuple(row['seq']))
   quality_counts[family(row)]=quality_counts.get(family(row),0)+1
   device_counts[row['device']]=device_counts.get(row['device'],0)+1
   found=True;break
  if not found:raise RuntimeError('No viable voicing candidates')
 SELECTED.extend(chosen)
 print('Selected',length,len(chosen))

def spell_midi(m,c):
 n=next(n for n in c['spellings'] if raw(n)%12==m%12)
 octave=(m-raw(n))//12-1
 assert raw(n)+12*(octave+1)==m
 return n+str(octave)
def concert_midi(m):return ['C','Db','D','Eb','E','F','F#','G','Ab','A','Bb','B'][m%12]+str(m//12-1)
SOL=['Do','Di','Re','Ri','Mi','Fa','Fi','So','Si','La','Li','Ti']
INV=['Root position','1st inversion','2nd inversion','3rd inversion']
records=[]
for i,row in enumerate(SELECTED,1+(BATCH-1)*100):
 steps=[]
 for j,(key,v) in enumerate(zip(row['seq'],row['voicings'])):
  c=CH[key];notes=[spell_midi(m,c) for m in v];idx=c['pcs'].index(v[0]%12)
  steps.append({'position':j+1,'chord':key,'root':c['root'],'quality':c['quality'],'diatonic':key in DIAT,'inversion':INV[idx],'voicing_notes':notes,'voicing_midi':list(v),'bass_note':notes[0],'melody_note':notes[-1],'melody_midi':v[-1],'melody_solfege':SOL[v[-1]%12]})
 m=[s['melody_midi'] for s in steps]
 explanation='Owner-specified colour connection: '+row['device']+'. Keep this a local colour route inside the surrounding C-major phrase; it is not automatically a textbook dominant resolution.'
 source=row['seq'][row['motif_start']];target=row['seq'][row['motif_start']+1]
 targetpc=CH[target]['root_pc'];s=CH[source]
 if s['quality'] in ['maj','dom7'] and (s['root_pc']-targetpc)%12==7:
  explanation=f'{source} approaches {target} as its applied dominant. The phrase continues to C as the overall home.'
 if row['device']=='half-diminished round-trip':explanation='Confirmed Dm7b5 → Am7b5 → Dm7b5 → C chain, framed by C and F. The reciprocal half-diminished connection uses three chromatic events, so it is marked as an exception to the majority guideline.'
 if row['device']=='minor-major descent':explanation='Confirmed chain FmMaj7 → FdimMaj7 → Fdim7 → C. At F, the fifth falls C→Cb, then E→Ebb lowers the seventh; the phrase returns to C. This is the marked three-chromatic-event exception.'
 rec={'id':f'CM-{i:03d}','length':len(steps),'key':'C major','status':'draft_for_listening_review','device':row['device'],'harmonic_explanation':explanation,'diatonic_events':len(steps)-row['non'],'non_diatonic_events':row['non'],'dense_exception':row['dense'],'chords':row['seq'],'melody_midi':m,'melody_notes':[concert_midi(x) for x in m],'melody_solfege':[SOL[x%12] for x in m],'melody_semitones':[b-a for a,b in zip(m,m[1:])],'bass_semitones':[b['voicing_midi'][0]-a['voicing_midi'][0] for a,b in zip(steps,steps[1:])],'steps':steps}
 records.append(rec)
# Independent validation: formula coverage, absolute notes, inversion, range, steps.
for rec in records:
 assert harmonic_signature(rec['chords']) not in LEGACY_SIGNATURES
 assert tuple(x%12 for x in rec['melody_midi']) not in LEGACY_MELODIES
 assert rec['length'] in [5,6] and len(rec['steps'])==rec['length']
 assert rec['chords'][-1] in ['C','Cmaj7']
 assert max(map(abs,rec['melody_semitones']))<=7
 assert max(map(abs,rec['bass_semitones']))<=7
 assert sum(x['quality']=='dim7' for x in rec['steps'])<=1
 for source,target in zip(rec['chords'],rec['chords'][1:]):
  if source not in DIAT:assert target in PALETTE[source],(source,target)
 assert rec['diatonic_events']>rec['non_diatonic_events'] or rec['dense_exception']
 for step in rec['steps']:
  v=step['voicing_midi'];c=CH[step['chord']]
  assert len(v)==4 and len(set(v))==4 and sorted(v)==v
  assert set(m%12 for m in v)==set((c['root_pc']+x)%12 for x in FORM[c['quality']])
  assert min(v)>=48 and max(v)<=72 and max(v)-min(v)<=16
  assert step['melody_midi']==max(v)
  assert step['inversion']==INV[c['pcs'].index(min(v)%12)]
  for note,m in zip(step['voicing_notes'],v):
   pitch=note[:-1];octave=int(note[-1]);assert raw(pitch)+12*(octave+1)==m
assert len(records)==100 and len({tuple(r['chords']) for r in records})==100
assert len({tuple(x%12 for x in r['melody_midi']) for r in records})==100
OUT.mkdir(parents=True,exist_ok=True)
data={'title':'100 chordal-melody candidates in C major','version':f'2026-11-06-review-batch-{BATCH}','batch':BATCH,'status':'Review only; not imported into quizzes; no rendered audio generated','counts':{'five_events':50,'six_events':50},'register':'MIDI 48–72, C4=60','triad_model':'Four notes: all three defining pitch classes, with root or fifth doubled; sevenths have all four defining pitch classes.','conventions':['Use Dm7b5, not Fm6.','One dim7 event maximum.','All phrases end on C or Cmaj7.','All melodies distinct by pitch class; all harmonic sequences distinct.','All except two explicitly flagged six-event chains have a strict majority of diatonic chord events; those chains have three diatonic and three chromatic events.','Theoretical note spellings and sounding melody names may be enharmonic.'],'quality_formulas':FORM,'approved_connections':PALETTE,'sequences':records}
(OUT/(STEM+'.json')).write_text(json.dumps(data,ensure_ascii=False,indent=2))
lines=[f'# 100 chordal-melody candidates in C major · Batch {BATCH}','', '**Review draft · not added to the quizzes.** 50 five-event phrases and 50 six-event phrases. Each event is one block chord; its highest note is the melody.','', 'Every chord is voiced with four notes within C3–C5. Triads double a root or fifth; seventh chords retain all four defining tones. Note names show theoretical spelling; the separate melody line uses familiar sounding names.','', 'Two six-event phrases carry a marked three-chromatic-event exception: FmMaj7 → FdimMaj7 → Fdim7 → C and Dm7b5 → Am7b5 → Dm7b5 → C. Both are framed by C and F. The other 98 phrases have a strict majority of diatonic events.','', 'These are pitch- and rule-checked candidates. Harmonic explanations for unusual routes describe the owner’s palette, not universal functional rules. Listen and approve in batches before any import.','']
for rec in records:
 lines += [f"## {rec['id']} · {rec['length']} events",'', '**Chords:** '+' → '.join(rec['chords']), '**Melody:** '+' → '.join(rec['melody_notes']), '**Solfege:** '+' · '.join(rec['melody_solfege']), f"**Diatonic / chromatic:** {rec['diatonic_events']} / {rec['non_diatonic_events']}"+(' · **Approved-chain exception to majority guideline**' if rec['dense_exception'] else ''),'', '| Step | Chord | Inversion | Notes, low → high | MIDI |','|---|---|---|---|---|']
 for step in rec['steps']:lines.append('| '+ ' | '.join([str(step['position']),step['chord'],step['inversion'],' · '.join(step['voicing_notes']),' '.join(map(str,step['voicing_midi']))])+' |')
 lines += ['',rec['harmonic_explanation'],'',f"Melody movements: {rec['melody_semitones']} semitones. Bass movements: {rec['bass_semitones']} semitones.",'']
(OUT/(STEM+'.md')).write_text('\n'.join(lines))
with (OUT/('chordal-melody-100-overview'+TAG+'.csv')).open('w') as file:
 writer=csv.writer(file)
 writer.writerow(['ID','Events','Progression','Melody','Solfege','Diatonic events','Chromatic events','Exception'])
 for row in records:
  writer.writerow([row['id'],row['length'],' -> '.join(row['chords']),' -> '.join(row['melody_notes']),' '.join(row['melody_solfege']),row['diatonic_events'],row['non_diatonic_events'],row['dense_exception']])

report={'batch':BATCH,'prior_generated_sequences_compared':len(PRIOR),'total_sequences':len(records),'lengths':{'5':sum(r['length']==5 for r in records),'6':sum(r['length']==6 for r in records)},'unique_harmonic_sequences':100,'existing_literal_pool_compared':len(LEGACY),'duplicates_of_existing_harmonies':sum(harmonic_signature(r['chords']) in LEGACY_SIGNATURES for r in records),'unique_pitch_class_melodies':100,'duplicates_of_existing_pitch_class_melodies':sum(tuple(x%12 for x in r['melody_midi']) in LEGACY_MELODIES for r in records),'all_chromatic_exits_match_approved_connections':True,'all_note_spellings_match_midi':True,'all_chord_formulas_complete':True,'all_inversions_match_bass':True,'midi_min':min(m for r in records for s in r['steps'] for m in s['voicing_midi']),'midi_max':max(m for r in records for s in r['steps'] for m in s['voicing_midi']),'max_melody_leap':max(abs(x) for r in records for x in r['melody_semitones']),'max_individual_voice_leap':max(abs(b-a) for r in records for left,right in zip(r['steps'],r['steps'][1:]) for a,b in zip(left['voicing_midi'],right['voicing_midi'])),'max_bass_leap':max(abs(x) for r in records for x in r['bass_semitones']),'dense_exception_ids':[r['id'] for r in records if r['dense_exception']],'family_distribution':quality_counts,'listening_review':'pending','imported_into_quizzes':False}
(OUT/('chordal-melody-100-validation'+TAG+'.json')).write_text(json.dumps(report,indent=2))
print(json.dumps(report,indent=2))
