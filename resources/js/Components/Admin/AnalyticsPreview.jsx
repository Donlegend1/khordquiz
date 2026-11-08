import { useState } from 'react';

export const demoMembers = [
    { name: 'Amara Okafor', email: 'amara@example.com', country: 'Nigeria', language: 'English', level: 'Beginner', inactive: 16, subscribed: true, joined: '2026-10-12' },
    { name: 'Jonas Weber', email: 'jonas@example.com', country: 'Germany', language: 'German', level: 'Intermediate', inactive: 2, subscribed: true, joined: '2026-10-21' },
    { name: 'Sofia Martins', email: 'sofia@example.com', country: 'Brazil', language: 'Portuguese', level: 'Advanced', inactive: 21, subscribed: false, joined: '2026-10-06' },
    { name: 'Emma Wilson', email: 'emma@example.com', country: 'United Kingdom', language: 'English', level: 'Beginner', inactive: 1, subscribed: true, joined: '2026-11-02' },
    { name: 'Noah de Vries', email: 'noah@example.com', country: 'Netherlands', language: 'Dutch', level: 'Intermediate', inactive: 15, subscribed: false, joined: '2026-10-19' },
];
export function Panel({ title, detail, children, action }) {
    return <section className="min-w-0 rounded-2xl border border-neutral-200 bg-white p-5 sm:p-6"><div className="mb-5 flex flex-wrap items-start justify-between gap-3"><div><h2 className="font-semibold text-neutral-900">{title}</h2>{detail && <p className="mt-1 text-sm leading-relaxed text-neutral-500">{detail}</p>}</div>{action}</div>{children}</section>;
}
export function Metric({ label, value, detail, tone = 'neutral' }) {
    const tones = { neutral: 'bg-white', blue: 'bg-sky-50', green: 'bg-emerald-50', amber: 'bg-amber-50' };
    return <div className={`rounded-2xl border border-black/5 p-5 ${tones[tone]}`}><p className="text-sm text-neutral-600">{label}</p><p className="mt-3 text-3xl font-bold tracking-tight text-neutral-900">{value}</p><p className="mt-2 text-xs leading-relaxed text-neutral-500">{detail}</p></div>;
}
export function Bars({ rows, unit = '' }) {
    const max = Math.max(1, ...rows.map(row => row.value));
    return <ul className="space-y-4">{rows.map(row => <li key={row.label}><div className="mb-1.5 flex justify-between gap-4 text-sm"><span className="text-neutral-700">{row.label}</span><span className="font-semibold tabular-nums">{row.value.toLocaleString()}{unit}</span></div><div className="h-2 overflow-hidden rounded-full bg-neutral-100"><div className="h-full rounded-full bg-sky-600" style={{ width: `${row.value / max * 100}%` }} /></div></li>)}</ul>;
}
export function PreviewTable({ headings, rows }) {
    return <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr>{headings.map(heading => <th key={heading} className="whitespace-nowrap border-b border-neutral-200 pb-3 pr-5 text-xs font-semibold text-neutral-500">{heading}</th>)}</tr></thead><tbody>{rows.map((row, index) => <tr key={index}>{row.map((cell, column) => <td key={column} className="border-b border-neutral-100 py-4 pr-5 align-top last:pr-0">{cell}</td>)}</tr>)}</tbody></table></div>;
}
export function SignupChart({ range }) {
    const values = range === '7' ? [8, 11, 6, 15, 13, 18, 13] : range === '90' ? [60, 75, 66, 98, 104, 90, 120, 110, 130, 140, 135, 160] : [14, 20, 17, 24, 19, 28, 25, 33, 29, 37, 40, 34];
    const max = Math.max(...values);
    return <><div role="img" aria-label={`Example signup trend: ${values.join(', ')} signups per interval`} className="flex h-40 items-end gap-2 sm:gap-3">{values.map((value, index) => <div key={index} title={`${value} sample signups`} className="flex h-full min-w-0 flex-1 items-end"><div className="w-full rounded-t-md bg-sky-500" style={{ height: `${value / max * 100}%` }} /></div>)}</div><div className="mt-3 flex justify-between text-xs text-neutral-500"><span>Start of period</span><span>End of period</span></div><details className="mt-4 text-xs text-neutral-500"><summary className="min-h-6 cursor-pointer">View chart values</summary><p className="mt-2">{values.join(', ')}</p></details></>;
}
export function AudiencePreview() {
    const [filters, setFilters] = useState({ country: '', language: '', level: '', inactive: '', subscribed: '', joined: '' });
    const options = { country: ['Nigeria', 'Germany', 'Brazil', 'United Kingdom', 'Netherlands'], language: ['English', 'German', 'Portuguese', 'Dutch'], level: ['Beginner', 'Intermediate', 'Advanced'], inactive: ['7', '14', '30'], subscribed: ['Subscribed', 'Not subscribed'] };
    const labels = { country: 'Country', language: 'Language', level: 'Skill level', inactive: 'Inactive for at least', subscribed: 'Marketing emails' };
    const filtered = demoMembers.filter(member => (!filters.country || member.country === filters.country) && (!filters.language || member.language === filters.language) && (!filters.level || member.level === filters.level) && (!filters.inactive || member.inactive >= Number(filters.inactive)) && (!filters.subscribed || member.subscribed === (filters.subscribed === 'Subscribed')) && (!filters.joined || member.joined >= filters.joined));
    return <Panel title="Audience" detail="Explore sample segments for future campaigns. These are fictional contacts, not your member list.">
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">{Object.entries(options).map(([key, choices]) => <label key={key} className="text-xs font-semibold text-neutral-600">{labels[key]}<select value={filters[key]} onChange={event => setFilters({ ...filters, [key]: event.target.value })} className="mt-2 min-h-11 w-full rounded-xl border-neutral-300 text-sm"><option value="">All</option>{choices.map(choice => <option key={choice} value={choice}>{key === 'inactive' ? `${choice} days` : choice}</option>)}</select></label>)}<label className="text-xs font-semibold text-neutral-600">Joined on or after<input type="date" value={filters.joined} onChange={event => setFilters({ ...filters, joined: event.target.value })} className="mt-2 min-h-11 w-full rounded-xl border-neutral-300 text-sm" /></label></div>
        <div className="my-5 flex flex-wrap items-center justify-between gap-3"><p className="text-sm text-neutral-600">{filtered.length} sample contacts match</p><button onClick={() => setFilters({ country: '', language: '', level: '', inactive: '', subscribed: '', joined: '' })} className="min-h-11 px-3 text-sm font-semibold text-sky-700">Clear filters</button></div>
        {filtered.length ? <PreviewTable headings={['Member', 'Country / language', 'Level', 'Last practice', 'Email status']} rows={filtered.map(member => [<div key="member"><p className="font-semibold">{member.name}</p><p className="mt-1 text-xs text-neutral-500">{member.email}</p></div>, `${member.country} · ${member.language}`, member.level, `${member.inactive} days ago`, <span key="status" className={`rounded-full px-2 py-1 text-xs ${member.subscribed ? 'bg-emerald-50 text-emerald-800' : 'bg-neutral-100 text-neutral-600'}`}>{member.subscribed ? 'Subscribed' : 'Not subscribed'}</span>])} /> : <p className="rounded-xl bg-neutral-50 p-6 text-center text-sm text-neutral-500">No sample contacts match. Try clearing a filter.</p>}
        <div className="mt-5 flex flex-wrap items-center gap-3"><button disabled className="min-h-11 rounded-xl border border-neutral-200 px-4 text-sm font-semibold text-neutral-400">Export audience</button><button disabled className="min-h-11 rounded-xl bg-neutral-100 px-4 text-sm font-semibold text-neutral-400">Create email campaign</button><p className="text-xs text-neutral-500">Design only · exports and sending are not connected.</p></div>
    </Panel>;
}
