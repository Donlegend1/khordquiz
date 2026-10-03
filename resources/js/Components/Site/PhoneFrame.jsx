// iPhone-style frame around a 589×1280 app screenshot. Size it via className (width).
export default function PhoneFrame({ src, alt, className = '', compact = false, loading = 'lazy' }) {
    return (
        <div className={`relative ${className}`}>
            {/* Side buttons */}
            <span className="absolute -left-[3px] top-[9%] h-[2.5%] w-[3px] rounded-l bg-gray-900" />
            <span className="absolute -left-[3px] top-[12.5%] h-[4.5%] w-[3px] rounded-l bg-gray-900" />
            <span className="absolute -right-[3px] top-[11%] h-[6%] w-[3px] rounded-r bg-gray-900" />

            <div
                className={
                    compact
                        ? 'rounded-[26px] bg-gray-900 p-[4px] shadow-[0_16px_40px_-12px_rgba(17,24,39,0.35)]'
                        : 'rounded-[44px] bg-gray-900 p-[7px] shadow-[0_24px_60px_-12px_rgba(17,24,39,0.35)]'
                }
            >
                <img
                    src={src}
                    alt={alt}
                    className={`block w-full ${compact ? 'rounded-[22px]' : 'rounded-[38px]'}`}
                    width="589"
                    height="1280"
                    loading={loading}
                />
            </div>
        </div>
    );
}
