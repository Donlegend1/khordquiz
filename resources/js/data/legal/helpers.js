// Mirrors src/lib/legal.ts in the KhordQuiz app.
export { SUPPORT_EMAIL as LEGAL_CONTACT_EMAIL } from '@/data/contact';

export const p = (text) => ({ text, type: 'paragraph' });
export const h = (text) => ({ text, type: 'subheading' });
export const list = (...items) => ({ items, type: 'list' });
