import stats from '@/data/playableAudioStats.json';
import Eyebrow from '@/Components/Site/Eyebrow';

export default function AudioLibrary() {
    const rounded = Math.floor(stats.examples / 1000) * 1000;
    const count = rounded > 0 ? `${rounded.toLocaleString('en-US')}+` : stats.examples.toLocaleString('en-US');

    return (
        <section className="bg-white px-6 py-16 sm:py-20" aria-labelledby="audio-library-heading">
            <div className="mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-neutral-900 px-6 py-10 text-white sm:px-10 sm:py-14 lg:px-14">
                <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <Eyebrow variant="dark">Explore the sounds</Eyebrow>
                        <h2 id="audio-library-heading" className="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">More ways to listen.<br />More room to grow.</h2>
                        <p className="mt-5 max-w-lg text-base leading-relaxed text-neutral-300">Explore notes, melodies, chords and progressions in a wide range of instrument sounds. Each live-mix example brings individual instrument notes together as you listen.</p>
                    </div>
                    <div>
                        <p className="text-5xl font-bold tracking-tight sm:text-6xl">{count}</p>
                        <p className="mt-3 text-lg font-semibold">Playable audio samples</p>
                        <div className="mt-7 flex items-center gap-4 border-t border-white/15 pt-6">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10 text-xl" aria-hidden="true">♫</span>
                            <p className="text-neutral-300"><strong className="text-white">{stats.instruments} instrument sounds</strong><br />Different sounds. New listening challenges.</p>
                        </div>
                        <p className="mt-6 text-sm leading-relaxed text-neutral-400">Counted from available quiz examples, with shared examples counted once. Sound choices vary by quiz.</p>
                    </div>
                </div>
            </div>
        </section>
    );
}
