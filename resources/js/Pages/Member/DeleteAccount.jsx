import AppShell from '@/Layouts/AppShell';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm';
import { Head } from '@inertiajs/react';

export default function DeleteAccount() {
    return (
        <AppShell eyebrow="Account" title="Delete Account" intro="Permanently remove your account and its data.">
            <Head title="Delete Account" />

            <div className="mx-auto max-w-xl rounded-2xl border border-black/5 bg-white p-5 sm:p-6">
                <DeleteUserForm />
            </div>
        </AppShell>
    );
}
