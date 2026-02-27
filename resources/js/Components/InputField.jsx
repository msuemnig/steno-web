export default function InputField({ label, id, type = 'text', error, ...props }) {
    return (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-text-light dark:text-text-dark mb-1">
                {label}
            </label>
            <input
                id={id}
                type={type}
                className="w-full px-3 py-2 rounded-lg border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark text-text-light dark:text-text-dark placeholder-muted focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition-colors"
                {...props}
            />
            {error && <p className="mt-1 text-sm text-danger">{error}</p>}
        </div>
    );
}
