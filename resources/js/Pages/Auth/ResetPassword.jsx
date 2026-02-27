import { useForm, Head } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';
import InputField from '../../Components/InputField';
import Button from '../../Components/Button';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email: email || '',
        password: '',
        password_confirmation: '',
    });

    function submit(e) {
        e.preventDefault();
        post('/reset-password');
    }

    return (
        <GuestLayout>
            <Head title="Reset Password" />
            <h2 className="text-xl font-semibold text-text-light dark:text-text-dark mb-6">Set new password</h2>

            <form onSubmit={submit} className="flex flex-col gap-4">
                <InputField label="Email" id="email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} error={errors.email} required />
                <InputField label="New Password" id="password" type="password" value={data.password} onChange={e => setData('password', e.target.value)} error={errors.password} required />
                <InputField label="Confirm Password" id="password_confirmation" type="password" value={data.password_confirmation} onChange={e => setData('password_confirmation', e.target.value)} required />
                <Button type="submit" disabled={processing}>
                    {processing ? 'Resetting...' : 'Reset password'}
                </Button>
            </form>
        </GuestLayout>
    );
}
