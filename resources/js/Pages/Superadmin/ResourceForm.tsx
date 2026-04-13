import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminFormSection from '@/Components/Superadmin/SuperadminFormSection';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type {
    SuperadminBreadcrumb,
    SuperadminFormSection as SuperadminFormSectionData,
    SuperadminFormStateAction,
    SuperadminFormSubmit,
    SuperadminResourceMeta,
} from '@/types/superadmin';
import { Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useMemo } from 'react';

type FormValue = string | number | boolean | File | null;

type ResourceFormProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    mode: 'create' | 'edit';
    recordLabel?: string | null;
    sections: SuperadminFormSectionData[];
    title?: string | null;
    description?: string | null;
    submit?: SuperadminFormSubmit | null;
    stateAction?: SuperadminFormStateAction | null;
};

function buildInitialData(sections: SuperadminFormSectionData[]) {
    return sections.reduce<Record<string, FormValue>>((accumulator, section) => {
        section.fields.forEach((field) => {
            accumulator[field.name] =
                field.type === 'file'
                    ? null
                    : field.type === 'toggle'
                    ? field.value === '1'
                    : field.value;
        });

        return accumulator;
    }, {});
}

export default function ResourceForm({
    meta,
    breadcrumbs,
    mode,
    recordLabel,
    sections,
    title,
    description,
    submit,
    stateAction,
}: ResourceFormProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const currentUrl = page.url;
    const initialData = useMemo(() => buildInitialData(sections), [sections]);
    const {
        data,
        setData,
        post,
        put,
        submit: submitRequest,
        transform,
        delete: destroy,
        processing,
        errors,
    } = useForm<Record<string, FormValue>>(initialData);

    const isCreate = mode === 'create';
    const resolvedTitle = title ?? (isCreate ? `Nuevo ${meta.singular}` : recordLabel ?? `Editar ${meta.singular}`);
    const resolvedDescription = description ?? (isCreate ? `Alta de ${meta.singular}.` : `Edicion de ${meta.singular}.`);

    useEffect(() => {
        setData(buildInitialData(sections));
    }, [currentUrl, setData]);

    const submitForm = (overrides: Partial<Record<string, FormValue>> = {}) => {
        if (!submit) {
            return;
        }

        const hasFiles = Object.entries(data).some(([name, value]) => {
            if (name in overrides) {
                return overrides[name] instanceof File;
            }

            return value instanceof File;
        });

        const options = {
            preserveScroll: true,
            forceFormData: hasFiles,
        };

        transform((values) =>
            submit.method === 'put' && hasFiles
                ? { ...values, ...overrides, _method: 'put' }
                : { ...values, ...overrides },
        );

        if (submit.method === 'put' && !hasFiles) {
            put(submit.action, options);
            return;
        }

        if (submit.method === 'put' && hasFiles) {
            submitRequest('post', submit.action, options);
            return;
        }

        post(submit.action, options);
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        submitForm();
    };

    const handleStateAction = () => {
        if (!submit || !stateAction) {
            return;
        }

        if (stateAction.confirm && !window.confirm(stateAction.confirm)) {
            return;
        }

        submitForm({
            est_usu: stateAction.value === '1',
        });
    };

    const handleDelete = () => {
        if (!submit?.deleteAction) {
            return;
        }

        if (!window.confirm(`Se eliminara este ${meta.singular}. Esta accion no se puede deshacer.`)) {
            return;
        }

        destroy(submit.deleteAction, {
            preserveScroll: true,
        });
    };

    return (
        <SuperadminLayout title={`MORE | ${resolvedTitle}`}>
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title={resolvedTitle}
                    description={resolvedDescription}
                    breadcrumbs={breadcrumbs}
                    actions={[{ label: 'Volver al modulo', href: meta.indexHref, kind: 'ghost' }]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error} />

                <form onSubmit={handleSubmit} className="space-y-4">
                    {sections.map((section) => (
                        <SuperadminFormSection
                            key={section.title}
                            section={section}
                            data={data}
                            errors={errors as Record<string, string>}
                            processing={processing}
                            readOnly={!submit}
                            onChange={(name, value) => setData(name, value)}
                        />
                    ))}

                    <div className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <p className="text-sm text-stone-300">
                                Revisa los datos antes de guardar.
                            </p>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href={meta.indexHref}
                                    className="sa-button-ghost px-5 py-3 text-center text-[0.72rem] uppercase tracking-[0.18em]"
                                >
                                    Cancelar
                                </Link>

                                {stateAction && (
                                    <button
                                        type="button"
                                        onClick={handleStateAction}
                                        disabled={processing}
                                        className="rounded-full border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.12)] px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] text-[rgb(245,208,210)] transition hover:bg-[rgba(183,108,112,0.18)] disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        {stateAction.label}
                                    </button>
                                )}

                                {submit?.deleteAction && (
                                    <button
                                        type="button"
                                        onClick={handleDelete}
                                        disabled={processing}
                                        className="rounded-full border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.12)] px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] text-[rgb(245,208,210)] transition hover:bg-[rgba(183,108,112,0.18)] disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        Eliminar
                                    </button>
                                )}

                                {submit && (
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        {processing ? 'Guardando' : isCreate ? 'Guardar' : 'Actualizar'}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </SuperadminLayout>
    );
}
