import { useForm, Head } from '@inertiajs/react';
import { useState } from 'react';
import GuestLayout from '../../Layouts/GuestLayout';
import InputField from '../../Components/InputField';
import Button from '../../Components/Button';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        recovery_code: '',
    });

    function submit(e) {
        e.preventDefault();
        post('/two-factor-challenge');
    }

    return (
        <GuestLayout>
            <Head title="Two-Factor Authentication" />
            <h2 className="text-xl font-semibold text-text-light dark:text-text-dark mb-2">Two-factor authentication</h2>
            <p className="text-sm text-muted mb-6">
                {useRecovery
                    ? 'Enter one of your recovery codes.'
                    : 'Enter the code from your authenticator app.'}
            </p>

            <form onSubmit={submit} className="flex flex-col gap-4">
                {useRecovery ? (
                    <InputField
                        label="Recovery Code"
                        id="recovery_code"
                        value={data.recovery_code}
                        onChange={e => setData('recovery_code', e.target.value)}
                        error={errors.recovery_code}
                        autoComplete="one-time-code"
                        required
                    />
                ) : (
                    <InputField
                        label="Code"
                        id="code"
                        value={data.code}
                        onChange={e => setData('code', e.target.value)}
                        error={errors.code}
                        autoComplete="one-time-code"
                        inputMode="numeric"
                        required
                    />
                )}
                <Button type="submit" disabled={processing}>
                    {processing ? 'Verifying...' : 'Verify'}
                </Button>
            </form>

            <button
                onClick={() => setUseRecovery(!useRecovery)}
                className="mt-4 text-sm text-accent hover:text-accent-hover"
            >
                {useRecovery ? 'Use authenticator code' : 'Use recovery code'}
            </button>
        </GuestLayout>
    );
}
