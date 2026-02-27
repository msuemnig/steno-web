import { Head, useForm, usePage, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import InputField from '../../Components/InputField';
import Button from '../../Components/Button';

function Section({ title, children }) {
    return (
        <div className="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 mb-6">
            <h2 className="text-lg font-semibold text-text-light dark:text-text-dark mb-4">{title}</h2>
            {children}
        </div>
    );
}

function ProfileSection({ user }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
    });

    return (
        <Section title="Profile">
            <form onSubmit={e => { e.preventDefault(); put('/user/profile-information'); }} className="flex flex-col gap-4 max-w-md">
                <InputField label="Name" id="name" value={data.name} onChange={e => setData('name', e.target.value)} error={errors.name} />
                <InputField label="Email" id="email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} error={errors.email} />
                <Button type="submit" disabled={processing}>
                    {processing ? 'Saving...' : 'Save'}
                </Button>
            </form>
        </Section>
    );
}

function PasswordSection() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <Section title="Password">
            <form onSubmit={e => { e.preventDefault(); put('/user/password', { onSuccess: () => reset() }); }} className="flex flex-col gap-4 max-w-md">
                <InputField label="Current Password" id="current_password" type="password" value={data.current_password} onChange={e => setData('current_password', e.target.value)} error={errors.current_password} />
                <InputField label="New Password" id="password" type="password" value={data.password} onChange={e => setData('password', e.target.value)} error={errors.password} />
                <InputField label="Confirm Password" id="password_confirmation" type="password" value={data.password_confirmation} onChange={e => setData('password_confirmation', e.target.value)} />
                <Button type="submit" disabled={processing}>
                    {processing ? 'Updating...' : 'Update password'}
                </Button>
            </form>
        </Section>
    );
}

function MFASection({ enabled }) {
    return (
        <Section title="Two-Factor Authentication">
            <p className="text-sm text-muted mb-4">
                {enabled
                    ? 'Two-factor authentication is enabled. Your account is more secure.'
                    : 'Add an extra layer of security to your account by enabling two-factor authentication.'}
            </p>
            {enabled ? (
                <form method="POST" action="/user/two-factor-authentication" onSubmit={e => { e.preventDefault(); router.delete('/user/two-factor-authentication'); }}>
                    <Button variant="danger">Disable 2FA</Button>
                </form>
            ) : (
                <form method="POST" action="/user/two-factor-authentication" onSubmit={e => { e.preventDefault(); router.post('/user/two-factor-authentication'); }}>
                    <Button>Enable 2FA</Button>
                </form>
            )}
        </Section>
    );
}

function TokensSection({ tokens }) {
    return (
        <Section title="API Tokens">
            {tokens.length === 0 ? (
                <p className="text-sm text-muted">No API tokens. Tokens are created when you connect the extension.</p>
            ) : (
                <div className="flex flex-col gap-2">
                    {tokens.map(token => (
                        <div key={token.id} className="flex items-center justify-between py-2 px-3 rounded-lg bg-bg-light dark:bg-bg-dark">
                            <div>
                                <div className="text-sm font-medium text-text-light dark:text-text-dark">{token.name}</div>
                                <div className="text-xs text-muted">
                                    Created {new Date(token.created_at).toLocaleDateString()}
                                    {token.last_used_at && ` · Last used ${new Date(token.last_used_at).toLocaleDateString()}`}
                                </div>
                            </div>
                            <button
                                onClick={() => router.delete(`/user/api-tokens/${token.id}`)}
                                className="text-xs text-danger hover:text-red-400"
                            >
                                Revoke
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </Section>
    );
}

export default function Show() {
    const { user, twoFactorEnabled, tokens } = usePage().props;

    return (
        <AppLayout>
            <Head title="Settings" />
            <h1 className="text-2xl font-bold text-text-light dark:text-text-dark mb-8">Settings</h1>
            <ProfileSection user={user} />
            <PasswordSection />
            <MFASection enabled={twoFactorEnabled} />
            <TokensSection tokens={tokens} />
        </AppLayout>
    );
}
