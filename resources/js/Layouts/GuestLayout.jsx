import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-bg-light dark:bg-bg-dark">
            <div className="mb-8">
                <Link href="/" className="text-3xl font-bold text-accent">
                    Steno
                </Link>
            </div>
            <div className="w-full max-w-md bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-8 shadow-sm">
                {children}
            </div>
        </div>
    );
}
