import DeleteAccountForm from '@/Components/DeleteAccountForm';
import AppShell from '@/Layouts/AppShell';
import { sampleSubscription } from '@/lib/subscription';
import { Head, usePage } from '@inertiajs/react';

export default function DeleteAccount() {
    const { auth } = usePage().props;
    const user = auth.user;
    const sub = sampleSubscription();

    return (
        <AppShell eyebrow="Account" title="Delete Account" intro="Permanently remove your account and its data.">
            <Head title="Delete Account" />

            <div className="mx-auto max-w-xl rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <DeleteAccountForm sub={sub} user={user} />
            </div>
        </AppShell>
    );
}
