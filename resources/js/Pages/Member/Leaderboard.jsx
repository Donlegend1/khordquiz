import AppShell from '@/Layouts/AppShell';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { tk, useLocale, useTrans } from '@/lib/i18n';

const PODIUM_COLORS = [
    { bg: 'bg-rose-50', text: 'text-brand' },
    { bg: 'bg-emerald-50', text: 'text-emerald-700' },
    { bg: 'bg-violet-50', text: 'text-violet-700' },
];
const ROW_COLORS = ['bg-amber-50 text-amber-700', 'bg-sky-50 text-sky-700', 'bg-indigo-50 text-indigo-700', 'bg-rose-50 text-rose-700'];

const PERIOD_LABELS = {
    week: tk('This week'),
    all: tk('All time'),
};

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

// "Aisha Hassan" → "Aisha H." so other members' full surnames aren't shown.
function shortName(name) {
    const parts = name.split(' ').filter(Boolean);

    return parts.length > 1 ? `${parts[0]} ${parts[parts.length - 1][0].toUpperCase()}.` : name;
}

function PodiumSpot({ entry, order }) {
    const t = useTrans();
    const locale = useLocale();
    const first = entry.rank === 1;
    const color = PODIUM_COLORS[entry.rank - 1];
    const barHeight = first ? 'h-24' : entry.rank === 2 ? 'h-[72px]' : 'h-14';

    return (
        <div className="flex flex-1 flex-col items-center gap-2" style={{ order }}>
            <div className="relative">
                <span
                    className={`flex items-center justify-center rounded-full font-extrabold ${color.bg} ${color.text} ${first ? 'h-16 w-16 text-lg' : 'h-14 w-14 text-base'} ${
                        entry.is_you ? 'ring-2 ring-brand ring-offset-2' : ''
                    }`}
                >
                    {initials(entry.name)}
                </span>
                <span
                    className={`absolute -bottom-1.5 -right-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-white text-xs font-extrabold text-white ${
                        entry.rank === 1 ? 'bg-amber-400' : entry.rank === 2 ? 'bg-neutral-400' : 'bg-orange-400'
                    }`}
                >
                    {entry.rank}
                </span>
            </div>
            <p className="max-w-full truncate text-sm font-semibold text-neutral-900">{entry.is_you ? t('You') : shortName(entry.name)}</p>
            <div className={`flex w-full flex-col items-center justify-start rounded-t-xl pt-2.5 ${barHeight} ${first ? 'bg-neutral-900' : 'bg-neutral-100'}`}>
                <span className={`text-sm font-extrabold tabular-nums ${first ? 'text-white' : 'text-neutral-900'}`}>
                    {entry.points.toLocaleString(locale)}
                </span>
                <span className={`text-[11px] ${first ? 'text-white/70' : 'text-neutral-500'}`}>{t('pts')}</span>
            </div>
        </div>
    );
}

function RankRow({ row, index }) {
    const t = useTrans();
    const locale = useLocale();

    return (
        <li className={`flex items-center gap-3 px-3 py-3 ${row.is_you ? '-mx-2 rounded-xl bg-rose-50/60 px-5' : ''}`}>
            <span className="w-8 shrink-0 text-center text-sm font-bold tabular-nums text-neutral-400">{row.rank.toLocaleString(locale)}</span>
            <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold ${ROW_COLORS[index % ROW_COLORS.length]}`}>
                {initials(row.name)}
            </span>
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-semibold text-neutral-900">{row.is_you ? t('You') : shortName(row.name)}</p>
                <p className="truncate text-xs text-neutral-500">
                    {t(':accuracy% accuracy · :streak-day streak', { accuracy: row.accuracy, streak: row.streak })}
                </p>
            </div>
            <span className="shrink-0 text-sm font-extrabold tabular-nums text-neutral-900">{row.points.toLocaleString(locale)}</span>
        </li>
    );
}

export default function Leaderboard({ boards }) {
    const t = useTrans();
    const locale = useLocale();
    const [period, setPeriod] = useState('week');
    const board = boards[period];
    const { you } = board;
    const topPercent = Math.max(1, Math.ceil((you.rank / board.total) * 100));
    const ahead = board.around.find((row) => row.rank === you.rank - 1);
    const players = board.top.filter((row) => row.points > 0 || row.is_you);
    const podium = players.length >= 3 ? players.slice(0, 3) : [];
    const rows = players.slice(podium.length);
    const youInTop = players.some((row) => row.is_you);
    const aroundYou = youInTop ? [] : board.around.filter((row) => row.rank > players.length);

    return (
        <AppShell eyebrow={t('Member')} title={t('Leaderboard')} intro={t('See how your practice stacks up against other members.')}>
            <Head title={t('Leaderboard')} />

            <div className="mb-6 grid grid-cols-2 gap-2 sm:inline-flex sm:gap-0 sm:rounded-xl sm:bg-neutral-100 sm:p-1">
                {Object.entries(PERIOD_LABELS).map(([key, label]) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setPeriod(key)}
                        className={`rounded-xl px-4 py-2.5 text-sm font-semibold transition-colors duration-150 sm:rounded-lg sm:py-2 ${
                            period === key ? 'bg-brand text-white shadow-sm' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-900 sm:bg-transparent'
                        }`}
                    >
                        {t(label)}
                    </button>
                ))}
            </div>

            <div className="mb-6 flex items-center gap-4 rounded-2xl bg-neutral-900 p-4 text-white sm:gap-6 sm:p-6">
                <div className="flex shrink-0 flex-col items-start text-left">
                    <span className="text-[10px] font-semibold uppercase tracking-wide text-white/60 sm:text-[11px]">{t('Your rank')}</span>
                    <span className="text-2xl font-extrabold tabular-nums sm:text-3xl">#{you.rank.toLocaleString(locale)}</span>
                    <span className="text-[11px] text-white/60 sm:text-xs">{t('of :total', { total: board.total.toLocaleString(locale) })}</span>
                </div>
                <div className="h-12 w-px shrink-0 bg-white/15 sm:h-14" />
                <div className="flex min-w-0 flex-1 flex-col items-start gap-1 text-left">
                    <span className="text-sm font-extrabold tabular-nums sm:text-base">{t(':points pts', { points: you.points.toLocaleString(locale) })}</span>
                    <span className="truncate text-xs text-white/70 sm:text-sm">
                        {ahead
                            ? t(':points pts to pass :name', {
                                  points: (ahead.points - you.points + 1).toLocaleString(locale),
                                  name: shortName(ahead.name),
                              })
                            : you.points > 0
                              ? t("You're in the lead")
                              : t('Play a quiz to get on the board')}
                    </span>
                    <span className="mt-1 rounded-full bg-white/10 px-2.5 py-0.5 text-[10px] font-bold sm:text-[11px]">{t('Top :percent%', { percent: topPercent })}</span>
                </div>
            </div>

            <div className="mb-3 flex items-baseline justify-between">
                <h2 className="text-lg font-bold text-neutral-900">{t('Top players')}</h2>
                <span className="text-sm text-neutral-500">{t('Top :count', { count: board.top.length })}</span>
            </div>

            {podium.length === 3 && (
                <div className="mb-4 flex items-end gap-3">
                    <PodiumSpot entry={podium[1]} order={1} />
                    <PodiumSpot entry={podium[0]} order={2} />
                    <PodiumSpot entry={podium[2]} order={3} />
                </div>
            )}

            {rows.length > 0 && (
                <ul className="divide-y divide-black/5 rounded-2xl border border-black/5 bg-white px-2">
                    {rows.map((row, index) => (
                        <RankRow key={row.id} row={row} index={index} />
                    ))}
                </ul>
            )}

            {aroundYou.length > 0 && (
                <>
                    <p className="my-3 text-center text-sm font-bold tracking-widest text-neutral-300">···</p>
                    <ul className="divide-y divide-black/5 rounded-2xl border border-black/5 bg-white px-2">
                        {aroundYou.map((row, index) => (
                            <RankRow key={row.id} row={row} index={index} />
                        ))}
                    </ul>
                </>
            )}
        </AppShell>
    );
}
