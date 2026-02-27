import { Head, Link, usePage } from '@inertiajs/react';

function Feature({ title, description }) {
    return (
        <div className="p-6 rounded-xl bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark">
            <h3 className="text-lg font-semibold text-text-light dark:text-text-dark mb-2">{title}</h3>
            <p className="text-sm text-muted leading-relaxed">{description}</p>
        </div>
    );
}

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-bg-light dark:bg-bg-dark">
            <Head title="Record & Replay Form Fills" />

            {/* Nav */}
            <nav className="border-b border-border-light dark:border-border-dark">
                <div className="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
                    <span className="text-2xl font-bold text-accent">Steno</span>
                    <div className="flex items-center gap-4">
                        <Link href="/pricing" className="text-sm text-muted hover:text-text-light dark:hover:text-text-dark">
                            Pricing
                        </Link>
                        {auth.user ? (
                            <Link href="/dashboard" className="px-4 py-2 rounded-lg bg-accent hover:bg-accent-hover text-gray-900 text-sm font-semibold">
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="text-sm text-muted hover:text-text-light dark:hover:text-text-dark">
                                    Login
                                </Link>
                                <Link href="/register" className="px-4 py-2 rounded-lg bg-accent hover:bg-accent-hover text-gray-900 text-sm font-semibold">
                                    Get Started
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </nav>

            {/* Hero */}
            <section className="max-w-4xl mx-auto px-4 py-24 text-center">
                <h1 className="text-5xl font-bold text-text-light dark:text-text-dark mb-6 leading-tight">
                    Record form fills once.<br />
                    <span className="text-accent">Replay them instantly.</span>
                </h1>
                <p className="text-lg text-muted max-w-2xl mx-auto mb-10">
                    Steno is a browser extension that captures your form interactions and replays them with a single click. Perfect for QA testing, demo environments, and repetitive data entry.
                </p>
                <div className="flex items-center justify-center gap-4">
                    <Link href="/register" className="px-6 py-3 rounded-lg bg-accent hover:bg-accent-hover text-gray-900 font-semibold">
                        Start for free
                    </Link>
                    <Link href="/pricing" className="px-6 py-3 rounded-lg border border-border-light dark:border-border-dark text-muted hover:text-text-light dark:hover:text-text-dark font-medium">
                        View pricing
                    </Link>
                </div>
            </section>

            {/* Features */}
            <section className="max-w-6xl mx-auto px-4 pb-24">
                <h2 className="text-2xl font-bold text-text-light dark:text-text-dark text-center mb-12">
                    How it works
                </h2>
                <div className="grid md:grid-cols-3 gap-6">
                    <Feature
                        title="1. Record"
                        description="Click record, fill out your form normally. Steno captures every field, checkbox, dropdown, and button click."
                    />
                    <Feature
                        title="2. Name & organize"
                        description="Save scripts organized by site and persona. Keep your QA scenarios tidy with the built-in tree view."
                    />
                    <Feature
                        title="3. Replay"
                        description="One click fills everything back in. Works across page navigations and even handles React forms."
                    />
                </div>
            </section>

            {/* Cloud sync section */}
            <section className="border-t border-border-light dark:border-border-dark py-24">
                <div className="max-w-4xl mx-auto px-4 text-center">
                    <h2 className="text-2xl font-bold text-text-light dark:text-text-dark mb-4">
                        Cloud sync & team sharing
                    </h2>
                    <p className="text-muted max-w-2xl mx-auto mb-8">
                        Upgrade to sync your scripts across devices and share them with your team. Role-based access control keeps your data safe.
                    </p>
                    <Link href="/pricing" className="px-6 py-3 rounded-lg bg-accent hover:bg-accent-hover text-gray-900 font-semibold">
                        See plans
                    </Link>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-border-light dark:border-border-dark py-8">
                <div className="max-w-6xl mx-auto px-4 text-center text-sm text-muted">
                    Steno
                </div>
            </footer>
        </div>
    );
}
