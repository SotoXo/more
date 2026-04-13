import SuperadminComboboxField from '@/Components/Superadmin/SuperadminComboboxField';
import SuperadminDrawer from '@/Components/Superadmin/SuperadminDrawer';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminBreadcrumb, SuperadminFormFieldOption, SuperadminResourceMeta } from '@/types/superadmin';
import axios from 'axios';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ImagePlus, Plus, Sparkles, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState, type FormEvent } from 'react';

type VariantDraft = {
    cod_colores_prv: string;
    cod_tallas_prv: string;
    sku_prv: string;
    cob_prv: string;
    pve_prv: string;
    cos_prv: string;
    stock_inicial: string;
    ubi_inv: string;
};

type ProductCreateForm = {
    nom_pre: string;
    des_pre: string;
    cod_categorias_pre: string;
    cod_marcas_pre: string;
    cod_temporadas_pre: string;
    gen_pre: string;
    prb_pre: string;
    est_pre: boolean;
    usa_variantes: boolean;
    imagen_principal: File | null;
    fue_igp: string;
    variant_rows: VariantDraft[];
};

type InlineCatalogKey = 'categories' | 'colors' | 'sizes' | 'seasons';

type InlineTarget =
    | { type: 'product'; field: 'cod_categorias_pre' | 'cod_temporadas_pre' }
    | { type: 'variant'; field: 'cod_colores_prv' | 'cod_tallas_prv'; rowIndex: number };

type InlineDrawerState = {
    resource: InlineCatalogKey;
    target: InlineTarget;
    seed: string;
};

type InlineForm = {
    name: string;
    description: string;
    hex: string;
    order: string;
};

type SizeProfile = {
    key: 'alpha' | 'numeric' | 'unit';
    label: string;
    values: string[];
};

type ProductCreateProps = {
    meta: SuperadminResourceMeta;
    breadcrumbs: SuperadminBreadcrumb[];
    options: {
        categories: SuperadminFormFieldOption[];
        brands: SuperadminFormFieldOption[];
        seasons: SuperadminFormFieldOption[];
        genders: SuperadminFormFieldOption[];
        colors: SuperadminFormFieldOption[];
        sizes: SuperadminFormFieldOption[];
    };
};

const LOCATION_OPTIONS: SuperadminFormFieldOption[] = [
    { label: 'DEPÓSITO', value: 'DEPOSITO' },
    { label: 'TIENDA', value: 'TIENDA' },
];

function createVariantRow(defaultPrice = '', defaultLocation = 'TIENDA'): VariantDraft {
    return {
        cod_colores_prv: '',
        cod_tallas_prv: '',
        sku_prv: '',
        cob_prv: '',
        pve_prv: defaultPrice,
        cos_prv: '',
        stock_inicial: '1',
        ubi_inv: defaultLocation,
    };
}

function normalizeText(value: string) {
    return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
}

function inferSizeProfile(productName: string, categoryLabel: string): SizeProfile {
    const haystack = normalizeText(`${productName} ${categoryLabel}`);

    if (/(PANTAL|JEAN|DENIM|JOGGER|SHORT|BERMUDA|LEGGING)/.test(haystack)) {
        return { key: 'numeric', label: 'Tallas numericas sugeridas', values: ['32', '34', '36', '38', '40', '42', '44', '46', '48'] };
    }

    if (/(ACCESOR|BOLSO|CARTERA|CINTURON|BUFANDA|GORRO|JOY|COLLAR|PULSERA|ANILLO|ARETE|LENTE|SOMBRERO)/.test(haystack)) {
        return { key: 'unit', label: 'Talla sugerida', values: ['UNITALLA'] };
    }

    return { key: 'alpha', label: 'Tallas sugeridas', values: ['XS', 'S', 'M', 'L', 'XL'] };
}

function previewCodePrefix(source: string) {
    const letters = normalizeText(source).replace(/[^A-Z]/g, '');
    const prefix = (letters || 'PRE').slice(0, 3).padEnd(3, 'X');
    return `${prefix}-XXXXX`;
}

function previewVariantCode(baseCode: string, position: number, hasVariants: boolean) {
    return hasVariants ? `${baseCode}-${String(position).padStart(2, '0')}` : baseCode;
}

function suggestSizeOrder(name: string) {
    const normalized = normalizeText(name);
    const alphaMap: Record<string, number> = { XXS: 10, XS: 20, S: 30, M: 40, L: 50, XL: 60, XXL: 70, UNITALLA: 900 };

    if (alphaMap[normalized]) {
        return alphaMap[normalized];
    }

    if (/^\d+$/.test(normalized)) {
        return Number(normalized);
    }

    return 999;
}

function sortOptions(resource: InlineCatalogKey, items: SuperadminFormFieldOption[]) {
    const unique = Array.from(new Map(items.map((item) => [item.value, item])).values());

    if (resource === 'sizes') {
        return unique.sort((left, right) => suggestSizeOrder(left.label) - suggestSizeOrder(right.label));
    }

    return unique.sort((left, right) => left.label.localeCompare(right.label, 'es', { sensitivity: 'base' }));
}

function inlineDefaults(resource: InlineCatalogKey, seed: string): InlineForm {
    return {
        name: seed.trim().toUpperCase(),
        description: '',
        hex: '',
        order: resource === 'sizes' && seed ? String(suggestSizeOrder(seed)) : '',
    };
}

export default function ProductCreate({ meta, breadcrumbs, options }: ProductCreateProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const [optionSets, setOptionSets] = useState(options);
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const [showInternalFields, setShowInternalFields] = useState(false);
    const [inlineDrawer, setInlineDrawer] = useState<InlineDrawerState | null>(null);
    const [inlineForm, setInlineForm] = useState<InlineForm>(inlineDefaults('categories', ''));
    const [inlineErrors, setInlineErrors] = useState<Record<string, string>>({});
    const [inlineMessage, setInlineMessage] = useState<string | null>(null);
    const [inlineProcessing, setInlineProcessing] = useState(false);
    const { data, setData, post, processing, errors, transform } = useForm<ProductCreateForm>({
        nom_pre: '',
        des_pre: '',
        cod_categorias_pre: '',
        cod_marcas_pre: '',
        cod_temporadas_pre: '',
        gen_pre: '',
        prb_pre: '',
        est_pre: true,
        usa_variantes: false,
        imagen_principal: null,
        fue_igp: 'catalogo',
        variant_rows: [createVariantRow()],
    });

    const selectedCategory = useMemo(
        () => optionSets.categories.find((option) => option.value === data.cod_categorias_pre)?.label ?? '',
        [data.cod_categorias_pre, optionSets.categories],
    );

    const sizeProfile = useMemo(
        () => inferSizeProfile(data.nom_pre, selectedCategory),
        [data.nom_pre, selectedCategory],
    );

    const previewBaseCode = useMemo(
        () => previewCodePrefix(selectedCategory || data.nom_pre || 'PRENDA'),
        [data.nom_pre, selectedCategory],
    );

    const resolvedBasePrice = useMemo(() => {
        const prices = data.variant_rows.map((row) => Number(row.pve_prv || 0)).filter((value) => Number.isFinite(value) && value > 0);
        return prices.length > 0 ? String(prices[0]) : '0';
    }, [data.variant_rows]);

    const summary = useMemo(() => ({
        variants: data.variant_rows.length,
        stock: data.variant_rows.reduce((total, row) => total + (Number(row.stock_inicial) || 0), 0),
        price: resolvedBasePrice,
    }), [data.variant_rows, resolvedBasePrice]);

    const availableSuggestedSizes = useMemo(
        () =>
            sizeProfile.values
                .map((sizeName) =>
                    optionSets.sizes.find((option) => normalizeText(option.label) === normalizeText(sizeName)),
                )
                .filter((option): option is SuperadminFormFieldOption => Boolean(option)),
        [optionSets.sizes, sizeProfile.values],
    );

    const missingSuggestedSizes = useMemo(
        () =>
            sizeProfile.values.filter(
                (sizeName) =>
                    !optionSets.sizes.some((option) => normalizeText(option.label) === normalizeText(sizeName)),
            ),
        [optionSets.sizes, sizeProfile.values],
    );

    useEffect(() => {
        if (!data.imagen_principal) {
            setImagePreview(null);
            return;
        }

        const objectUrl = URL.createObjectURL(data.imagen_principal);
        setImagePreview(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [data.imagen_principal]);

    useEffect(() => {
        if (sizeProfile.values.length !== 1) {
            return;
        }

        const [suggestedSize] = sizeProfile.values;
        const firstRow = data.variant_rows[0];

        if (!firstRow || firstRow.cod_tallas_prv) {
            return;
        }

        const existingOption = optionSets.sizes.find(
            (option) => normalizeText(option.label) === normalizeText(suggestedSize),
        );

        if (!existingOption) {
            return;
        }

        updateVariantRow(0, 'cod_tallas_prv', existingOption.value);
    }, [data.variant_rows, optionSets.sizes, sizeProfile.values]);

    const updateVariantRow = (index: number, field: keyof VariantDraft, value: string) => {
        setData(
            'variant_rows',
            data.variant_rows.map((row, rowIndex) =>
                rowIndex === index ? { ...row, [field]: value } : row,
            ),
        );
    };

    const addVariantRow = () => {
        const seed = data.variant_rows[0] ?? createVariantRow(summary.price);
        setData('variant_rows', [
            ...data.variant_rows,
            createVariantRow(seed.pve_prv || summary.price, seed.ubi_inv || 'TIENDA'),
        ]);
    };

    const removeVariantRow = (index: number) => {
        if (data.variant_rows.length === 1) {
            return;
        }

        setData('variant_rows', data.variant_rows.filter((_, rowIndex) => rowIndex !== index));
    };

    const toggleVariants = (enabled: boolean) => {
        setData((current) => ({
            ...current,
            usa_variantes: enabled,
            variant_rows: enabled
                ? current.variant_rows
                : [current.variant_rows[0] ?? createVariantRow(summary.price)],
        }));
    };

    const openInlineCreate = (resource: InlineCatalogKey, target: InlineTarget, seed = '') => {
        setInlineDrawer({ resource, target, seed });
        setInlineForm(inlineDefaults(resource, seed));
        setInlineErrors({});
        setInlineMessage(null);
    };

    const closeInlineCreate = () => {
        setInlineDrawer(null);
        setInlineErrors({});
        setInlineMessage(null);
    };

    const applyInlineSelection = (target: InlineTarget, value: string) => {
        if (target.type === 'product') {
            setData(target.field, value);
            return;
        }

        updateVariantRow(target.rowIndex, target.field, value);
    };

    const submitInlineCreate = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!inlineDrawer) {
            return;
        }

        const resourcePath = {
            categories: 'categorias',
            colors: 'colores',
            sizes: 'tallas',
            seasons: 'temporadas',
        }[inlineDrawer.resource];

        const payload = inlineDrawer.resource === 'categories'
            ? { nom_cat: inlineForm.name, des_cat: inlineForm.description, est_cat: true }
            : inlineDrawer.resource === 'colors'
            ? { nom_col: inlineForm.name, hex_col: inlineForm.hex || null, est_col: true }
            : inlineDrawer.resource === 'sizes'
            ? { nom_tal: inlineForm.name, ord_tal: inlineForm.order || suggestSizeOrder(inlineForm.name), est_tal: true }
            : { nom_tem: inlineForm.name, des_tem: inlineForm.description, est_tem: true };

        setInlineProcessing(true);
        setInlineErrors({});
        setInlineMessage(null);

        try {
            const response = await axios.post(
                `/superadmin/catalogo/${resourcePath}/inline`,
                payload,
                { headers: { Accept: 'application/json' } },
            );

            const option = response.data.option as SuperadminFormFieldOption;

            setOptionSets((current) => ({
                ...current,
                [inlineDrawer.resource]: sortOptions(inlineDrawer.resource, [
                    ...current[inlineDrawer.resource],
                    option,
                ]),
            }));
            applyInlineSelection(inlineDrawer.target, option.value);
            closeInlineCreate();
        } catch (error) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const fieldErrors = error.response.data.errors ?? {};
                setInlineErrors(
                    Object.fromEntries(
                        Object.entries(fieldErrors).map(([key, value]) => [
                            key,
                            Array.isArray(value) ? String(value[0]) : String(value),
                        ]),
                    ),
                );
            } else {
                setInlineMessage('No se pudo crear el registro desde este flujo.');
            }
        } finally {
            setInlineProcessing(false);
        }
    };

    const applySuggestedSizes = () => {
        if (availableSuggestedSizes.length === 0) {
            return;
        }

        const seed = data.variant_rows[0] ?? createVariantRow(summary.price);

        if (!data.usa_variantes) {
            updateVariantRow(0, 'cod_tallas_prv', availableSuggestedSizes[0].value);
            return;
        }

        setData(
            'variant_rows',
            availableSuggestedSizes.map((option, index) => ({
                ...createVariantRow(seed.pve_prv || summary.price, seed.ubi_inv || 'TIENDA'),
                cod_colores_prv: seed.cod_colores_prv,
                cod_tallas_prv: option.value,
                cob_prv: index === 0 ? seed.cob_prv : '',
                pve_prv: seed.pve_prv || summary.price,
                cos_prv: seed.cos_prv,
                stock_inicial: index === 0 ? seed.stock_inicial || '1' : '1',
            })),
        );
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        transform((values) => ({
            ...values,
            prb_pre: resolvedBasePrice,
            variant_rows: values.variant_rows.map((row, index) => ({
                ...row,
                sku_prv: previewVariantCode(previewBaseCode, index + 1, values.usa_variantes),
            })),
        }));

        post(meta.indexHref, {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const inlineTitle = inlineDrawer
        ? {
            categories: 'Nueva categoria',
            colors: 'Nuevo color',
            sizes: 'Nueva talla',
            seasons: 'Nueva temporada',
        }[inlineDrawer.resource]
        : '';

    const inlineDescription = inlineDrawer
        ? {
            categories: 'Crea la categoria y vuelve al alta con la opcion seleccionada.',
            colors: 'Crea el color y usalo de inmediato en la variante actual.',
            sizes: 'Crea la talla y continua sin salir del registro de prenda.',
            seasons: 'Crea la temporada y vuelve automaticamente al formulario.',
        }[inlineDrawer.resource]
        : '';

    return (
        <SuperadminLayout title="MORE | Nueva prenda">
            <div className="space-y-8">
                <SuperadminPageHeader
                    eyebrow={meta.sectionLabel}
                    title="Nueva prenda"
                    description="Registra la prenda, su imagen principal y la configuracion inicial de venta en un solo flujo."
                    breadcrumbs={breadcrumbs}
                    actions={[{ label: 'Volver al modulo', href: meta.indexHref, kind: 'ghost' }]}
                />

                <SuperadminFlashNotice success={flash?.success} error={flash?.error ?? inlineMessage} />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 1</p>
                                <h2 className="mt-2 text-lg text-white">Datos de la prenda</h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">
                                    Completa la informacion base y deja listo el codigo unico que se confirmara al guardar.
                                </p>
                            </div>
                            <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] px-4 py-3">
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Codigo unico de prenda</p>
                                <p className="mt-2 text-sm text-white">{previewBaseCode}</p>
                            </div>
                        </div>

                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            <label className="space-y-2 md:col-span-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Nombre de la prenda</span>
                                <input
                                    value={data.nom_pre}
                                    onChange={(event) => setData('nom_pre', event.target.value)}
                                    className="sa-input w-full"
                                    placeholder="Blazer estructurado"
                                />
                                {errors.nom_pre && <p className="text-sm text-rose-300">{errors.nom_pre}</p>}
                            </label>

                            <label className="space-y-2 md:col-span-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Descripcion</span>
                                <textarea
                                    value={data.des_pre}
                                    onChange={(event) => setData('des_pre', event.target.value)}
                                    className="sa-input min-h-28 w-full resize-none"
                                    placeholder="Detalle breve para el equipo y la tienda."
                                />
                                {errors.des_pre && <p className="text-sm text-rose-300">{errors.des_pre}</p>}
                            </label>

                            <SuperadminComboboxField
                                label="Categoria"
                                value={data.cod_categorias_pre}
                                options={optionSets.categories}
                                placeholder="Selecciona una categoria"
                                searchPlaceholder="Buscar categoria"
                                error={errors.cod_categorias_pre}
                                onChange={(value) => setData('cod_categorias_pre', value)}
                                onCreate={(seed) => openInlineCreate('categories', { type: 'product', field: 'cod_categorias_pre' }, seed)}
                            />

                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Marca</span>
                                <select
                                    value={data.cod_marcas_pre}
                                    onChange={(event) => setData('cod_marcas_pre', event.target.value)}
                                    className="sa-input w-full"
                                >
                                    <option value="">Sin marca</option>
                                    {optionSets.brands.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                                {errors.cod_marcas_pre && <p className="text-sm text-rose-300">{errors.cod_marcas_pre}</p>}
                            </label>

                            <SuperadminComboboxField
                                label="Temporada"
                                value={data.cod_temporadas_pre}
                                options={optionSets.seasons}
                                placeholder="Selecciona una temporada"
                                searchPlaceholder="Buscar temporada"
                                error={errors.cod_temporadas_pre}
                                onChange={(value) => setData('cod_temporadas_pre', value)}
                                onCreate={(seed) => openInlineCreate('seasons', { type: 'product', field: 'cod_temporadas_pre' }, seed)}
                            />

                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Genero</span>
                                <select
                                    value={data.gen_pre}
                                    onChange={(event) => setData('gen_pre', event.target.value)}
                                    className="sa-input w-full"
                                >
                                    <option value="">Selecciona genero</option>
                                    {optionSets.genders.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                                {errors.gen_pre && <p className="text-sm text-rose-300">{errors.gen_pre}</p>}
                            </label>

                            <label className="space-y-3 md:col-span-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Estado inicial</span>
                                <button
                                    type="button"
                                    onClick={() => setData('est_pre', !data.est_pre)}
                                    className={`flex w-full items-center justify-between rounded-[1.1rem] border px-4 py-3 text-left transition ${
                                        data.est_pre
                                            ? 'border-[rgba(124,140,255,0.28)] bg-[rgba(124,140,255,0.12)] text-white'
                                            : 'border-white/10 bg-[#0d141d] text-stone-300'
                                    }`}
                                >
                                    <span className="text-sm">
                                        {data.est_pre ? 'La prenda quedara activa al guardar.' : 'La prenda quedara oculta hasta activarla.'}
                                    </span>
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                        {data.est_pre ? 'Activa' : 'Oculta'}
                                    </span>
                                </button>
                            </label>
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 2</p>
                                <h2 className="mt-2 text-lg text-white">Imagen principal</h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">
                                    Carga la imagen base del producto para dejar lista la presentacion principal desde este mismo alta.
                                </p>
                            </div>
                            <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] px-4 py-3">
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Estado de imagen</p>
                                <p className="mt-2 text-sm text-white">
                                    {data.imagen_principal ? 'Lista para guardar' : 'Pendiente'}
                                </p>
                            </div>
                        </div>

                        <div className="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1.08fr)_minmax(280px,0.92fr)]">
                            <label className="block space-y-3">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                    Imagen principal de la prenda
                                </span>
                                <div className="rounded-[1.4rem] border border-dashed border-white/14 bg-[#0d141d] p-5">
                                    <div className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-stone-300">
                                        <ImagePlus size={18} />
                                    </div>
                                    <p className="mt-4 text-sm text-white">Se usara como imagen base del producto.</p>
                                    <p className="mt-2 max-w-xl text-sm leading-6 text-stone-400">
                                        Puede reutilizarse para tallas o variantes y quedara como referencia principal de la prenda.
                                    </p>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={(event) => setData('imagen_principal', event.target.files?.[0] ?? null)}
                                        className="sa-input mt-4 w-full file:mr-4 file:rounded-full file:border-0 file:bg-white/8 file:px-4 file:py-2 file:text-xs file:uppercase file:tracking-[0.18em] file:text-stone-200"
                                    />
                                </div>
                                {errors.imagen_principal && <p className="text-sm text-rose-300">{errors.imagen_principal}</p>}
                            </label>

                            <div className="rounded-[1.4rem] border border-white/8 bg-[#0d141d] p-4">
                                {imagePreview ? (
                                    <div className="overflow-hidden rounded-[1.2rem] border border-white/8 bg-[#090d13]">
                                        <img src={imagePreview} alt="Vista previa de la prenda" className="h-[320px] w-full object-cover" />
                                    </div>
                                ) : (
                                    <div className="flex h-[320px] flex-col items-center justify-center rounded-[1.2rem] border border-dashed border-white/10 bg-[#090d13] px-6 text-center">
                                        <div className="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-stone-300">
                                            <ImagePlus size={18} />
                                        </div>
                                        <p className="mt-4 text-sm text-white">Aun no hay una imagen seleccionada.</p>
                                        <p className="mt-2 max-w-xs text-sm leading-6 text-stone-400">
                                            Carga la imagen principal para usarla como base del producto y de sus variantes.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 3</p>
                                <h2 className="mt-2 text-lg text-white">Configuracion comercial</h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">
                                    Define si la prenda se cargara como una sola referencia o con variantes de color y talla.
                                </p>
                            </div>

                            <div className="flex rounded-full border border-white/10 bg-[#0d141d] p-1">
                                <button
                                    type="button"
                                    onClick={() => toggleVariants(false)}
                                    className={`rounded-full px-4 py-2 text-[0.68rem] uppercase tracking-[0.18em] transition ${!data.usa_variantes ? 'bg-[rgba(124,140,255,0.18)] text-white' : 'text-stone-400 hover:text-white'}`}
                                >
                                    Prenda unica
                                </button>
                                <button
                                    type="button"
                                    onClick={() => toggleVariants(true)}
                                    className={`rounded-full px-4 py-2 text-[0.68rem] uppercase tracking-[0.18em] transition ${data.usa_variantes ? 'bg-[rgba(124,140,255,0.18)] text-white' : 'text-stone-400 hover:text-white'}`}
                                >
                                    Prenda con variantes
                                </button>
                            </div>
                        </div>

                        <div className="mt-5 rounded-[1.4rem] border border-white/8 bg-[#0d141d] p-4">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <div className="inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                        <Sparkles size={13} />
                                        Perfil sugerido
                                    </div>
                                    <p className="mt-2 text-sm text-white">{sizeProfile.label}</p>
                                    <p className="mt-2 text-sm text-stone-400">{sizeProfile.values.join(', ')}</p>
                                    {missingSuggestedSizes.length > 0 && (
                                        <p className="mt-2 text-sm text-amber-200">
                                            Faltan tallas en catalogo: {missingSuggestedSizes.join(', ')}.
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-wrap gap-3">
                                    {availableSuggestedSizes.length > 0 && (
                                        <button
                                            type="button"
                                            onClick={applySuggestedSizes}
                                            className="sa-button-secondary px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]"
                                        >
                                            Aplicar sugerencia
                                        </button>
                                    )}
                                    {missingSuggestedSizes.length > 0 && (
                                        <button
                                            type="button"
                                            onClick={() => openInlineCreate('sizes', { type: 'variant', field: 'cod_tallas_prv', rowIndex: 0 }, missingSuggestedSizes[0])}
                                            className="sa-button-ghost px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]"
                                        >
                                            Crear talla sugerida
                                        </button>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => setShowInternalFields((current) => !current)}
                                        className="sa-button-ghost px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]"
                                    >
                                        {showInternalFields ? 'Ocultar datos internos' : 'Mostrar datos internos'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 4</p>
                                <h2 className="mt-2 text-lg text-white">
                                    {data.usa_variantes ? 'Variantes y stock inicial' : 'Prenda unica y stock inicial'}
                                </h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">
                                    Registra precio de venta, stock inicial y ubicacion desde este mismo paso.
                                </p>
                            </div>

                            {data.usa_variantes && (
                                <button
                                    type="button"
                                    onClick={addVariantRow}
                                    className="sa-button-secondary inline-flex items-center gap-2 px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]"
                                >
                                    <Plus size={14} />
                                    Agregar variante
                                </button>
                            )}
                        </div>

                        <div className="mt-5 space-y-4">
                            {data.variant_rows.map((row, index) => (
                                <div key={`variant-row-${index}`} className="rounded-[1.4rem] border border-white/8 bg-[#0d141d] p-4">
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="text-sm text-white">{data.usa_variantes ? `Variante ${index + 1}` : 'Prenda unica'}</p>
                                            <p className="mt-1 text-sm text-stone-400">
                                                {previewVariantCode(previewBaseCode, index + 1, data.usa_variantes)}
                                            </p>
                                        </div>

                                        {data.usa_variantes && data.variant_rows.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={() => removeVariantRow(index)}
                                                className="sa-button-ghost h-10 w-10 p-0"
                                                aria-label={`Eliminar variante ${index + 1}`}
                                            >
                                                <Trash2 size={15} />
                                            </button>
                                        )}
                                    </div>

                                    <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <SuperadminComboboxField
                                            label="Color"
                                            value={row.cod_colores_prv}
                                            options={optionSets.colors}
                                            placeholder="Selecciona color"
                                            searchPlaceholder="Buscar color"
                                            error={errors[`variant_rows.${index}.cod_colores_prv`]}
                                            onChange={(value) => updateVariantRow(index, 'cod_colores_prv', value)}
                                            onCreate={(seed) => openInlineCreate('colors', { type: 'variant', field: 'cod_colores_prv', rowIndex: index }, seed)}
                                        />

                                        <SuperadminComboboxField
                                            label="Talla"
                                            value={row.cod_tallas_prv}
                                            options={optionSets.sizes}
                                            placeholder="Selecciona talla"
                                            searchPlaceholder="Buscar talla"
                                            error={errors[`variant_rows.${index}.cod_tallas_prv`]}
                                            hint={index === 0 && sizeProfile.values.length > 0 ? `Sugerencia: ${sizeProfile.values.join(', ')}` : undefined}
                                            onChange={(value) => updateVariantRow(index, 'cod_tallas_prv', value)}
                                            onCreate={(seed) => openInlineCreate('sizes', { type: 'variant', field: 'cod_tallas_prv', rowIndex: index }, seed)}
                                        />

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Codigo interno</span>
                                            <input value={previewVariantCode(previewBaseCode, index + 1, data.usa_variantes)} disabled className="sa-input w-full" />
                                        </label>

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Precio de venta (Bs.)</span>
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={row.pve_prv}
                                                onChange={(event) => updateVariantRow(index, 'pve_prv', event.target.value)}
                                                className="sa-input w-full"
                                                placeholder="0.00"
                                            />
                                            {errors[`variant_rows.${index}.pve_prv`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.pve_prv`]}</p>}
                                        </label>

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Stock inicial</span>
                                            <input
                                                type="number"
                                                min="1"
                                                step="1"
                                                value={row.stock_inicial}
                                                onChange={(event) => updateVariantRow(index, 'stock_inicial', event.target.value)}
                                                className="sa-input w-full"
                                                placeholder="1"
                                            />
                                            {errors[`variant_rows.${index}.stock_inicial`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.stock_inicial`]}</p>}
                                        </label>

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Ubicacion</span>
                                            <select
                                                value={row.ubi_inv}
                                                onChange={(event) => updateVariantRow(index, 'ubi_inv', event.target.value)}
                                                className="sa-input w-full"
                                            >
                                                {LOCATION_OPTIONS.map((option) => (
                                                    <option key={`${option.value}-${index}-location`} value={option.value}>{option.label}</option>
                                                ))}
                                            </select>
                                            {errors[`variant_rows.${index}.ubi_inv`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.ubi_inv`]}</p>}
                                        </label>

                                        {showInternalFields && (
                                            <>
                                                <label className="space-y-2">
                                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Costo interno (Bs.)</span>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={row.cos_prv}
                                                        onChange={(event) => updateVariantRow(index, 'cos_prv', event.target.value)}
                                                        className="sa-input w-full"
                                                        placeholder="0.00"
                                                    />
                                                    <p className="text-sm text-stone-400">Dato interno para margen y rentabilidad.</p>
                                                    {errors[`variant_rows.${index}.cos_prv`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.cos_prv`]}</p>}
                                                </label>

                                                <label className="space-y-2">
                                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Codigo de barras (opcional)</span>
                                                    <input
                                                        value={row.cob_prv}
                                                        onChange={(event) => updateVariantRow(index, 'cob_prv', event.target.value)}
                                                        className="sa-input w-full"
                                                        placeholder="1234567890123"
                                                    />
                                                    {errors[`variant_rows.${index}.cob_prv`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.cob_prv`]}</p>}
                                                </label>
                                            </>
                                        )}
                                    </div>
                                </div>
                            ))}

                            {errors.variant_rows && <p className="text-sm text-rose-300">{errors.variant_rows}</p>}
                        </div>
                    </section>

                    <section className="sa-panel rounded-[1.6rem] p-5">
                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-[1.3rem] border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Configuracion</p>
                                <p className="mt-3 text-2xl text-white">{data.usa_variantes ? 'Variantes' : 'Unica'}</p>
                            </div>
                            <div className="rounded-[1.3rem] border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Precio principal</p>
                                <p className="mt-3 text-2xl text-white">Bs {Number(summary.price || 0).toFixed(2)}</p>
                            </div>
                            <div className="rounded-[1.3rem] border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Stock inicial</p>
                                <p className="mt-3 text-2xl text-white">{summary.stock}</p>
                            </div>
                        </div>

                        <div className="mt-5 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href={meta.indexHref}
                                className="sa-button-ghost px-5 py-3 text-center text-[0.72rem] uppercase tracking-[0.18em]"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                {processing ? 'Guardando' : 'Guardar prenda'}
                            </button>
                        </div>
                    </section>
                </form>
            </div>

            <SuperadminDrawer open={Boolean(inlineDrawer)} title={inlineTitle} description={inlineDescription} onClose={closeInlineCreate}>
                {inlineDrawer && (
                    <form onSubmit={submitInlineCreate} className="space-y-4">
                        <label className="space-y-2">
                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Nombre</span>
                            <input
                                value={inlineForm.name}
                                onChange={(event) =>
                                    setInlineForm((current) => ({
                                        ...current,
                                        name: event.target.value.toUpperCase(),
                                        order: inlineDrawer.resource === 'sizes' ? String(suggestSizeOrder(event.target.value)) : current.order,
                                    }))
                                }
                                className="sa-input w-full"
                                placeholder="Nombre del registro"
                            />
                            {inlineErrors.nom_cat && <p className="text-sm text-rose-300">{inlineErrors.nom_cat}</p>}
                            {inlineErrors.nom_col && <p className="text-sm text-rose-300">{inlineErrors.nom_col}</p>}
                            {inlineErrors.nom_tal && <p className="text-sm text-rose-300">{inlineErrors.nom_tal}</p>}
                            {inlineErrors.nom_tem && <p className="text-sm text-rose-300">{inlineErrors.nom_tem}</p>}
                        </label>

                        {(inlineDrawer.resource === 'categories' || inlineDrawer.resource === 'seasons') && (
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Descripcion breve</span>
                                <textarea
                                    value={inlineForm.description}
                                    onChange={(event) => setInlineForm((current) => ({ ...current, description: event.target.value }))}
                                    className="sa-input min-h-24 w-full resize-none"
                                    placeholder="Detalle opcional"
                                />
                            </label>
                        )}

                        {inlineDrawer.resource === 'colors' && (
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">HEX (opcional)</span>
                                <input
                                    value={inlineForm.hex}
                                    onChange={(event) => setInlineForm((current) => ({ ...current, hex: event.target.value.toUpperCase() }))}
                                    className="sa-input w-full"
                                    placeholder="#1A1A1A"
                                />
                                {inlineErrors.hex_col && <p className="text-sm text-rose-300">{inlineErrors.hex_col}</p>}
                            </label>
                        )}

                        {inlineDrawer.resource === 'sizes' && (
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Orden</span>
                                <input
                                    value={inlineForm.order}
                                    onChange={(event) => setInlineForm((current) => ({ ...current, order: event.target.value }))}
                                    className="sa-input w-full"
                                />
                                {inlineErrors.ord_tal && <p className="text-sm text-rose-300">{inlineErrors.ord_tal}</p>}
                            </label>
                        )}

                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button" onClick={closeInlineCreate} className="sa-button-ghost px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                Cancelar
                            </button>
                            <button type="submit" disabled={inlineProcessing} className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70">
                                {inlineProcessing ? 'Guardando' : 'Crear y seleccionar'}
                            </button>
                        </div>
                    </form>
                )}
            </SuperadminDrawer>
        </SuperadminLayout>
    );
}
