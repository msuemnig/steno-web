import { useForm, Head, Link } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';
import Button from '../../Components/Button';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    function submit(e) {
        e.preventDefault();
        post('/email/verification-notification');
    }

    return (
        <GuestLayout>
            <Head title="Verify Email" />
            <h2 className="text-xl font-semibold text-text-light dark:text-text-dark mb-2">Verify your email</h2>
            <p className="text-sm text-muted mb-6">
                Check your inbox for a verification link. If you didn't receive it, we can send another.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm text-ok">A new verification link has been sent.</div>
            )}

            <form onSubmit={submit}>
                <Button type="submit" disabled={processing} className="w-full">
                    {processing ? 'Sending...' : 'Resend verification email'}
                </Button>
            </form>

            <Link
                href="/logout"
                method="post"
                as="button"
                className="mt-4 text-sm text-muted hover:text-accent block text-center w-full"
            >
                Log out
            </Link>
        </GuestLayout>
    );
}
