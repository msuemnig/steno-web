import { Link, usePage, router } from '@inertiajs/react';
import { useState } from 'react';

function NavLink({ href, active, children }) {
    return (
        <Link
            href={href}
            className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                active
                    ? 'bg-accent/10 text-accent'
                    : 'text-muted hover:text-text-light dark:hover:text-text-dark'
            }`}
        >
            {children}
        </Link>
    );
}

export default function AppLayout({ children }) {
    const { auth, flash } = usePage().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <div className="min-h-screen bg-bg-light dark:bg-bg-dark">
            <nav className="border-b border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        <div className="flex items-center gap-6">
                            <Link href="/dashboard" className="text-xl font-bold text-accent">
                                Steno
                            </Link>
                            <div className="hidden md:flex items-center gap-1">
                                <NavLink href="/dashboard" active={currentPath === '/dashboard'}>
                                    Dashboard
                                </NavLink>
                                <NavLink href="/teams" active={currentPath.startsWith('/teams')}>
                                    Teams
                                </NavLink>
                                <NavLink href="/billing" active={currentPath === '/billing'}>
                                    Billing
                                </NavLink>
                                <NavLink href="/settings" active={currentPath === '/settings'}>
                                    Settings
                                </NavLink>
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <span className="text-sm text-muted hidden sm:block">
                                {auth.user?.name}
                            </span>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="text-sm text-muted hover:text-danger transition-colors"
                            >
                                Logout
                            </Link>
                            <button
                                className="md:hidden text-muted"
                                onClick={() => setMobileOpen(!mobileOpen)}
                            >
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                {mobileOpen && (
                    <div className="md:hidden border-t border-border-light dark:border-border-dark px-4 py-2 flex flex-col gap-1">
                        <NavLink href="/dashboard" active={currentPath === '/dashboard'}>Dashboard</NavLink>
                        <NavLink href="/teams" active={currentPath.startsWith('/teams')}>Teams</NavLink>
                        <NavLink href="/billing" active={currentPath === '/billing'}>Billing</NavLink>
                        <NavLink href="/settings" active={currentPath === '/settings'}>Settings</NavLink>
                    </div>
                )}
            </nav>

            {(flash?.success || flash?.error) && (
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    {flash.success && (
                        <div className="bg-ok/10 border border-ok/30 text-ok rounded-lg px-4 py-3 text-sm">
                            {flash.success}
                        </div>
                    )}
                    {flash.error && (
                        <div className="bg-danger/10 border border-danger/30 text-danger rounded-lg px-4 py-3 text-sm">
                            {flash.error}
                        </div>
                    )}
                </div>
            )}

            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {children}
            </main>
        </div>
    );
}
