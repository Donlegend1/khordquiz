const variants = {
    light: 'border-brand/30 bg-white text-brand shadow-[0_2px_8px_rgba(193,39,31,0.12)]',
    dark: 'border-white/10 bg-white/5 text-gray-300 shadow-[0_2px_12px_rgba(193,39,31,0.35)]',
};

export default function Eyebrow({ children, variant = 'light' }) {
    return (
        <span className={`inline-block rounded-full border px-3 py-1 text-xs sm:text-sm ${variants[variant]}`}>
            {children}
        </span>
    );
}
