import DeleteAccountForm from '@/Components/DeleteAccountForm';
import AppShell from '@/Layouts/AppShell';
import { useLocale, useTrans } from '@/lib/i18n';
import { sampleSubscription } from '@/lib/subscription';
import { Head, usePage } from '@inertiajs/react';

export default function DeleteAccount() {
    const t = useTrans();
    const { auth } = usePage().props;
    const user = auth.user;
    const sub = sampleSubscription(useLocale());

    return (
        <AppShell eyebrow={t('Account')} title={t('Delete Account')} intro={t('Permanently remove your account and its data.')}>
            <Head title={t('Delete Account')} />

            <div className="mx-auto max-w-xl rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <DeleteAccountForm sub={sub} user={user} />
            </div>
        </AppShell>
    );
}
