import { AVATAR_OPTIONS } from '@/lib/profile-options';

import icons from '@/data/avatarIcons.json';

export default function AvatarBadge({ id, name = '', size = 48 }) {
    const option = AVATAR_OPTIONS.find((item) => item.id === id) ?? (id === 'electric-guitar' ? { bg: '#E8F0FE', fg: '#1A56DB' } : null);
    const initials = name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0].toUpperCase()).join('');
    return (
        <span aria-hidden="true" className="inline-flex shrink-0 items-center justify-center rounded-full font-bold" style={{ width: size, height: size, backgroundColor: option?.bg ?? '#FDEDEE', color: option?.fg ?? '#C81E1E', fontSize: size * 0.32 }}>
            {option ? <span className="inline-flex" style={{ width: size * 0.66, height: size * 0.66 }} dangerouslySetInnerHTML={{ __html: icons[id] }} /> : initials}
        </span>
    );
}
