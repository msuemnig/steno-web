export default function Button({ children, variant = 'primary', className = '', disabled, ...props }) {
    const variants = {
        primary: 'bg-accent hover:bg-accent-hover text-gray-900 font-semibold',
        danger: 'bg-danger hover:bg-red-600 text-white font-semibold',
        ghost: 'border border-border-light dark:border-border-dark text-muted hover:text-text-light dark:hover:text-text-dark',
        secondary: 'bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark text-text-light dark:text-text-dark hover:bg-bg-light dark:hover:bg-bg-dark',
    };

    return (
        <button
            className={`px-4 py-2 rounded-lg text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${variants[variant]} ${className}`}
            disabled={disabled}
            {...props}
        >
            {children}
        </button>
    );
}
