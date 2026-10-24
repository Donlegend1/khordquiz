import AppShell from '@/Layouts/AppShell';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

// Sample leaderboard, same as the mobile app's own Rank tab (see
// src/lib/demo-leaderboard.ts there): "Sample leaderboard used to preview the
// Rank page until rankings come from the server." The web schema has no points
// or ranking system yet, so this mirrors that same honest placeholder here.
const PODIUM_COLORS = [
    { bg: 'bg-rose-50', text: 'text-brand' },
    { bg: 'bg-emerald-50', text: 'text-emerald-700' },
    { bg: 'bg-violet-50', text: 'text-violet-700' },
];
const ROW_COLORS = ['bg-amber-50 text-amber-700', 'bg-sky-50 text-sky-700', 'bg-indigo-50 text-indigo-700', 'bg-rose-50 text-rose-700'];

const PERIODS = {
    week: {
        label: 'This week',
        total: 1248,
        you: { rank: 485, points: 296, accuracy: 74, streak: 6 },
        ahead: { name: 'Aisha H.', pointsToPass: 4 },
        podium: [
            { place: 1, name: 'Isaac A.', points: 2580 },
            { place: 2, name: 'Imani B.', points: 2024 },
            { place: 3, name: 'Jonah C.', points: 1756 },
        ],
        rows: [
            { rank: 4, name: 'Aisha D.', points: 1588, accuracy: 93, streak: 36 },
            { rank: 5, name: 'David E.', points: 1469, accuracy: 96, streak: 35 },
            { rank: 6, name: 'Mia F.', points: 1378, accuracy: 93, streak: 34 },
            { rank: 7, name: 'Noah G.', points: 1306, accuracy: 89, streak: 31 },
            { rank: 8, name: 'Zara H.', points: 1244, accuracy: 91, streak: 29 },
        ],
    },
    all: {
        label: 'All time',
        total: 1248,
        you: { rank: 512, points: 4120, accuracy: 74, streak: 6 },
        ahead: { name: 'Esther R.', pointsToPass: 18 },
        podium: [
            { place: 1, name: 'Daniel K.', points: 18450 },
            { place: 2, name: 'Sofia M.', points: 15210 },
            { place: 3, name: 'Tunde O.', points: 13870 },
        ],
        rows: [
            { rank: 4, name: 'Grace P.', points: 12990, accuracy: 94, streak: 58 },
            { rank: 5, name: 'Marcus Q.', points: 11840, accuracy: 92, streak: 52 },
            { rank: 6, name: 'Chioma S.', points: 10765, accuracy: 90, streak: 47 },
            { rank: 7, name: 'Liam T.', points: 9920, accuracy: 88, streak: 44 },
            { rank: 8, name: 'Zainab U.', points: 9315, accuracy: 91, streak: 40 },
        ],
    },
};

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function TrophyIcon(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M8 4h8v5a4 4 0 01-8 0V4z" />
            <path d="M8 5H5a3 3 0 003 3M16 5h3a3 3 0 01-3 3" />
            <path d="M12 13v3M9 20h6M10 20v-2.5h4V20" />
        </svg>
    );
}

function PodiumSpot({ entry, order }) {
    const first = entry.place === 1;
    const color = PODIUM_COLORS[entry.place - 1];
    const barHeight = first ? 'h-24' : entry.place === 2 ? 'h-[72px]' : 'h-14';

    return (
        <div className="flex flex-1 flex-col items-center gap-2" style={{ order }}>
            <div className="relative">
                <span className={`flex items-center justify-center rounded-full font-extrabold ${color.bg} ${color.text} ${first ? 'h-16 w-16 text-lg' : 'h-14 w-14 text-base'}`}>
                    {initials(entry.name)}
                </span>
                <span
                    className={`absolute -bottom-1.5 -right-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-white text-xs font-extrabold text-white ${
                        entry.place === 1 ? 'bg-amber-400' : entry.place === 2 ? 'bg-neutral-400' : 'bg-orange-400'
                    }`}
                >
                    {entry.place}
                </span>
            </div>
            <p className="max-w-full truncate text-sm font-semibold text-neutral-900">{entry.name}</p>
            <div className={`flex w-full flex-col items-center justify-start rounded-t-xl pt-2.5 ${barHeight} ${first ? 'bg-neutral-900' : 'bg-neutral-100'}`}>
                <span className={`text-sm font-extrabold tabular-nums ${first ? 'text-white' : 'text-neutral-900'}`}>
                    {entry.points.toLocaleString('en-US')}
                </span>
                <span className={`text-[11px] ${first ? 'text-white/70' : 'text-neutral-500'}`}>pts</span>
            </div>
        </div>
    );
}

export default function Leaderboard() {
    const [period, setPeriod] = useState('week');
    const board = PERIODS[period];
    const topPercent = Math.max(1, Math.ceil((board.you.rank / board.total) * 100));

    return (
        <AppShell eyebrow="Member" title="Leaderboard" intro="See how your practice stacks up against other members.">
            <Head title="Leaderboard" />

            <div className="mb-6 grid grid-cols-2 gap-2 sm:inline-flex sm:gap-0 sm:rounded-xl sm:bg-neutral-100 sm:p-1">
                {Object.entries(PERIODS).map(([key, value]) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setPeriod(key)}
                        className={`rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors duration-150 sm:rounded-lg sm:py-2 ${
                            period === key ? 'bg-brand text-white shadow-sm' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-900 sm:bg-transparent'
                        }`}
                    >
                        {value.label}
                    </button>
                ))}
            </div>

            <div className="mb-6 flex items-center gap-4 rounded-2xl bg-neutral-900 p-4 text-white sm:gap-6 sm:p-6">
                <div className="flex shrink-0 flex-col items-start text-left">
                    <span className="text-[10px] font-semibold uppercase tracking-wide text-white/60 sm:text-[11px]">Your rank</span>
                    <span className="text-2xl font-extrabold tabular-nums sm:text-3xl">#{board.you.rank.toLocaleString('en-US')}</span>
                    <span className="text-[11px] text-white/60 sm:text-xs">of {board.total.toLocaleString('en-US')}</span>
                </div>
                <div className="h-12 w-px shrink-0 bg-white/15 sm:h-14" />
                <div className="flex min-w-0 flex-1 flex-col items-start gap-1 text-left">
                    <span className="text-sm font-extrabold tabular-nums sm:text-base">{board.you.points.toLocaleString('en-US')} pts</span>
                    <span className="truncate text-xs text-white/70 sm:text-sm">
                        {board.ahead.pointsToPass.toLocaleString('en-US')} pts to pass {board.ahead.name}
                    </span>
                    <span className="mt-1 rounded-full bg-white/10 px-2.5 py-0.5 text-[10px] font-bold sm:text-[11px]">Top {topPercent}%</span>
                </div>
            </div>

            <div className="mb-3 flex items-baseline justify-between">
                <h2 className="text-lg font-bold text-neutral-900">Top players</h2>
                <span className="text-sm text-neutral-500">Top 50</span>
            </div>

            <div className="mb-4 flex items-end gap-3">
                <PodiumSpot entry={board.podium[1]} order={1} />
                <PodiumSpot entry={board.podium[0]} order={2} />
                <PodiumSpot entry={board.podium[2]} order={3} />
            </div>

            <ul className="divide-y divide-black/5 rounded-2xl border border-black/5 bg-white px-2">
                {board.rows.map((row, index) => (
                    <li key={row.rank} className="flex items-center gap-3 px-3 py-3">
                        <span className="w-6 shrink-0 text-center text-sm font-bold tabular-nums text-neutral-400">{row.rank}</span>
                        <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold ${ROW_COLORS[index % ROW_COLORS.length]}`}>
                            {initials(row.name)}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-semibold text-neutral-900">{row.name}</p>
                            <p className="truncate text-xs text-neutral-500">
                                {row.accuracy}% accuracy &middot; {row.streak}-day streak
                            </p>
                        </div>
                        <span className="shrink-0 text-sm font-extrabold tabular-nums text-neutral-900">{row.points.toLocaleString('en-US')}</span>
                    </li>
                ))}
            </ul>
        </AppShell>
    );
}
