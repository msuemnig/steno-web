import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function ExtensionLogin() {
    const { token, user } = usePage().props;
    const [sent, setSent] = useState(false);

    useEffect(() => {
        if (token && user) {
            window.postMessage({
                type: 'STENO_AUTH_TOKEN',
                token,
                user,
            }, '*');
            setSent(true);
        }
    }, [token, user]);

    return (
        <div className="min-h-screen flex items-center justify-center bg-bg-light dark:bg-bg-dark">
            <Head title="Connect Extension" />
            <div className="text-center max-w-md p-8">
                <div className="text-4xl font-bold text-accent mb-4">Steno</div>
                {sent ? (
                    <>
                        <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-ok/20 flex items-center justify-center">
                            <svg className="w-8 h-8 text-ok" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 className="text-xl font-semibold text-text-light dark:text-text-dark mb-2">
                            Connected!
                        </h2>
                        <p className="text-muted">
                            Signed in as <strong>{user.email}</strong>. You can close this tab.
                        </p>
                    </>
                ) : (
                    <>
                        <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/20 flex items-center justify-center animate-pulse">
                            <svg className="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h2 className="text-xl font-semibold text-text-light dark:text-text-dark mb-2">
                            Connecting...
                        </h2>
                        <p className="text-muted">Authenticating your extension.</p>
                    </>
                )}
            </div>
        </div>
    );
}
