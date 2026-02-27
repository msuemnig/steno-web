import { Head, Link, usePage } from '@inertiajs/react';

function PlanCard({ name, price, period, features, cta, highlighted }) {
    return (
        <div className={`rounded-xl p-8 border ${highlighted ? 'border-accent bg-accent/5' : 'border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark'}`}>
            <h3 className="text-xl font-bold text-text-light dark:text-text-dark mb-2">{name}</h3>
            <div className="mb-6">
                <span className="text-3xl font-bold text-text-light dark:text-text-dark">{price}</span>
                {period && <span className="text-muted text-sm">/{period}</span>}
            </div>
            <ul className="flex flex-col gap-3 mb-8">
                {features.map((f, i) => (
                    <li key={i} className="flex items-start gap-2 text-sm text-muted">
                        <svg className="w-5 h-5 text-ok shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                        {f}
                    </li>
                ))}
            </ul>
            <Link
                href={cta.href}
                className={`block text-center px-4 py-2.5 rounded-lg text-sm font-semibold ${
                    highlighted
                        ? 'bg-accent hover:bg-accent-hover text-gray-900'
                        : 'border border-border-light dark:border-border-dark text-text-light dark:text-text-dark hover:bg-bg-light dark:hover:bg-bg-dark'
                }`}
            >
                {cta.label}
            </Link>
        </div>
    );
}

export default function Pricing() {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-bg-light dark:bg-bg-dark">
            <Head title="Pricing" />

            <nav className="border-b border-border-light dark:border-border-dark">
                <div className="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
                    <Link href="/" className="text-2xl font-bold text-accent">Steno</Link>
                    <div className="flex items-center gap-4">
                        {auth.user ? (
                            <Link href="/dashboard" className="text-sm text-muted hover:text-text-light dark:hover:text-text-dark">Dashboard</Link>
                        ) : (
                            <Link href="/login" className="text-sm text-muted hover:text-text-light dark:hover:text-text-dark">Login</Link>
                        )}
                    </div>
                </div>
            </nav>

            <div className="max-w-5xl mx-auto px-4 py-20">
                <h1 className="text-4xl font-bold text-text-light dark:text-text-dark text-center mb-4">
                    Simple pricing
                </h1>
                <p className="text-muted text-center mb-16 max-w-lg mx-auto">
                    Steno works free forever locally. Upgrade to sync across devices and collaborate with your team.
                </p>

                <div className="grid md:grid-cols-3 gap-8">
                    <PlanCard
                        name="Free"
                        price="$0"
                        period=""
                        features={[
                            'Up to 2 scripts',
                            'Record & replay',
                            'Cross-page navigation',
                        ]}
                        cta={{ label: 'Install extension', href: '/register' }}
                    />
                    <PlanCard
                        name="Individual"
                        price="$50"
                        period="year"
                        highlighted
                        features={[
                            'Unlimited scripts',
                            'Cloud sync across devices',
                            'Import / export JSON',
                            'Web dashboard',
                        ]}
                        cta={{ label: 'Get started', href: '/register' }}
                    />
                    <PlanCard
                        name="Business"
                        price="$250"
                        period="year"
                        features={[
                            'Everything in Individual',
                            'Up to 10 team members',
                            'Role-based access control',
                            'Team script sharing',
                            'Admin dashboard',
                            'Priority support',
                        ]}
                        cta={{ label: 'Start trial', href: '/register' }}
                    />
                </div>
            </div>
        </div>
    );
}
