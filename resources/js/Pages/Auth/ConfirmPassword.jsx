import { useForm, Head } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';
import InputField from '../../Components/InputField';
import Button from '../../Components/Button';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors } = useForm({ password: '' });

    function submit(e) {
        e.preventDefault();
        post('/user/confirm-password');
    }

    return (
        <GuestLayout>
            <Head title="Confirm Password" />
            <h2 className="text-xl font-semibold text-text-light dark:text-text-dark mb-2">Confirm password</h2>
            <p className="text-sm text-muted mb-6">Please confirm your password before continuing.</p>

            <form onSubmit={submit} className="flex flex-col gap-4">
                <InputField
                    label="Password"
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={e => setData('password', e.target.value)}
                    error={errors.password}
                    required
                />
                <Button type="submit" disabled={processing}>
                    {processing ? 'Confirming...' : 'Confirm'}
                </Button>
            </form>
        </GuestLayout>
    );
}
