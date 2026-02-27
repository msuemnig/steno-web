import { useForm, Head, Link } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';
import InputField from '../../Components/InputField';
import Button from '../../Components/Button';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    function submit(e) {
        e.preventDefault();
        post('/forgot-password');
    }

    return (
        <GuestLayout>
            <Head title="Forgot Password" />
            <h2 className="text-xl font-semibold text-text-light dark:text-text-dark mb-2">Reset password</h2>
            <p className="text-sm text-muted mb-6">Enter your email and we'll send you a reset link.</p>

            {status && <div className="mb-4 text-sm text-ok">{status}</div>}

            <form onSubmit={submit} className="flex flex-col gap-4">
                <InputField label="Email" id="email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} error={errors.email} required />
                <Button type="submit" disabled={processing}>
                    {processing ? 'Sending...' : 'Send reset link'}
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-muted">
                <Link href="/login" className="text-accent hover:text-accent-hover">Back to login</Link>
            </p>
        </GuestLayout>
    );
}
