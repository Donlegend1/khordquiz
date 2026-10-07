// KhordQuiz KQ mark (replaces Laravel's default logo in the account layouts).
export default function ApplicationLogo({ className = '', ...props }) {
    return <img src="/images/logo-mark.png" alt="KhordQuiz" {...props} className={`object-contain ${className}`} />;
}
