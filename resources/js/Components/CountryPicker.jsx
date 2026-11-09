import { COUNTRIES } from '@/lib/countries';
import { useLocale, useTrans } from '@/lib/i18n';
import { useEffect, useMemo, useRef, useState } from 'react';

// COUNTRIES holds English names (that's the stored value), not ISO codes, so the
// ISO code each name stands for is worked out once by matching against Intl's own
// English region names — plus the handful whose spelling differs from Intl's.
// The code is then used only to DISPLAY the name in the member's language;
// onChange still receives the English name.
const ISO_ALIASES = {
    'Antigua and Barbuda': 'AG',
    'Bosnia and Herzegovina': 'BA',
    'Cabo Verde': 'CV',
    'Congo, Democratic Republic of the': 'CD',
    'Congo, Republic of the': 'CG',
    "Cote d'Ivoire": 'CI',
    'Holy See': 'VA',
    Myanmar: 'MM',
    'Palestine, State of': 'PS',
    'Saint Kitts and Nevis': 'KN',
    'Saint Lucia': 'LC',
    'Saint Vincent and the Grenadines': 'VC',
    'Sao Tome and Principe': 'ST',
    'Trinidad and Tobago': 'TT',
    Turkey: 'TR',
};

let isoCodes = null;
function countryCodes() {
    if (isoCodes) return isoCodes;
    isoCodes = { ...ISO_ALIASES };
    try {
        const english = new Intl.DisplayNames(['en'], { type: 'region' });
        const byName = {};
        for (let a = 65; a < 91; a++) {
            for (let b = 65; b < 91; b++) {
                const code = String.fromCharCode(a, b);
                try {
                    const name = english.of(code);
                    if (name && name !== code && !byName[name]) byName[name] = code;
                } catch {
                    // Not a valid region code.
                }
            }
        }
        for (const country of COUNTRIES) {
            if (!isoCodes[country] && byName[country]) isoCodes[country] = byName[country];
        }
    } catch {
        // No Intl.DisplayNames — names just stay in English.
    }
    return isoCodes;
}

function useCountryName() {
    const locale = useLocale();

    return useMemo(() => {
        if (locale.startsWith('en')) return (country) => country;
        let display = null;
        try {
            display = new Intl.DisplayNames([locale], { type: 'region' });
        } catch {
            return (country) => country;
        }
        const codes = countryCodes();
        return (country) => {
            const code = codes[country];
            if (!code) return country;
            try {
                return display.of(code) || country;
            } catch {
                return country;
            }
        };
    }, [locale]);
}

function ChevronDownIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 9l6 6 6-6" />
        </svg>
    );
}

function CheckIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M5 13l4 4L19 7" />
        </svg>
    );
}

function SearchIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="11" cy="11" r="7" />
            <path d="M21 21l-4.3-4.3" />
        </svg>
    );
}

// Searchable country picker — shared between EditProfile.jsx (where a member
// can change it later) and Signup.jsx (captured once at signup). `triggerClassName`
// lets each caller match its own field styling exactly (Signup's fields read an
// `error` to switch border color; EditProfile's don't), rather than this
// component guessing one shared look.
export default function CountryPicker({ value, onChange, triggerClassName }) {
    const t = useTrans();
    const locale = useLocale();
    const countryName = useCountryName();
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const ref = useRef(null);
    const options = useMemo(
        () => COUNTRIES.map((country) => ({ value: country, label: countryName(country) })).sort((a, b) => a.label.localeCompare(b.label, locale)),
        [countryName, locale],
    );
    const needle = query.trim().toLowerCase();
    // Matches the shown (translated) name or the English one.
    const matches = options.filter((c) => c.label.toLowerCase().includes(needle) || c.value.toLowerCase().includes(needle));

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (e) => {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onPointerDown);
        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    return (
        <div className="relative" ref={ref}>
            <button type="button" onClick={() => setOpen((v) => !v)} className={`${triggerClassName} flex items-center justify-between text-left`}>
                <span className={value ? 'text-neutral-900' : 'text-neutral-400'}>{value ? countryName(value) : t('Select your country')}</span>
                <ChevronDownIcon className="h-4 w-4 shrink-0 text-neutral-400" />
            </button>

            {open && (
                <div className="absolute left-0 right-0 top-full z-20 mt-1.5 overflow-hidden rounded-xl border border-black/5 bg-white shadow-lg">
                    <div className="flex items-center gap-2 border-b border-black/5 px-3.5 py-2.5">
                        <SearchIcon className="h-4 w-4 shrink-0 text-neutral-400" />
                        <input
                            autoFocus
                            type="text"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder={t('Search countries')}
                            className="w-full text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none"
                        />
                    </div>
                    <div className="max-h-60 overflow-y-auto py-1">
                        {matches.map(({ value: country, label }) => (
                            <button
                                key={country}
                                type="button"
                                onClick={() => {
                                    onChange(country);
                                    setOpen(false);
                                    setQuery('');
                                }}
                                className="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm text-neutral-800 transition-colors duration-150 hover:bg-neutral-50"
                            >
                                {label}
                                {country === value && <CheckIcon className="h-4 w-4 text-brand" />}
                            </button>
                        ))}
                        {matches.length === 0 && <p className="px-3.5 py-3 text-sm text-neutral-400">{t('No matches.')}</p>}
                    </div>
                </div>
            )}
        </div>
    );
}
