import { Head, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

function StatCard({ label, value }) {
    return (
        <div className="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl p-4">
            <div className="text-2xl font-bold text-text-light dark:text-text-dark">{value}</div>
            <div className="text-sm text-muted">{label}</div>
        </div>
    );
}

function ScriptRow({ script, sites, personas }) {
    const site = sites.find(s => s.id === script.site_id);
    const persona = personas.find(p => p.id === script.persona_id);

    return (
        <div className="flex items-center justify-between px-4 py-3 border-b border-border-light dark:border-border-dark last:border-b-0 hover:bg-bg-light dark:hover:bg-bg-dark transition-colors">
            <div className="flex-1 min-w-0">
                <div className="text-sm font-medium text-text-light dark:text-text-dark truncate">
                    {script.name}
                    {script.deleted_at && <span className="ml-2 text-xs text-danger">(deleted)</span>}
                </div>
                <div className="text-xs text-muted flex items-center gap-2 mt-0.5">
                    {site && <span>{site.label || site.hostname}</span>}
                    {persona && <><span className="text-border-dark">/</span><span>{persona.name}</span></>}
                </div>
            </div>
            <div className="text-xs text-muted font-mono shrink-0 ml-4">
                {script.fields?.length || 0} steps
            </div>
        </div>
    );
}

export default function Index() {
    const { sites, scripts, personas, stats } = usePage().props;
    const [search, setSearch] = useState('');

    const activeScripts = scripts.filter(s => !s.deleted_at);
    const filtered = search
        ? activeScripts.filter(s =>
            s.name.toLowerCase().includes(search.toLowerCase()) ||
            sites.find(site => site.id === s.site_id)?.hostname?.toLowerCase().includes(search.toLowerCase())
        )
        : activeScripts;

    // Group by site
    const grouped = {};
    const ungrouped = [];
    filtered.forEach(script => {
        if (script.site_id) {
            if (!grouped[script.site_id]) grouped[script.site_id] = [];
            grouped[script.site_id].push(script);
        } else {
            ungrouped.push(script);
        }
    });

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-text-light dark:text-text-dark">Script Library</h1>
                <p className="text-muted text-sm mt-1">Your synced form-fill scripts</p>
            </div>

            <div className="grid grid-cols-3 gap-4 mb-8">
                <StatCard label="Scripts" value={stats.total_scripts} />
                <StatCard label="Sites" value={stats.total_sites} />
                <StatCard label="Personas" value={stats.total_personas} />
            </div>

            <div className="mb-4">
                <input
                    type="text"
                    placeholder="Search scripts..."
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                    className="w-full max-w-sm px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark text-text-light dark:text-text-dark placeholder-muted focus:outline-none focus:ring-2 focus:ring-accent/50 text-sm"
                />
            </div>

            <div className="bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-xl overflow-hidden">
                {Object.entries(grouped).map(([siteId, siteScripts]) => {
                    const site = sites.find(s => s.id === siteId);
                    return (
                        <div key={siteId}>
                            <div className="px-4 py-2 bg-bg-light dark:bg-bg-dark border-b border-border-light dark:border-border-dark">
                                <span className="text-xs font-semibold text-muted uppercase tracking-wide">
                                    {site?.label || site?.hostname || 'Unknown site'}
                                </span>
                            </div>
                            {siteScripts.map(script => (
                                <ScriptRow key={script.id} script={script} sites={sites} personas={personas} />
                            ))}
                        </div>
                    );
                })}
                {ungrouped.length > 0 && (
                    <div>
                        <div className="px-4 py-2 bg-bg-light dark:bg-bg-dark border-b border-border-light dark:border-border-dark">
                            <span className="text-xs font-semibold text-muted uppercase tracking-wide">Ungrouped</span>
                        </div>
                        {ungrouped.map(script => (
                            <ScriptRow key={script.id} script={script} sites={sites} personas={personas} />
                        ))}
                    </div>
                )}
                {filtered.length === 0 && (
                    <div className="px-4 py-12 text-center text-muted text-sm">
                        {search ? 'No scripts match your search.' : 'No scripts yet. Install the extension to start recording.'}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
