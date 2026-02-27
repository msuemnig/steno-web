import { Head, useForm, usePage, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import InputField from '../../Components/InputField';
import Button from '../../Components/Button';
import { useState } from 'react';

function MemberRow({ member, teamId, isOwner, currentUserId }) {
    const canManage = isOwner && member.id !== currentUserId;

    return (
        <div className="flex items-center justify-between py-3 px-4 border-b border-border-light dark:border-border-dark last:border-b-0">
            <div>
                <div className="text-sm font-medium text-text-light dark:text-text-dark">{member.name}</div>
                <div className="text-xs text-muted">{member.email}</div>
            </div>
            <div className="flex items-center gap-3">
                {canManage ? (
                    <select
                        value={member.role}
                        onChange={e => router.put(`/teams/${teamId}/members/${member.id}`, { role: e.target.value })}
                        className="text-xs px-2 py-1 rounded border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark text-text-light dark:text-text-dark"
                    >
                        <option value="admin">Admin</option>
                        <option value="editor">Editor</option>
                        <option value="viewer">Viewer</option>
                    </select>
                ) : (
                    <span className="text-xs px-2 py-1 rounded-full bg-accent/10 text-accent capitalize">{member.role}</span>
                )}
                {canManage && (
                    <button
                        onClick={() => { if (confirm('Remove this member?')) router.delete(`/teams/${teamId}/members/${member.id}`); }}
                        className="text-xs text-danger hover:text-red-400"
                    >
                        Remove
                    </button>
                )}
            </div>
        </div>
    );
}

export default function Show() {
    const { team, members, invitations, isOwner } = usePage().props;
    const { auth } = usePage().props;
    const [showInvite, setShowInvite] = useState(false);

    const inviteForm = useForm({ email: '', role: 'editor' });
    const nameForm = useForm({ name: team.name });

    function submitInvite(e) {
        e.preventDefault();
        inviteForm.post(`/teams/${team.id}/invitations`, {
            onSuccess: () => { inviteForm.reset(); setShowInvite(false); },
        });
    }

    function submitName(e) {
        e.preventDefault();
        nameForm.put(`/teams/${team.id}`);
    }

    return (
        <AppLayout>
            <Head title={team.name} />

            <div className="flex items-center justify-between mb-8">
                <h1 className="text-2xl font-bold text-text-light dark:text-text-dark">{team.name}</h1>
                {isOwner && (
                    <Button
                        variant="ghost"
                        onClick={() => router.put(`/teams/${team.id}/switch`)}
                    >
                        Switch to this team
                    </Button>
                )}
            </div>

            {isOwner && (
                <div className="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-6 mb-6">
                    <h2 className="text-lg font-semibold text-text-light dark:text-text-dark mb-4">Team Settings</h2>
                    <form onSubmit={submitName} className="flex items-end gap-4 max-w-md">
                        <div className="flex-1">
                            <InputField label="Team Name" id="team-name" value={nameForm.data.name} onChange={e => nameForm.setData('name', e.target.value)} error={nameForm.errors.name} />
                        </div>
                        <Button type="submit" disabled={nameForm.processing}>Save</Button>
                    </form>
                </div>
            )}

            {/* Members */}
            <div className="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl mb-6">
                <div className="flex items-center justify-between px-4 py-3 border-b border-border-light dark:border-border-dark">
                    <h2 className="text-sm font-semibold text-text-light dark:text-text-dark">Members ({members.length})</h2>
                    {isOwner && (
                        <button onClick={() => setShowInvite(!showInvite)} className="text-xs text-accent hover:text-accent-hover">
                            {showInvite ? 'Cancel' : 'Invite member'}
                        </button>
                    )}
                </div>
                {showInvite && (
                    <form onSubmit={submitInvite} className="flex items-end gap-3 px-4 py-3 border-b border-border-light dark:border-border-dark bg-bg-light dark:bg-bg-dark">
                        <div className="flex-1">
                            <InputField label="Email" id="invite-email" type="email" value={inviteForm.data.email} onChange={e => inviteForm.setData('email', e.target.value)} error={inviteForm.errors.email} />
                        </div>
                        <select
                            value={inviteForm.data.role}
                            onChange={e => inviteForm.setData('role', e.target.value)}
                            className="px-2 py-2 rounded-lg border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark text-sm text-text-light dark:text-text-dark"
                        >
                            <option value="admin">Admin</option>
                            <option value="editor">Editor</option>
                            <option value="viewer">Viewer</option>
                        </select>
                        <Button type="submit" disabled={inviteForm.processing}>Send</Button>
                    </form>
                )}
                {members.map(member => (
                    <MemberRow key={member.id} member={member} teamId={team.id} isOwner={isOwner} currentUserId={auth.user.id} />
                ))}
            </div>

            {/* Pending invitations */}
            {invitations.length > 0 && (
                <div className="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl">
                    <div className="px-4 py-3 border-b border-border-light dark:border-border-dark">
                        <h2 className="text-sm font-semibold text-text-light dark:text-text-dark">Pending Invitations</h2>
                    </div>
                    {invitations.map(inv => (
                        <div key={inv.id} className="flex items-center justify-between py-3 px-4 border-b border-border-light dark:border-border-dark last:border-b-0">
                            <div>
                                <div className="text-sm text-text-light dark:text-text-dark">{inv.email}</div>
                                <div className="text-xs text-muted capitalize">{inv.role}</div>
                            </div>
                            {isOwner && (
                                <button
                                    onClick={() => router.delete(`/team-invitations/${inv.id}`)}
                                    className="text-xs text-danger hover:text-red-400"
                                >
                                    Cancel
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
