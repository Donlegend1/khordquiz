import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { useEffect, useRef } from 'react';
import illustratedGuides from '@/data/illustratedGuides.json';
import { useTrans } from '@/lib/i18n';

export default function IllustratedGuideDialog({ open, onClose, guide = illustratedGuides['Chord Tone Recognition'] }) {
    const t = useTrans();
    const reader = useRef(null);
    const { title, pages, slug } = guide ?? illustratedGuides['Chord Tone Recognition'];

    useEffect(() => {
        if (!open) return;
        const receive = (event) => {
            if (event.origin !== window.location.origin || event.source !== reader.current?.contentWindow) return;
            if (event.data?.type === 'guide-close') onClose();
        };
        window.addEventListener('message', receive);
        return () => window.removeEventListener('message', receive);
    }, [open, onClose]);

    return (
        <Dialog open={open} onClose={onClose} className="relative z-[100]">
            <div className="fixed inset-0 bg-black/50" aria-hidden="true" />
            <div className="fixed inset-0 flex items-center justify-center sm:p-6">
                <DialogPanel className="flex h-[100dvh] w-full flex-col overflow-hidden bg-white sm:h-[92dvh] sm:max-w-5xl sm:rounded-2xl sm:shadow-2xl">
                    <header className="flex shrink-0 items-center justify-between gap-3 border-b border-neutral-200 px-4 py-3 sm:px-6" style={{ paddingTop: 'max(0.75rem, env(safe-area-inset-top))' }}>
                        <div className="min-w-0">
                            <DialogTitle className="text-base font-bold text-neutral-900 sm:text-xl">{t(title)}</DialogTitle>
                            <p className="mt-0.5 text-xs text-neutral-500 sm:text-sm">{t('Illustrated PDF guide · :pages pages', { pages })}</p>
                        </div>
                        <button type="button" data-autofocus onClick={onClose} className="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-neutral-700 hover:bg-neutral-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" aria-label={t('Close illustrated guide')}>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-5 w-5"><path d="m6 6 12 12M18 6 6 18" /></svg>
                            {t('Close')}
                        </button>
                    </header>
                    <iframe ref={reader} key={slug} src={`/guides/${slug}-reader.html`} title={t(':title illustrated PDF guide', { title: t(title) })} className="min-h-0 w-full flex-1 border-0" />
                </DialogPanel>
            </div>
        </Dialog>
    );
}
