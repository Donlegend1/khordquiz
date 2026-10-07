// KQ logo mark, used in the site header.
export default function BrandLogo({ className = '' }) {
    return (
        <img
            src="/images/logo-mark.png"
            alt="KhordQuiz"
            width="287"
            height="192"
            className={`block h-10 w-auto ${className}`}
        />
    );
}
