import AppShell from '@/Layouts/AppShell';
import { useTrans } from '@/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

const PADDLE_JS = 'https://cdn.paddle.com/paddle/v2/paddle.js';

// Loads Paddle.js once per page load (the script registers window.Paddle).
function loadPaddle() {
    if (window.Paddle) {
        return Promise.resolve(window.Paddle);
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = PADDLE_JS;
        script.async = true;
        script.onload = () => resolve(window.Paddle);
        script.onerror = () => reject(new Error('Paddle.js failed to load'));
        document.head.appendChild(script);
    });
}

// BillingController::startPaddleCheckout creates the Paddle transaction (plan,
// trial, customer) on the server; this page only opens Paddle's secure overlay
// for it. Paying → billing.success (which activates the plan straight away);
// closing the overlay → back to the Subscription page.
export default function PaddleCheckout({ clientToken, sandbox, transactionId, successUrl, cancelUrl }) {
    const t = useTrans();
    const [state, setState] = useState('loading');
    const completed = useRef(false);
    const active = useRef(true);

    const handleEvent = useCallback(
        (event) => {
            if (!active.current) {
                return;
            }

            if (event.name === 'checkout.loaded') {
                setState('open');
            } else if (event.name === 'checkout.completed') {
                completed.current = true;
                setState('done');
                router.visit(successUrl);
            } else if (event.name === 'checkout.closed' && !completed.current) {
                router.visit(cancelUrl);
            }
        },
        [successUrl, cancelUrl],
    );

    const openCheckout = useCallback(() => {
        setState('loading');

        loadPaddle()
            .then((Paddle) => {
                // Paddle.js can only be initialised once per page load; later
                // visits (Inertia keeps the page alive) just swap the callback.
                if (Paddle.Initialized) {
                    Paddle.Update({ eventCallback: handleEvent });
                } else {
                    if (sandbox) {
                        Paddle.Environment.set('sandbox');
                    }
                    Paddle.Initialize({ token: clientToken, eventCallback: handleEvent });
                }

                Paddle.Checkout.open({
                    transactionId,
                    settings: { displayMode: 'overlay', theme: 'light', allowLogout: false },
                });
            })
            .catch(() => setState('error'));
    }, [clientToken, sandbox, transactionId, handleEvent]);

    useEffect(() => {
        active.current = true;
        openCheckout();

        return () => {
            active.current = false;
            window.Paddle?.Checkout?.close?.();
        };
    }, [openCheckout]);

    return (
        <AppShell eyebrow={t('Account')} title={t('Secure checkout')} intro={t('Your secure payment window will open here.')}>
            <Head title={t('Secure checkout')} />

            <div className="mx-auto max-w-md rounded-2xl border border-neutral-200 bg-white p-6 text-center">
                {state === 'error' ? (
                    <>
                        <p className="text-sm font-medium text-brand">{t('We couldn\'t open the payment window. Check your connection and try again.')}</p>
                        <button type="button" onClick={openCheckout} className="mt-4 rounded-xl bg-neutral-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-neutral-800">
                            {t('Try again')}
                        </button>
                    </>
                ) : (
                    <>
                        <div className="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-900" />
                        <p className="mt-4 text-sm text-neutral-600">
                            {state === 'done' ? t('Payment received — activating your plan…') : t('Opening the secure payment window…')}
                        </p>
                        {state === 'open' && (
                            <button type="button" onClick={openCheckout} className="mt-4 text-xs font-bold text-brand hover:underline">
                                {t('Payment window not showing? Open it again')}
                            </button>
                        )}
                    </>
                )}

                <Link href={cancelUrl} className="mt-6 block text-xs font-semibold text-neutral-500 hover:text-neutral-800">
                    {t('Back to plans')}
                </Link>
            </div>
        </AppShell>
    );
}
