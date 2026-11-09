import DeleteAccountForm from '@/Components/DeleteAccountForm';
import AppShell from '@/Layouts/AppShell';
import { useTrans } from '@/lib/i18n';
import { Head, usePage } from '@inertiajs/react';

export default function DeleteAccount({ subscription: sub }) {
    const t = useTrans();
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <AppShell eyebrow={t('Account')} title={t('Delete Account')} intro={t('Permanently remove your account and its data.')}>
            <Head title={t('Delete Account')} />

            <div className="mx-auto max-w-xl rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <DeleteAccountForm sub={sub} user={user} />
            </div>
        </AppShell>
    );
}
