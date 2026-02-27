import { useForm, Head, Link } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';
import InputField from '../../Components/InputField';
import Button from '../../Components/Button';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function submit(e) {
        e.preventDefault();
        post('/register');
    }

    return (
        <GuestLayout>
            <Head title="Register" />
            <h2 className="text-xl font-semibold text-text-light dark:text-text-dark mb-6">Create your account</h2>

            <a
                href="/auth/google"
                className="flex items-center justify-center gap-2 w-full px-4 py-2 rounded-lg border border-border-light dark:border-border-dark text-text-light dark:text-text-dark hover:bg-bg-light dark:hover:bg-bg-dark transition-colors text-sm font-medium mb-6"
            >
                <svg className="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                Sign up with Google
            </a>

            <div className="flex items-center gap-3 mb-6">
                <div className="flex-1 h-px bg-border-light dark:bg-border-dark" />
                <span className="text-xs text-muted">or</span>
                <div className="flex-1 h-px bg-border-light dark:bg-border-dark" />
            </div>

            <form onSubmit={submit} className="flex flex-col gap-4">
                <InputField label="Name" id="name" value={data.name} onChange={e => setData('name', e.target.value)} error={errors.name} required />
                <InputField label="Email" id="email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} error={errors.email} required />
                <InputField label="Password" id="password" type="password" value={data.password} onChange={e => setData('password', e.target.value)} error={errors.password} required />
                <InputField label="Confirm Password" id="password_confirmation" type="password" value={data.password_confirmation} onChange={e => setData('password_confirmation', e.target.value)} error={errors.password_confirmation} required />
                <Button type="submit" disabled={processing}>
                    {processing ? 'Creating account...' : 'Create account'}
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-muted">
                Already have an account?{' '}
                <Link href="/login" className="text-accent hover:text-accent-hover">Sign in</Link>
            </p>
        </GuestLayout>
    );
}
