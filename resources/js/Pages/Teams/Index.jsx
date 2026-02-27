import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import InputField from '../../Components/InputField';
import Button from '../../Components/Button';
import { useState } from 'react';

export default function Index() {
    const { teams } = usePage().props;
    const [showCreate, setShowCreate] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });

    function submit(e) {
        e.preventDefault();
        post('/teams', { onSuccess: () => { reset(); setShowCreate(false); } });
    }

    return (
        <AppLayout>
            <Head title="Teams" />
            <div className="flex items-center justify-between mb-8">
                <h1 className="text-2xl font-bold text-text-light dark:text-text-dark">Teams</h1>
                <Button onClick={() => setShowCreate(!showCreate)}>
                    {showCreate ? 'Cancel' : 'Create team'}
                </Button>
            </div>

            {showCreate && (
                <div className="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 mb-6">
                    <form onSubmit={submit} className="flex items-end gap-4">
                        <div className="flex-1">
                            <InputField label="Team Name" id="team-name" value={data.name} onChange={e => setData('name', e.target.value)} error={errors.name} />
                        </div>
                        <Button type="submit" disabled={processing}>Create</Button>
                    </form>
                </div>
            )}

            <div className="flex flex-col gap-3">
                {teams.map(team => (
                    <Link
                        key={team.id}
                        href={`/teams/${team.id}`}
                        className="flex items-center justify-between p-4 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl hover:border-accent/50 transition-colors"
                    >
                        <div>
                            <div className="text-sm font-semibold text-text-light dark:text-text-dark">{team.name}</div>
                            <div className="text-xs text-muted mt-0.5">{team.members_count} member{team.members_count !== 1 ? 's' : ''} · {team.pivot.role}</div>
                        </div>
                        <span className="text-xs px-2 py-1 rounded-full bg-accent/10 text-accent capitalize">
                            {team.plan_type}
                        </span>
                    </Link>
                ))}
            </div>
        </AppLayout>
    );
}
