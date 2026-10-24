import { COUNTRIES } from '@/lib/countries';
import { useEffect, useRef, useState } from 'react';

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
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const ref = useRef(null);
    const matches = COUNTRIES.filter((c) => c.toLowerCase().includes(query.trim().toLowerCase()));

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
                <span className={value ? 'text-neutral-900' : 'text-neutral-400'}>{value || 'Select your country'}</span>
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
                            placeholder="Search countries"
                            className="w-full text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none"
                        />
                    </div>
                    <div className="max-h-60 overflow-y-auto py-1">
                        {matches.map((country) => (
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
                                {country}
                                {country === value && <CheckIcon className="h-4 w-4 text-brand" />}
                            </button>
                        ))}
                        {matches.length === 0 && <p className="px-3.5 py-3 text-sm text-neutral-400">No matches.</p>}
                    </div>
                </div>
            )}
        </div>
    );
}
