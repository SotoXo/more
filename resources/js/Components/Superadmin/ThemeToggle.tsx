import { Moon, Sun } from 'lucide-react';

export type AdminTheme = 'dark' | 'light';

export default function ThemeToggle({
    theme,
    onToggle,
}: {
    theme: AdminTheme;
    onToggle: () => void;
}) {
    const isLight = theme === 'light';

    return (
        <button
            type="button"
            onClick={onToggle}
            title="Cambiar tema"
            aria-label="Cambiar tema"
            className="sa-theme-toggle"
        >
            <span className="sa-theme-toggle__icon">
                {isLight ? <Sun size={15} /> : <Moon size={15} />}
            </span>
            <span className="hidden text-[0.7rem] uppercase tracking-[0.18em] lg:inline">
                {isLight ? 'Modo claro' : 'Modo oscuro'}
            </span>
        </button>
    );
}
