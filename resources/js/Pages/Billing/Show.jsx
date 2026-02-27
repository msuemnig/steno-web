import { Head, useForm, usePage, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Button from '../../Components/Button';

function PlanOption({ name, price, current, planKey, onSelect }) {
    return (
        <div className={`rounded-xl p-6 border ${current ? 'border-accent bg-accent/5' : 'border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark'}`}>
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-text-light dark:text-text-dark">{name}</h3>
                {current && <span className="text-xs px-2 py-1 rounded-full bg-accent/20 text-accent">Current plan</span>}
            </div>
            <div className="text-2xl font-bold text-text-light dark:text-text-dark mb-4">
                {price}<span className="text-sm text-muted font-normal">/year</span>
            </div>
            {!current && (
                <Button onClick={() => onSelect(planKey)} className="w-full">
                    {current ? 'Current' : 'Select'}
                </Button>
            )}
        </div>
    );
}

export default function Show() {
    const { team, subscription, onGracePeriod, subscribed, plans, stripeKey } = usePage().props;

    function handleSubscribe(plan) {
        router.post('/billing/subscribe', {
            plan,
            payment_method: 'pm_card_visa', // placeholder — real implementation uses Stripe Elements
        });
    }

    function handleChangePlan(plan) {
        router.post('/billing/change-plan', { plan });
    }

    return (
        <AppLayout>
            <Head title="Billing" />

            <h1 className="text-2xl font-bold text-text-light dark:text-text-dark mb-2">Billing</h1>
            <p className="text-muted text-sm mb-8">
                Manage your subscription for <strong>{team?.name}</strong>
            </p>

            {/* Current status */}
            <div className="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 mb-8">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="text-sm font-medium text-text-light dark:text-text-dark">
                            {subscribed ? 'Active subscription' : 'Free plan'}
                        </div>
                        <div className="text-xs text-muted mt-1">
                            {subscribed
                                ? `${team?.plan_type} plan`
                                : 'Local storage only — upgrade to enable cloud sync'}
                        </div>
                        {onGracePeriod && (
                            <div className="text-xs text-warn mt-1">
                                Cancellation pending — access continues until end of billing period
                            </div>
                        )}
                    </div>
                    <div className="flex gap-2">
                        {subscribed && !onGracePeriod && (
                            <Button variant="ghost" onClick={() => router.post('/billing/cancel')}>
                                Cancel
                            </Button>
                        )}
                        {onGracePeriod && (
                            <Button onClick={() => router.post('/billing/resume')}>
                                Resume
                            </Button>
                        )}
                        {subscribed && (
                            <a
                                href="/billing/portal"
                                className="px-4 py-2 rounded-lg text-sm font-medium border border-border-light dark:border-border-dark text-text-light dark:text-text-dark hover:bg-bg-light dark:hover:bg-bg-dark transition-colors"
                            >
                                Manage in Stripe
                            </a>
                        )}
                    </div>
                </div>
            </div>

            {/* Plans */}
            <h2 className="text-lg font-semibold text-text-light dark:text-text-dark mb-4">
                {subscribed ? 'Change plan' : 'Choose a plan'}
            </h2>
            <div className="grid md:grid-cols-2 gap-6">
                <PlanOption
                    name="Individual"
                    price="$50"
                    planKey="individual"
                    current={team?.plan_type === 'individual'}
                    onSelect={subscribed ? handleChangePlan : handleSubscribe}
                />
                <PlanOption
                    name="Business"
                    price="$250"
                    planKey="business"
                    current={team?.plan_type === 'business'}
                    onSelect={subscribed ? handleChangePlan : handleSubscribe}
                />
            </div>
        </AppLayout>
    );
}
