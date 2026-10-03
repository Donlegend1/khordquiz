// TODO: replace with the live store listings once published.
const APP_STORE_URL = '#';
const PLAY_STORE_URL = '#';

export default function StoreBadges() {
    return (
        <section id="download" className="scroll-mt-[88px] bg-white">
            <div className="mx-auto max-w-7xl px-6 py-16 lg:px-[100px] lg:py-20">
                <div
                    className="relative overflow-hidden rounded-3xl px-6 py-12 text-center sm:px-12 lg:flex lg:items-center lg:justify-between lg:gap-10 lg:px-16 lg:py-14 lg:text-left"
                    style={{
                        backgroundColor: '#B3231C',
                        backgroundImage: [
                            'linear-gradient(to right, rgba(255, 255, 255, 0.06) 1px, transparent 1px)',
                            'linear-gradient(to bottom, rgba(255, 255, 255, 0.06) 1px, transparent 1px)',
                            'linear-gradient(135deg, #C1271F 0%, #8E1A14 100%)',
                        ].join(', '),
                        backgroundSize: '48px 48px, 48px 48px, 100% 100%',
                    }}
                >
                    <div>
                        <span className="inline-block rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs text-white sm:text-sm">
                            iOS &amp; Android
                        </span>
                        <h2 className="mt-5 text-3xl font-bold leading-tight tracking-tight text-white sm:text-4xl">
                            Available on the{' '}
                            <span className="font-display font-semibold tracking-normal">App Store</span>
                            <br className="hidden sm:inline" /> and{' '}
                            <span className="font-display font-semibold tracking-normal">Google Play.</span>
                        </h2>
                        <p className="mx-auto mt-4 max-w-[460px] text-base leading-relaxed text-white/80 lg:mx-0">
                            Download KhordQuiz, start your 7-day free trial, and train your ear wherever you go.
                        </p>
                    </div>

                    <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row lg:mt-0 lg:shrink-0 lg:flex-col">
                        <StoreButton href={APP_STORE_URL} eyebrow="Download on the" store="App Store" icon={<AppleIcon />} />
                        <StoreButton href={PLAY_STORE_URL} eyebrow="Get it on" store="Google Play" icon={<PlayIcon />} />
                    </div>
                </div>
            </div>
        </section>
    );
}

function StoreButton({ href, eyebrow, store, icon }) {
    return (
        <a
            href={href}
            className="flex w-[200px] items-center gap-3 rounded-xl border border-white/20 bg-black px-4 py-2.5 text-left text-white transition hover:bg-neutral-900"
        >
            {icon}
            <span>
                <span className="block text-[11px] uppercase leading-tight tracking-wide text-white/80">
                    {eyebrow}
                </span>
                <span className="block text-xl font-semibold leading-tight">{store}</span>
            </span>
        </a>
    );
}

function AppleIcon() {
    return (
        <svg className="h-7 w-7 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701" />
        </svg>
    );
}

function PlayIcon() {
    return (
        <svg className="h-6 w-6 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#00D7FE" d="M1.337.924a1.486 1.486 0 0 0-.112.568v21.017c0 .217.045.419.124.6l11.155-11.087L1.337.924z" />
            <path fill="#00F076" d="M13.544 10.989l3.258-3.238L3.45.195a1.466 1.466 0 0 0-.946-.179l11.04 10.973z" />
            <path fill="#FFD400" d="M22.018 13.298l-3.919 2.218-3.515-3.493 3.543-3.521 3.891 2.202a1.49 1.49 0 0 1 0 2.594z" />
            <path fill="#FF3A44" d="M13.544 13.056l-11 10.933c.298.036.612-.016.906-.183l13.324-7.54-3.23-3.21z" />
        </svg>
    );
}
