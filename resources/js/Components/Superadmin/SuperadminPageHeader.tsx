import type { SuperadminAction, SuperadminBreadcrumb } from '@/types/superadmin';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

const serifStyle = {
    fontFamily:
        '"Cormorant Garamond", "Iowan Old Style", "Palatino Linotype", Georgia, serif',
};

function actionClass(kind: SuperadminAction['kind']) {
    if (kind === 'secondary') {
        return 'sa-button-secondary';
    }

    if (kind === 'ghost') {
        return 'sa-button-ghost';
    }

    return 'sa-button-primary';
}

export default function SuperadminPageHeader({
    eyebrow,
    title,
    description,
    breadcrumbs,
    actions = [],
}: {
    eyebrow: string;
    title: string;
    description: string;
    breadcrumbs?: SuperadminBreadcrumb[];
    actions?: SuperadminAction[];
}) {
    return (
        <div className="space-y-4">
            {breadcrumbs && breadcrumbs.length > 0 && (
                <div className="flex flex-wrap items-center gap-2 text-[0.72rem] uppercase tracking-[0.18em] text-stone-300">
                    {breadcrumbs.map((item, index) => (
                        <div key={`${item.label}-${index}`} className="inline-flex items-center gap-2">
                            {item.href ? (
                                <Link href={item.href} className="transition hover:text-[rgb(210,227,239)]">
                                    {item.label}
                                </Link>
                            ) : (
                                <span className="text-white">{item.label}</span>
                            )}
                            {index < breadcrumbs.length - 1 && <ChevronRight size={12} />}
                        </div>
                    ))}
                </div>
            )}

            <div className="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div className="max-w-3xl">
                    <p className="text-[0.7rem] uppercase tracking-[0.22em] text-[rgb(194,214,232)]">
                        {eyebrow}
                    </p>
                    <h1 className="mt-2 text-3xl leading-[0.98] text-white sm:text-4xl" style={serifStyle}>
                        {title}
                    </h1>
                    <p className="mt-2 max-w-xl text-sm leading-6 text-stone-300">
                        {description}
                    </p>
                </div>

                {actions.length > 0 && (
                    <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap xl:justify-end">
                        {actions.map((action) =>
                            action.href ? (
                                <Link
                                    key={`${action.label}-${action.href}`}
                                    href={action.href}
                                    className={`${actionClass(action.kind)} px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]`}
                                >
                                    {action.label}
                                </Link>
                            ) : (
                                <button
                                    key={action.label}
                                    type="button"
                                    className={`${actionClass(action.kind)} px-5 py-3 text-[0.72rem] uppercase tracking-[0.22em]`}
                                >
                                    {action.label}
                                </button>
                            ),
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
