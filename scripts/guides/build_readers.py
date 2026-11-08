"""Generate the website readers from the shared template and guide registry."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
registry = json.loads((ROOT / 'resources/js/data/illustratedGuides.json').read_text())
template = (ROOT / 'scripts/guides/pdf-reader.html').read_text()
for guide in registry.values():
    reader = template.replace('Chord Tone Recognition guide', guide['title'] + ' guide')
    reader = reader.replace('const library = @@LIBRARY@@;\nconst worker = @@WORKER@@;\nconst pdf = @@PDF@@;\n', '')
    reader = reader.replace("const send = (data) => window.ReactNativeWebView?.postMessage(JSON.stringify(data));", "const send = (data) => window.parent.postMessage(data, window.location.origin);\nwindow.addEventListener('keydown', (event) => { if (event.key === 'Escape') send({type:'guide-close'}); });")
    start = reader.index(' const pdfjs=await import(')
    end = reader.index(' await render();', start)
    reader = reader[:start] + " const pdfjs=await import('./pdfjs/pdf.min.mjs');\n pdfjs.GlobalWorkerOptions.workerSrc=new URL('./pdfjs/pdf.worker.min.mjs',window.location.href).href;\n documentPdf=await pdfjs.getDocument({url:'./" + guide['slug'] + ".pdf',isEvalSupported:false,useWasm:false}).promise;\n" + reader[end:]
    reader = reader.replace('Go back and try again.', 'Close it and try again.')
    (ROOT / 'public/guides' / (guide['slug'] + '-reader.html')).write_text(reader)
print(f'Updated {len(registry)} web readers with the standard controls.')
