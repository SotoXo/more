import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminComboboxField from '@/Components/Superadmin/SuperadminComboboxField';
import ProductColorPicker from '@/Components/Superadmin/ProductColorPicker';
import SuperadminDrawer from '@/Components/Superadmin/SuperadminDrawer';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminBreadcrumb, SuperadminFormFieldOption, SuperadminFormStateAction, SuperadminFormSubmit, SuperadminResourceMeta } from '@/types/superadmin';
import axios from 'axios';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, ImagePlus, Palette, Plus, Sparkles } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react';

type ProductVariantRow = { id: string; sku_prv: string; cod_colores_prv: string; cod_tallas_prv: string; cob_prv: string; pve_prv: string; cos_prv: string; stock_inicial: string; ubi_inv: string; est_prv: boolean; remove: boolean; };
type ExistingImageRow = { id: string; url_igp: string; rut_igp: string; ord_igp: string; pri_igp: boolean; fue_igp: string; variant_label?: string | null; remove: boolean; };
type NewGalleryImageRow = { archivo: File | null; ord_igp: string; pri_igp: boolean; fue_igp: string; };
type ProductEditorForm = { nom_pre: string; des_pre: string; cod_categorias_pre: string; cod_marcas_pre: string; cod_temporadas_pre: string; gen_pre: string; prb_pre: string; est_pre: boolean; usa_variantes: boolean; imagen_principal: File | null; fue_igp: string; existing_images: ExistingImageRow[]; gallery_images: NewGalleryImageRow[]; variant_rows: ProductVariantRow[]; };
type InlineCatalogKey = 'categories' | 'brands' | 'colors' | 'sizes' | 'seasons';
type ProductStepId = 'base' | 'image' | 'type' | 'commercial' | 'variants' | 'review';
type InlineTarget = { type: 'product'; field: 'cod_categorias_pre' | 'cod_marcas_pre' | 'cod_temporadas_pre' } | { type: 'variant'; field: 'cod_colores_prv' | 'cod_tallas_prv'; rowIndex: number };
type InlineDrawerState = { resource: InlineCatalogKey; target: InlineTarget; seed: string; };
type InlineForm = { name: string; family: string; hex: string; order: string; };
type SizeProfile = { key: 'alpha' | 'numeric' | 'unit'; label: string; values: string[]; };
type ProductStep = { id: ProductStepId; number: number; title: string; description: string; };
type ColorInsight = { hex: string; family: SuperadminFormFieldOption | null; suggestedColor: SuperadminFormFieldOption | null; };
type PickedColorSample = { hex: string; relativeX: number; relativeY: number; };
type ProductCreateProps = { meta: SuperadminResourceMeta; breadcrumbs: SuperadminBreadcrumb[]; mode: 'create' | 'edit'; recordLabel?: string | null; submit: SuperadminFormSubmit; stateAction?: SuperadminFormStateAction | null; editor: { title: string; description: string; currentPrimaryUrl?: string | null; currentPrimaryLabel?: string | null; }; options: { categories: SuperadminFormFieldOption[]; brands: SuperadminFormFieldOption[]; seasons: SuperadminFormFieldOption[]; genders: SuperadminFormFieldOption[]; colors: SuperadminFormFieldOption[]; sizes: SuperadminFormFieldOption[]; locations: SuperadminFormFieldOption[]; colorFamilies: SuperadminFormFieldOption[]; categoryProfiles: Record<string, string>; }; initialData: ProductEditorForm; };

function createVariantRow(defaultPrice = '', defaultLocation = 'TIENDA'): ProductVariantRow { return { id: '', sku_prv: '', cod_colores_prv: '', cod_tallas_prv: '', cob_prv: '', pve_prv: defaultPrice, cos_prv: '', stock_inicial: '1', ubi_inv: defaultLocation, est_prv: true, remove: false }; }
function createGalleryRow(order = '1', source = 'galeria'): NewGalleryImageRow { return { archivo: null, ord_igp: order, pri_igp: false, fue_igp: source }; }
function normalizeText(value: string) { return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase(); }
function inferCategoryProfile(name: string) { const haystack = normalizeText(name); if (haystack.includes('PANTAL')) return 'NUMERICA'; if (haystack.includes('ACCESOR')) return 'UNITALLA'; return 'ALFA'; }
function inferSizeProfile(productName: string, categoryLabel: string, categoryProfile?: string): SizeProfile { const profile = normalizeText(categoryProfile ?? ''); if (profile === 'NUMERICA') return { key: 'numeric', label: 'Tallas numéricas sugeridas', values: ['32', '34', '36', '38', '40', '42', '44', '46', '48'] }; if (profile === 'UNITALLA') return { key: 'unit', label: 'Talla sugerida', values: ['UNITALLA'] }; const haystack = normalizeText(`${productName} ${categoryLabel}`); if (/(PANTAL|JEAN|DENIM|JOGGER|SHORT|BERMUDA|LEGGING)/.test(haystack)) return { key: 'numeric', label: 'Tallas numéricas sugeridas', values: ['32', '34', '36', '38', '40', '42', '44', '46', '48'] }; if (/(ACCESOR|BOLSO|CARTERA|CINTURON|BUFANDA|GORRO|JOY|COLLAR|PULSERA|ANILLO|ARETE|LENTE|SOMBRERO)/.test(haystack)) return { key: 'unit', label: 'Talla sugerida', values: ['UNITALLA'] }; return { key: 'alpha', label: 'Tallas sugeridas', values: ['XS', 'S', 'M', 'L', 'XL'] }; }
function previewCodePrefix(source: string) { const letters = normalizeText(source).replace(/[^A-Z]/g, ''); const prefix = (letters || 'PRE').slice(0, 3).padEnd(3, 'X'); return `${prefix}-XXXXX`; }
function previewVariantCode(baseCode: string, position: number, hasVariants: boolean, persistedCode = '') { if (persistedCode) return persistedCode; return hasVariants ? `${baseCode}-${String(position).padStart(2, '0')}` : baseCode; }
function suggestSizeOrder(name: string) { const normalized = normalizeText(name); const alphaMap: Record<string, number> = { UNITALLA: 10, XXS: 20, XS: 30, S: 40, M: 50, L: 60, XL: 70, XXL: 80 }; if (alphaMap[normalized]) return alphaMap[normalized]; if (/^\d+$/.test(normalized)) return 100 + Number(normalized); return 999; }
function sortOptions(resource: InlineCatalogKey, items: SuperadminFormFieldOption[]) { const unique = Array.from(new Map(items.map((item) => [item.value, item])).values()); if (resource === 'sizes') return unique.sort((left, right) => suggestSizeOrder(left.label) - suggestSizeOrder(right.label)); return unique.sort((left, right) => left.label.localeCompare(right.label, 'es', { sensitivity: 'base' })); }
function prioritizeOptions(options: SuperadminFormFieldOption[], preferredValues: string[]) { const order = preferredValues.filter(Boolean); if (order.length === 0) return options; return [...options].sort((left, right) => { const leftRank = order.indexOf(left.value); const rightRank = order.indexOf(right.value); if (leftRank === -1 && rightRank === -1) return 0; if (leftRank === -1) return 1; if (rightRank === -1) return -1; return leftRank - rightRank; }); }
function defaultColorFamily(seed: string) { const normalized = normalizeText(seed); if (normalized.includes('ROSA')) return 'ROSA'; if (normalized.includes('AZUL')) return 'AZUL'; if (normalized.includes('VERDE')) return 'VERDE'; if (normalized.includes('BEIGE')) return 'BEIGE'; if (normalized.includes('CAMEL')) return 'CAMEL'; if (normalized.includes('CREMA') || normalized.includes('BLANCO')) return 'BLANCO'; if (normalized.includes('BORGONA') || normalized.includes('ROJO')) return 'ROJO'; if (normalized.includes('MARRON') || normalized.includes('CAFE')) return 'MARRON'; if (normalized.includes('NEGRO')) return 'NEGRO'; if (normalized.includes('GRIS')) return 'GRIS'; return normalized.split(' ')[0] || 'NEUTRO'; }
function inlineDefaults(resource: InlineCatalogKey, seed: string): InlineForm { return { name: seed.trim().toUpperCase(), family: resource === 'colors' ? defaultColorFamily(seed) : '', hex: '#D8C3A5', order: resource === 'sizes' && seed ? String(suggestSizeOrder(seed)) : '' }; }
function valuesHasFiles(values: ProductEditorForm) { return values.imagen_principal instanceof File || values.gallery_images.some((image) => image.archivo instanceof File); }
function normalizeHex(value: string) { const hex = value.trim().replace('#', '').toUpperCase(); return /^([A-F0-9]{6})$/.test(hex) ? `#${hex}` : null; }
function hexToRgb(value: string) { const hex = normalizeHex(value); if (!hex) return null; return { r: Number.parseInt(hex.slice(1, 3), 16), g: Number.parseInt(hex.slice(3, 5), 16), b: Number.parseInt(hex.slice(5, 7), 16) }; }
function rgbToHex(r: number, g: number, b: number) { return `#${[r, g, b].map((value) => Math.max(0, Math.min(255, Math.round(value))).toString(16).padStart(2, '0')).join('').toUpperCase()}`; }
function colorDistance(left: string, right: string) { const a = hexToRgb(left); const b = hexToRgb(right); if (!a || !b) return Number.MAX_SAFE_INTEGER; return Math.sqrt(((a.r - b.r) ** 2) + ((a.g - b.g) ** 2) + ((a.b - b.b) ** 2)); }
const familyPalette: Record<string, string> = { NEGRO: '#1A1A1A', BLANCO: '#F4F1EC', CREMA: '#EEE0CF', BEIGE: '#D7C1A3', CAMEL: '#C69C6D', GRIS: '#8C8A8A', AZUL: '#2E4A6B', VERDE: '#576B4D', MARRON: '#6B4A35', ROJO: '#6E1E2A', ROSA: '#C894A2' };
function inferColorFamily(hex: string, families: SuperadminFormFieldOption[]) { const bestFamily = Object.entries(familyPalette).map(([label, swatch]) => ({ label, distance: colorDistance(hex, swatch) })).sort((left, right) => left.distance - right.distance)[0]?.label; if (!bestFamily) return null; return families.find((option) => normalizeText(option.label) === bestFamily || normalizeText(option.value) === bestFamily) ?? families.find((option) => normalizeText(option.label).includes(bestFamily)) ?? { label: bestFamily, value: bestFamily }; }
function closestColorOption(colors: SuperadminFormFieldOption[], hex: string, familyLabel?: string) { const family = normalizeText(familyLabel ?? ''); return [...colors].filter((option) => Boolean(option.swatch)).map((option) => ({ option, familyBoost: family && normalizeText(option.label).includes(family) ? -120 : 0, distance: colorDistance(option.swatch ?? '#000000', hex) })).sort((left, right) => (left.distance + left.familyBoost) - (right.distance + right.familyBoost))[0]?.option ?? null; }
function variantIssue(row?: ProductVariantRow | null) {
    if (!row || row.remove) return 'Completa la configuración de la variante.';
    if (!row.cod_colores_prv || !row.cod_tallas_prv || !row.ubi_inv) return 'Completa color, talla y ubicación.';

    const price = Number(row.pve_prv);
    const stock = Number(row.stock_inicial);
    const cost = row.cos_prv === '' ? null : Number(row.cos_prv);

    if (!Number.isFinite(price) || price <= 0) return 'El precio de venta debe ser mayor a 0.';
    if (!Number.isInteger(stock) || stock < 1) return 'La cantidad inicial debe ser un número entero mayor a 0.';
    if (cost !== null && (!Number.isFinite(cost) || cost < 0)) return 'El costo interno no puede ser negativo.';
    if (cost !== null && cost > price) return 'El costo interno no puede ser mayor al precio de venta.';

    return null;
}
function variantIsComplete(row?: ProductVariantRow | null) { return !variantIssue(row); }
function filenameLabel(path: string) { return path.split('/').pop() ?? path; }

export default function ProductCreate({ meta, breadcrumbs, mode, recordLabel, submit, stateAction, editor, options, initialData }: ProductCreateProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const isEdit = mode === 'edit';
    const displayTitle = isEdit ? recordLabel ?? editor.title : editor.title;
    const [optionSets, setOptionSets] = useState({ categories: options.categories, brands: options.brands, seasons: options.seasons, genders: options.genders, colors: options.colors, sizes: options.sizes, locations: options.locations });
    const [categoryProfiles, setCategoryProfiles] = useState(options.categoryProfiles);
    const [colorFamilyOptions, setColorFamilyOptions] = useState(options.colorFamilies);
    const [primaryPreview, setPrimaryPreview] = useState<string | null>(editor.currentPrimaryUrl ?? null);
    const [currentStep, setCurrentStep] = useState<ProductStepId>('base');
    const [stepError, setStepError] = useState<string | null>(null);
    const [showInternalFields, setShowInternalFields] = useState(false);
    const [inlineDrawer, setInlineDrawer] = useState<InlineDrawerState | null>(null);
    const [inlineForm, setInlineForm] = useState<InlineForm>(inlineDefaults('categories', ''));
    const [inlineErrors, setInlineErrors] = useState<Record<string, string>>({});
    const [inlineMessage, setInlineMessage] = useState<string | null>(null);
    const [inlineProcessing, setInlineProcessing] = useState(false);
    const [colorInsight, setColorInsight] = useState<ColorInsight | null>(null);
    const [pickedColorSample, setPickedColorSample] = useState<PickedColorSample | null>(null);
    const galleryPreviewUrls = useRef<Record<number, string>>({});
    const { data, setData, post, put, transform, processing, errors } = useForm<ProductEditorForm>(initialData);

    const steps = useMemo<ProductStep[]>(() => {
        const base: ProductStep[] = [
            { id: 'base', number: 1, title: 'Datos base', description: 'Nombre, categoría, marca, temporada y género.' },
            { id: 'image', number: 2, title: 'Imagen principal', description: 'Portada principal de la prenda.' },
            { id: 'type', number: 3, title: 'Tipo de prenda', description: 'Define si será única o tendrá más tallas o colores.' },
            { id: 'commercial', number: 4, title: 'Configuración comercial', description: data.usa_variantes ? 'Preparación antes de cargar variantes.' : 'Color, talla, precio y existencias base.' },
            { id: 'review', number: 6, title: 'Revisión y guardado', description: 'Confirma la ficha, la galería y el cierre del alta.' },
        ];

        if (data.usa_variantes) {
            base.splice(4, 0, { id: 'variants', number: 5, title: 'Variantes', description: 'Carga cada combinación con precio y existencias.' });
        }

        return base;
    }, [data.usa_variantes]);
    const selectedCategory = useMemo(() => optionSets.categories.find((option) => option.value === data.cod_categorias_pre)?.label ?? '', [data.cod_categorias_pre, optionSets.categories]);
    const selectedSeason = useMemo(() => optionSets.seasons.find((option) => option.value === data.cod_temporadas_pre)?.label ?? '', [data.cod_temporadas_pre, optionSets.seasons]);
    const selectedCategoryProfile = useMemo(() => categoryProfiles[data.cod_categorias_pre] ?? inferCategoryProfile(selectedCategory), [categoryProfiles, data.cod_categorias_pre, selectedCategory]);
    const sizeProfile = useMemo(() => inferSizeProfile(data.nom_pre, selectedCategory, selectedCategoryProfile), [data.nom_pre, selectedCategory, selectedCategoryProfile]);
    const previewBaseCode = useMemo(() => previewCodePrefix(selectedCategory || data.nom_pre || 'PRENDA'), [data.nom_pre, selectedCategory]);
    const activeVariantRows = useMemo(() => data.variant_rows.filter((row) => !row.remove), [data.variant_rows]);
    const firstActiveVariantIndex = useMemo(() => data.variant_rows.findIndex((row) => !row.remove), [data.variant_rows]);
    const resolvedBasePrice = useMemo(() => { const prices = activeVariantRows.map((row) => Number(row.pve_prv || 0)).filter((value) => Number.isFinite(value) && value > 0); if (prices.length > 0) return String(prices[0]); return data.prb_pre || '0'; }, [activeVariantRows, data.prb_pre]);
    const availableSuggestedSizes = useMemo(() => sizeProfile.values.map((sizeName) => optionSets.sizes.find((option) => normalizeText(option.label) === normalizeText(sizeName))).filter((option): option is SuperadminFormFieldOption => Boolean(option)), [optionSets.sizes, sizeProfile.values]);
    const missingSuggestedSizes = useMemo(() => sizeProfile.values.filter((sizeName) => !optionSets.sizes.some((option) => normalizeText(option.label) === normalizeText(sizeName))), [optionSets.sizes, sizeProfile.values]);
    const orderedSizeOptions = useMemo(() => prioritizeOptions(sortOptions('sizes', optionSets.sizes), availableSuggestedSizes.map((option) => option.value)), [availableSuggestedSizes, optionSets.sizes]);
    const orderedColorOptions = useMemo(() => prioritizeOptions(sortOptions('colors', optionSets.colors), colorInsight?.suggestedColor ? [colorInsight.suggestedColor.value] : []), [colorInsight, optionSets.colors]);
    const hasPrimaryImage = useMemo(() => Boolean(data.imagen_principal || data.existing_images.some((image) => !image.remove && image.pri_igp) || primaryPreview), [data.existing_images, data.imagen_principal, primaryPreview]);
    const currentPrimaryLabel = useMemo(() => { if (data.imagen_principal) return data.imagen_principal.name; const existingPrimary = data.existing_images.find((image) => !image.remove && image.pri_igp); if (existingPrimary) return filenameLabel(existingPrimary.rut_igp); return editor.currentPrimaryLabel ?? null; }, [data.existing_images, data.imagen_principal, editor.currentPrimaryLabel]);
    const galleryCount = useMemo(() => data.existing_images.filter((image) => !image.remove).length + data.gallery_images.filter((image) => image.archivo).length + (data.imagen_principal ? 1 : 0), [data.existing_images, data.gallery_images, data.imagen_principal]);
    const summary = useMemo(() => ({ variants: activeVariantRows.length, stock: activeVariantRows.reduce((total, row) => total + (Number(row.stock_inicial) || 0), 0), price: resolvedBasePrice, images: galleryCount }), [activeVariantRows, galleryCount, resolvedBasePrice]);
    const colorHint = colorInsight ? `Dominante sugerido: ${colorInsight.family?.label ?? 'Sin familia'} · tono sugerido: ${colorInsight.suggestedColor?.label ?? 'Revisar manualmente'}` : undefined;
    const selectedCommercialColor = useMemo(() => optionSets.colors.find((option) => option.value === activeVariantRows[0]?.cod_colores_prv) ?? null, [activeVariantRows, optionSets.colors]);
    const isSuggestedColorApplied = Boolean(colorInsight?.suggestedColor && selectedCommercialColor?.value === colorInsight.suggestedColor.value);
    const inlineTitle = inlineDrawer ? { categories: 'Otra categoría', brands: 'Otra marca', colors: 'Otro color', sizes: 'Otra talla', seasons: 'Otra temporada' }[inlineDrawer.resource] : '';
    const inlineDescription = inlineDrawer ? { categories: 'Registra la categoría y vuelve al formulario con esa opción seleccionada.', brands: 'Registra la marca y vuelve a la ficha de la prenda.', colors: 'Define el tono real y la familia dominante para usarlo de inmediato.', sizes: 'Agrega la talla sin salir del registro de la prenda.', seasons: 'Registra una temporada nueva solo si la prenda no corresponde a las ya disponibles.' }[inlineDrawer.resource] : '';

    useEffect(() => { setData(initialData); setPrimaryPreview(editor.currentPrimaryUrl ?? null); }, [editor.currentPrimaryUrl, initialData, setData]);
    useEffect(() => () => { Object.values(galleryPreviewUrls.current).forEach((url) => URL.revokeObjectURL(url)); }, []);
    useEffect(() => { if (!data.imagen_principal) { const currentPrimary = data.existing_images.find((image) => !image.remove && image.pri_igp); setPrimaryPreview(currentPrimary?.url_igp ?? editor.currentPrimaryUrl ?? null); return; } const objectUrl = URL.createObjectURL(data.imagen_principal); setPrimaryPreview(objectUrl); return () => URL.revokeObjectURL(objectUrl); }, [data.existing_images, data.imagen_principal, editor.currentPrimaryUrl]);
    useEffect(() => { if (mode !== 'create' || data.cod_temporadas_pre) return; const permanentSeason = optionSets.seasons.find((option) => normalizeText(option.label) === 'PERMANENTE'); if (permanentSeason) setData('cod_temporadas_pre', permanentSeason.value); }, [data.cod_temporadas_pre, mode, optionSets.seasons, setData]);
    useEffect(() => { if (sizeProfile.values.length !== 1) return; const firstIndex = data.variant_rows.findIndex((row) => !row.remove); if (firstIndex === -1 || data.variant_rows[firstIndex].cod_tallas_prv) return; const existingOption = optionSets.sizes.find((option) => normalizeText(option.label) === normalizeText(sizeProfile.values[0])); if (!existingOption) return; setData('variant_rows', data.variant_rows.map((row, rowIndex) => rowIndex === firstIndex ? { ...row, cod_tallas_prv: existingOption.value } : row)); }, [data.variant_rows, optionSets.sizes, setData, sizeProfile.values]);
    useEffect(() => { setPickedColorSample(null); setColorInsight(null); }, [primaryPreview]);
    useEffect(() => { if (steps.some((step) => step.id === currentStep)) return; setCurrentStep(steps[steps.length - 1]?.id ?? 'base'); }, [currentStep, steps]);
    useEffect(() => { setStepError(null); }, [currentStep, data.cod_categorias_pre, data.cod_marcas_pre, data.cod_temporadas_pre, data.gen_pre, data.imagen_principal, data.nom_pre, data.usa_variantes, data.variant_rows]);

    const updateVariantRow = (index: number, field: keyof ProductVariantRow, value: string | boolean) => {
        const normalizedValue =
            typeof value === 'string' && field === 'sku_prv'
                ? normalizeText(value).replace(/[^A-Z0-9_-]/g, '')
                : typeof value === 'string' && field === 'cob_prv'
                ? normalizeText(value).replace(/[^A-Z0-9-]/g, '')
                : value;

        setData('variant_rows', data.variant_rows.map((row, rowIndex) => rowIndex === index ? { ...row, [field]: normalizedValue } : row));
    };
    const addVariantRow = () => { const seed = activeVariantRows[0] ?? createVariantRow(summary.price); setData('variant_rows', [...data.variant_rows, createVariantRow(seed.pve_prv || summary.price, seed.ubi_inv || 'TIENDA')]); };
    const removeVariantRow = (index: number) => { const row = data.variant_rows[index]; if (!row) return; if (row.id) { updateVariantRow(index, 'remove', true); return; } if (activeVariantRows.length <= 1) return; setData('variant_rows', data.variant_rows.filter((_, rowIndex) => rowIndex !== index)); };
    const restoreVariantRow = (index: number) => updateVariantRow(index, 'remove', false);
    const toggleVariants = (enabled: boolean) => { if (enabled) { setData('usa_variantes', true); return; } let keptOne = false; const nextRows = data.variant_rows.map((row) => { if (row.remove) return row; if (!keptOne) { keptOne = true; return row; } return row.id ? { ...row, remove: true } : null; }).filter((row): row is ProductVariantRow => Boolean(row)); setData((current) => ({ ...current, usa_variantes: false, variant_rows: nextRows.length > 0 ? nextRows : [createVariantRow(summary.price)] })); };
    const applySuggestedSizes = () => { if (availableSuggestedSizes.length === 0) return; const seed = activeVariantRows[0] ?? createVariantRow(summary.price); const preservedRows = data.variant_rows.filter((row) => row.id && row.remove); const suggestedRows = availableSuggestedSizes.map((option, index) => ({ ...createVariantRow(seed.pve_prv || summary.price, seed.ubi_inv || 'TIENDA'), cod_colores_prv: seed.cod_colores_prv, cod_tallas_prv: option.value, cob_prv: index === 0 ? seed.cob_prv : '', pve_prv: seed.pve_prv || summary.price, cos_prv: seed.cos_prv, stock_inicial: index === 0 ? seed.stock_inicial || '1' : '1', est_prv: true, remove: false })); setData('variant_rows', [...suggestedRows, ...preservedRows]); };
    const nextImageOrder = () => String(Math.max(1, ...data.existing_images.filter((image) => !image.remove).map((image) => Number(image.ord_igp || 0) + 1), ...data.gallery_images.map((image) => Number(image.ord_igp || 0) + 1)));
    const addGalleryRow = () => setData('gallery_images', [...data.gallery_images, createGalleryRow(nextImageOrder())]);
    const updateGalleryRow = (index: number, field: keyof NewGalleryImageRow, value: string | boolean | File | null) => setData('gallery_images', data.gallery_images.map((row, rowIndex) => rowIndex === index ? { ...row, [field]: value } : row));
    const updateGalleryFile = (index: number, file: File | null) => { const currentUrl = galleryPreviewUrls.current[index]; if (currentUrl) { URL.revokeObjectURL(currentUrl); delete galleryPreviewUrls.current[index]; } if (file) galleryPreviewUrls.current[index] = URL.createObjectURL(file); updateGalleryRow(index, 'archivo', file); };
    const removeGalleryRow = (index: number) => { const currentUrl = galleryPreviewUrls.current[index]; if (currentUrl) { URL.revokeObjectURL(currentUrl); delete galleryPreviewUrls.current[index]; } galleryPreviewUrls.current = Object.fromEntries(Object.entries(galleryPreviewUrls.current).map(([key, url]) => { const numericKey = Number(key); if (numericKey > index) return [String(numericKey - 1), url]; return [key, url]; })); setData('gallery_images', data.gallery_images.filter((_, rowIndex) => rowIndex !== index)); };
    const rebalancePrimary = (images: ExistingImageRow[]) => { const activeImages = images.filter((image) => !image.remove); if (activeImages.length === 0 || activeImages.some((image) => image.pri_igp)) return images; const nextPrimaryId = activeImages[0].id; return images.map((image) => ({ ...image, pri_igp: !image.remove && image.id === nextPrimaryId })); };
    const setExistingPrimary = (index: number) => { setData('imagen_principal', null); setData('existing_images', data.existing_images.map((image, imageIndex) => ({ ...image, pri_igp: imageIndex === index && !image.remove }))); };
    const toggleExistingImageRemoval = (index: number) => { const nextImages = data.existing_images.map((image, imageIndex) => imageIndex === index ? { ...image, remove: !image.remove } : image); setData('existing_images', rebalancePrimary(nextImages)); };
    const openInlineCreate = (resource: InlineCatalogKey, target: InlineTarget, seed = '', overrides?: Partial<InlineForm>) => { setInlineDrawer({ resource, target, seed }); setInlineForm({ ...inlineDefaults(resource, seed), ...overrides }); setInlineErrors({}); setInlineMessage(null); };
    const closeInlineCreate = () => { setInlineDrawer(null); setInlineErrors({}); setInlineMessage(null); };
    const handleColorSamplePick = (sample: PickedColorSample) => { const family = inferColorFamily(sample.hex, colorFamilyOptions); const suggestedColor = closestColorOption(optionSets.colors, sample.hex, family?.label); setPickedColorSample(sample); setColorInsight({ hex: sample.hex, family, suggestedColor }); };
    const applySuggestedColor = () => { if (!colorInsight?.suggestedColor || firstActiveVariantIndex === -1) return; updateVariantRow(firstActiveVariantIndex, 'cod_colores_prv', colorInsight.suggestedColor.value); };
    const createColorFromSample = () => { if (firstActiveVariantIndex === -1 || !colorInsight) return; openInlineCreate('colors', { type: 'variant', field: 'cod_colores_prv', rowIndex: firstActiveVariantIndex }, colorInsight.suggestedColor?.label ?? colorInsight.family?.label ?? '', { name: colorInsight.suggestedColor?.label ?? '', family: colorInsight.family?.value ?? defaultColorFamily(colorInsight.suggestedColor?.label ?? colorInsight.hex), hex: colorInsight.hex }); };
    const issueForStep = (stepId: ProductStepId) => {
        const firstRow = activeVariantRows[0] ?? null;

        if (stepId === 'base') {
            const missing = [];
            if (!data.nom_pre.trim()) missing.push('nombre');
            if (!data.cod_categorias_pre) missing.push('categoría');
            if (!data.cod_marcas_pre) missing.push('marca');
            if (!data.cod_temporadas_pre) missing.push('temporada');
            if (!data.gen_pre) missing.push('género');

            return missing.length > 0 ? `Completa ${missing.join(', ')} para continuar.` : null;
        }

        if (stepId === 'image') return hasPrimaryImage ? null : 'Carga la imagen principal para continuar.';

        if (stepId === 'commercial') {
            if (!firstRow) return 'Prepara al menos una configuración comercial para la prenda.';
            if (!data.usa_variantes) return variantIssue(firstRow);
            return null;
        }

        if (stepId === 'variants' && data.usa_variantes) {
            if (activeVariantRows.length < 2) return 'Agrega al menos dos configuraciones para una prenda con más tallas o colores.';

            const combinations = activeVariantRows
                .map((row) => `${row.cod_colores_prv}:${row.cod_tallas_prv}`)
                .filter((value) => value !== ':');

            if (new Set(combinations).size !== combinations.length) {
                return 'No repitas la misma combinación de color y talla.';
            }

            const firstIssue = activeVariantRows
                .map((row, index) => ({ issue: variantIssue(row), index }))
                .find((entry) => entry.issue);

            if (firstIssue) return `Configuración ${firstIssue.index + 1}: ${firstIssue.issue}`;
        }

        return null;
    };
    const validateBeforeSubmit = () => { for (const step of steps) { const issue = issueForStep(step.id); if (issue) return { stepId: step.id, message: issue }; } return null; };
    const goToStep = (stepId: ProductStepId) => { setCurrentStep(stepId); setStepError(null); };
    const goNext = () => { const issue = issueForStep(currentStep); if (issue) { setStepError(issue); return; } const currentIndex = steps.findIndex((step) => step.id === currentStep); const nextStep = steps[currentIndex + 1]; if (nextStep) setCurrentStep(nextStep.id); };
    const goBack = () => { const currentIndex = steps.findIndex((step) => step.id === currentStep); const previousStep = steps[currentIndex - 1]; if (previousStep) setCurrentStep(previousStep.id); };
    const submitForm = (overrides: Partial<ProductEditorForm> = {}) => { const nextValues = { ...data, ...overrides }; const hasFiles = valuesHasFiles(nextValues); transform(() => ({ ...nextValues, prb_pre: resolvedBasePrice, gallery_images: nextValues.gallery_images.map((image) => ({ ...image, pri_igp: false })), ...(submit.method === 'put' && hasFiles ? { _method: 'put' } : {}) })); const requestOptions = { preserveScroll: true, forceFormData: hasFiles }; if (submit.method === 'put' && !hasFiles) { put(submit.action, requestOptions); return; } if (submit.method === 'put' && hasFiles) { post(submit.action, requestOptions); return; } post(submit.action, requestOptions); };
    const handleSubmit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); const issue = validateBeforeSubmit(); if (issue) { setCurrentStep(issue.stepId); setStepError(issue.message); return; } submitForm(); };
    const handleStateAction = () => { if (!stateAction) return; if (stateAction.confirm && !window.confirm(stateAction.confirm)) return; submitForm({ est_pre: stateAction.value === '1' }); };
    const completedSteps: Record<ProductStepId, boolean> = { base: !issueForStep('base'), image: !issueForStep('image'), type: true, commercial: !issueForStep('commercial'), variants: !data.usa_variantes || !issueForStep('variants'), review: false };
    const serverErrorCount = Object.keys(errors).length;
    const submitInlineCreate = async (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); if (!inlineDrawer) return; const resourcePath = { categories: 'categorias', brands: 'marcas', colors: 'colores', sizes: 'tallas', seasons: 'temporadas' }[inlineDrawer.resource]; const payload = inlineDrawer.resource === 'categories' ? { nom_cat: inlineForm.name, est_cat: true } : inlineDrawer.resource === 'brands' ? { nom_mar: inlineForm.name, est_mar: true } : inlineDrawer.resource === 'colors' ? { nom_col: inlineForm.name, fam_col: inlineForm.family || defaultColorFamily(inlineForm.name), hex_col: inlineForm.hex, hex_dom_col: inlineForm.hex, est_col: true } : inlineDrawer.resource === 'sizes' ? { nom_tal: inlineForm.name, ord_tal: inlineForm.order || String(suggestSizeOrder(inlineForm.name)), est_tal: true } : { nom_tem: inlineForm.name, est_tem: true }; setInlineProcessing(true); setInlineErrors({}); setInlineMessage(null); try { const response = await axios.post(`/superadmin/catalogo/${resourcePath}/inline`, payload); const option = response.data.option as SuperadminFormFieldOption; if (inlineDrawer.resource === 'categories') { setOptionSets((current) => ({ ...current, categories: sortOptions('categories', [...current.categories, option]) })); setCategoryProfiles((current) => ({ ...current, [option.value]: inferCategoryProfile(option.label) })); } if (inlineDrawer.resource === 'brands') setOptionSets((current) => ({ ...current, brands: sortOptions('brands', [...current.brands, option]) })); if (inlineDrawer.resource === 'colors') { setOptionSets((current) => ({ ...current, colors: sortOptions('colors', [...current.colors, option]) })); const familyValue = inlineForm.family || defaultColorFamily(option.label); if (!colorFamilyOptions.some((family) => family.value === familyValue)) setColorFamilyOptions((current) => sortOptions('colors', [...current, { label: familyValue, value: familyValue }])); } if (inlineDrawer.resource === 'sizes') setOptionSets((current) => ({ ...current, sizes: sortOptions('sizes', [...current.sizes, option]) })); if (inlineDrawer.resource === 'seasons') setOptionSets((current) => ({ ...current, seasons: sortOptions('seasons', [...current.seasons, option]) })); if (inlineDrawer.target.type === 'product') setData(inlineDrawer.target.field, option.value); else updateVariantRow(inlineDrawer.target.rowIndex, inlineDrawer.target.field, option.value); setInlineMessage(response.data.message ?? 'Registro creado correctamente.'); closeInlineCreate(); } catch (error) { if (axios.isAxiosError(error) && error.response?.status === 422) { setInlineErrors((error.response.data.errors ?? {}) as Record<string, string>); return; } setInlineMessage('No se pudo crear el registro en este momento.'); } finally { setInlineProcessing(false); } };

    return (
        <SuperadminLayout title={`Mati Boutique | ${displayTitle}`}>
            <div className="space-y-8">
                <SuperadminPageHeader eyebrow={meta.sectionLabel} title={displayTitle} description={editor.description} breadcrumbs={breadcrumbs} actions={[{ label: 'Volver al módulo', href: meta.indexHref, kind: 'ghost' }]} />
                <SuperadminFlashNotice success={flash?.success} error={flash?.error ?? inlineMessage} />
                {serverErrorCount > 0 && (
                    <section className="rounded-[1.4rem] border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] p-4">
                        <p className="text-sm font-medium text-[rgb(245,208,210)]">
                            Revisa {serverErrorCount === 1 ? 'el campo marcado' : 'los campos marcados'} antes de guardar la prenda.
                        </p>
                    </section>
                )}
                <div className="grid gap-3 xl:grid-cols-6">
                    {steps.map((step) => {
                        const active = currentStep === step.id;
                        const complete = completedSteps[step.id];
                        return (
                            <button key={step.id} type="button" onClick={() => goToStep(step.id)} className={`rounded-[1.35rem] border px-4 py-4 text-left transition ${active ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)]' : 'border-white/8 bg-[#0d141d] hover:border-white/14'}`}>
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-[0.66rem] uppercase tracking-[0.18em] text-stone-400">Paso {step.number}</span>
                                    {complete && <span className="inline-flex h-6 w-6 items-center justify-center rounded-full border border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.18)] text-white"><Check size={12} /></span>}
                                </div>
                                <p className="mt-3 text-sm text-white">{step.title}</p>
                                <p className="mt-2 text-sm leading-6 text-stone-400">{step.description}</p>
                            </button>
                        );
                    })}
                </div>
                {stepError && <div className="rounded-[1.3rem] border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.1)] px-4 py-3 text-sm text-[rgb(245,208,210)]">{stepError}</div>}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {currentStep === 'base' && (
                        <section className="sa-panel rounded-[1.6rem] p-5">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 1</p>
                                    <h2 className="mt-2 text-lg text-white">Datos base de la prenda</h2>
                                    <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">Registra la ficha principal para que el resto del flujo se adapte a la prenda.</p>
                                </div>
                                <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] px-4 py-3">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Código interno</p>
                                    <p className="mt-2 text-sm text-white">{previewBaseCode}</p>
                                    <p className="mt-2 text-xs leading-5 text-stone-400">Se genera automáticamente según la categoría.</p>
                                </div>
                            </div>

                            <div className="mt-6 grid gap-4 md:grid-cols-2">
                                <label className="space-y-2 md:col-span-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Nombre de la prenda</span>
                                    <input value={data.nom_pre} onChange={(event) => setData('nom_pre', event.target.value.toUpperCase())} className="sa-input w-full" placeholder="NOMBRE DE LA PRENDA" maxLength={150} />
                                    <p className="text-sm text-stone-400">Se guarda en MAYÚSCULAS. Puedes usar letras, números y guiones si el modelo lo necesita.</p>
                                    {errors.nom_pre && <p className="text-sm text-rose-300">{errors.nom_pre}</p>}
                                </label>

                                <label className="space-y-2 md:col-span-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Descripción</span>
                                    <textarea value={data.des_pre} onChange={(event) => setData('des_pre', event.target.value)} className="sa-input min-h-28 w-full resize-none" placeholder="Descripción breve opcional." />
                                    {errors.des_pre && <p className="text-sm text-rose-300">{errors.des_pre}</p>}
                                </label>

                                <SuperadminComboboxField
                                    label="Categoría"
                                    value={data.cod_categorias_pre}
                                    options={optionSets.categories}
                                    placeholder="Selecciona una categoría"
                                    searchPlaceholder="Buscar categoría"
                                    error={errors.cod_categorias_pre}
                                    createLabel="OTRA CATEGORÍA"
                                    createMode="list"
                                    onChange={(value) => setData('cod_categorias_pre', value)}
                                    onCreate={(seed) => openInlineCreate('categories', { type: 'product', field: 'cod_categorias_pre' }, seed)}
                                />

                                <SuperadminComboboxField
                                    label="Marca"
                                    value={data.cod_marcas_pre}
                                    options={optionSets.brands}
                                    placeholder="Selecciona una marca"
                                    searchPlaceholder="Buscar marca"
                                    error={errors.cod_marcas_pre}
                                    createLabel="OTRA MARCA"
                                    createMode="list"
                                    onChange={(value) => setData('cod_marcas_pre', value)}
                                    onCreate={(seed) => openInlineCreate('brands', { type: 'product', field: 'cod_marcas_pre' }, seed)}
                                />

                                <div className="space-y-2">
                                    <SuperadminComboboxField
                                        label="Temporada"
                                        value={data.cod_temporadas_pre}
                                        options={optionSets.seasons}
                                        placeholder="Selecciona una temporada"
                                        searchPlaceholder="Buscar temporada"
                                        error={errors.cod_temporadas_pre}
                                        createLabel="OTRA TEMPORADA"
                                        createMode="list"
                                        onChange={(value) => setData('cod_temporadas_pre', value)}
                                        onCreate={(seed) => openInlineCreate('seasons', { type: 'product', field: 'cod_temporadas_pre' }, seed)}
                                    />
                                    <p className="text-sm leading-6 text-stone-400">PERMANENTE se usa para prendas de linea continua o casual. Si la prenda pertenece a una coleccion especifica, puedes cambiarla.</p>
                                </div>

                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Género</span>
                                    <select value={data.gen_pre} onChange={(event) => setData('gen_pre', event.target.value)} className="sa-input w-full">
                                        <option value="">Selecciona género</option>
                                        {optionSets.genders.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                    </select>
                                    {errors.gen_pre && <p className="text-sm text-rose-300">{errors.gen_pre}</p>}
                                </label>

                                <label className="space-y-3 md:col-span-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Estado</span>
                                    <button type="button" onClick={() => setData('est_pre', !data.est_pre)} className={`flex w-full items-center justify-between rounded-[1.1rem] border px-4 py-3 text-left transition ${data.est_pre ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)] text-white' : 'border-white/10 bg-[#0d141d] text-stone-300'}`}>
                                        <span className="text-sm">{data.est_pre ? 'La prenda quedará visible en catálogo.' : 'La prenda quedará oculta hasta activarla.'}</span>
                                        <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">{data.est_pre ? 'Visible' : 'Oculta'}</span>
                                    </button>
                                </label>
                            </div>

                            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
                                <Link href={meta.indexHref} className="sa-button-ghost px-5 py-3 text-center text-[0.72rem] uppercase tracking-[0.18em]">Cancelar</Link>
                                <button type="button" onClick={goNext} className="sa-button-primary inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    Continuar con imagen principal
                                    <ArrowRight size={14} />
                                </button>
                            </div>
                        </section>
                    )}

                    {currentStep === 'image' && (
                        <section className="sa-panel rounded-[1.6rem] p-5">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 2</p>
                                    <h2 className="mt-2 text-lg text-white">Imagen principal</h2>
                                    <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">La portada se usará como imagen base del producto en catálogo y detalle.</p>
                                </div>
                                <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] px-4 py-3">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Estado de portada</p>
                                    <p className="mt-2 text-sm text-white">{currentPrimaryLabel ?? 'Pendiente de cargar'}</p>
                                </div>
                            </div>

                            <div className="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)]">
                                <div className="rounded-[1.45rem] border border-white/8 bg-[#0d141d] p-4">
                                    <ProductColorPicker
                                        src={primaryPreview}
                                        selectedSample={pickedColorSample}
                                        onPick={handleColorSamplePick}
                                    />
                                    <div className="mt-4 space-y-2">
                                        <p className="text-sm text-white">{data.imagen_principal ? 'Nueva portada lista para guardar.' : currentPrimaryLabel ?? 'Todavia no hay portada seleccionada.'}</p>
                                        <p className="text-sm leading-6 text-stone-400">Puede reutilizarse para tallas o variantes y quedara como imagen principal de la prenda.</p>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <label className="block space-y-3">
                                        <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Cargar imagen principal</span>
                                        <div className="rounded-[1.4rem] border border-dashed border-white/14 bg-[#0d141d] p-5">
                                            <div className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-stone-300"><ImagePlus size={18} /></div>
                                            <p className="mt-4 text-sm text-white">La portada principal se registra en este paso.</p>
                                            <p className="mt-2 text-sm leading-6 text-stone-400">Si reemplazas la imagen, la nueva portada quedara lista al guardar la ficha.</p>
                                            <input type="file" accept="image/*" onChange={(event) => setData('imagen_principal', event.target.files?.[0] ?? null)} className="sa-input mt-4 w-full file:mr-4 file:rounded-full file:border-0 file:bg-white/8 file:px-4 file:py-2 file:text-xs file:uppercase file:tracking-[0.18em] file:text-stone-200" />
                                            {data.imagen_principal && <button type="button" onClick={() => setData('imagen_principal', null)} className="sa-button-ghost mt-3 px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">Quitar nueva portada</button>}
                                        </div>
                                        {errors.imagen_principal && <p className="text-sm text-rose-300">{errors.imagen_principal}</p>}
                                    </label>

                                    <div className="rounded-[1.4rem] border border-white/8 bg-[#0d141d] p-4">
                                        <div className="inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400"><Palette size={12} />Lectura de color desde la imagen</div>
                                        {colorInsight ? (
                                            <div className="mt-4 space-y-3">
                                                <div className="flex items-center gap-3">
                                                    <span className="inline-flex h-8 w-8 rounded-full border border-white/12" style={{ backgroundColor: colorInsight.hex }} />
                                                    <div>
                                                        <p className="text-sm text-white">Familia sugerida: {colorInsight.family?.label ?? 'Sin familia'}</p>
                                                        <p className="text-sm text-stone-400">Tono sugerido: {colorInsight.suggestedColor?.label ?? 'No hay uno parecido todavía'}</p>
                                                    </div>
                                                </div>
                                                <div className="rounded-[1.1rem] border border-white/8 bg-[#0a1016] p-3">
                                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Hex detectado</p>
                                                    <p className="mt-2 text-sm text-white">{colorInsight.hex}</p>
                                                    <p className="mt-2 text-sm leading-6 text-stone-400">Esta muestra no se guarda sola. Primero debes confirmarla o corregirla.</p>
                                                </div>
                                                <div className="flex flex-col gap-3">
                                                    {colorInsight.suggestedColor && (
                                                        <button type="button" onClick={applySuggestedColor} className="sa-button-secondary px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                                            {isSuggestedColorApplied ? 'Tono sugerido aplicado' : 'Usar tono sugerido en la prenda'}
                                                        </button>
                                                    )}
                                                    <button type="button" onClick={createColorFromSample} className="sa-button-ghost px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                                        Crear color desde esta muestra
                                                    </button>
                                                </div>
                                                {selectedCommercialColor && (
                                                    <div className="rounded-[1.1rem] border border-white/8 bg-[#0a1016] p-3">
                                                        <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Color actual en la configuración</p>
                                                        <div className="mt-2 flex items-center gap-3">
                                                            {selectedCommercialColor.swatch && (
                                                                <span className="inline-flex h-5 w-5 rounded-full border border-white/12" style={{ backgroundColor: selectedCommercialColor.swatch }} />
                                                            )}
                                                            <p className="text-sm text-white">{selectedCommercialColor.label}</p>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        ) : (
                                            <p className="mt-3 text-sm leading-6 text-stone-400">Haz clic sobre una zona representativa de la tela para obtener una sugerencia de familia, tono y hexadecimal antes de continuar.</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
                                <button type="button" onClick={goBack} className="sa-button-ghost inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    <ArrowLeft size={14} />
                                    Volver
                                </button>
                                <button type="button" onClick={goNext} className="sa-button-primary inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    Continuar con tipo de prenda
                                    <ArrowRight size={14} />
                                </button>
                            </div>
                        </section>
                    )}

                    {currentStep === 'type' && (
                        <section className="sa-panel rounded-[1.6rem] p-5">
                            <div>
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 3</p>
                                <h2 className="mt-2 text-lg text-white">Tipo de prenda</h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">Escoge el flujo que mejor se adapta a la forma real en que vendes esta prenda.</p>
                            </div>

                            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                                <button type="button" onClick={() => toggleVariants(false)} className={`rounded-[1.5rem] border p-5 text-left transition ${!data.usa_variantes ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)]' : 'border-white/8 bg-[#0d141d] hover:border-white/14'}`}>
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Opción 1</p>
                                    <h3 className="mt-3 text-base text-white">Prenda unica</h3>
                                    <p className="mt-3 text-sm leading-6 text-stone-300">Usala cuando la prenda tenga una sola combinacion operativa de color y talla.</p>
                                </button>

                                <button type="button" onClick={() => toggleVariants(true)} className={`rounded-[1.5rem] border p-5 text-left transition ${data.usa_variantes ? 'border-[rgba(126,154,178,0.28)] bg-[rgba(126,154,178,0.12)]' : 'border-white/8 bg-[#0d141d] hover:border-white/14'}`}>
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Opción 2</p>
                                    <h3 className="mt-3 text-base text-white">Prenda con mas tallas o colores</h3>
                        <p className="mt-3 text-sm leading-6 text-stone-300">Úsala cuando cada combinación necesite su propio color, talla, precio o existencias.</p>
                                </button>
                            </div>

                            <div className="mt-5 rounded-[1.4rem] border border-white/8 bg-[#0d141d] p-4">
                                <div className="inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400"><Sparkles size={13} />Perfil de tallas sugerido</div>
                                <p className="mt-3 text-sm text-white">{sizeProfile.label}</p>
                                <p className="mt-2 text-sm text-stone-400">{sizeProfile.values.join(', ')}</p>
                                {missingSuggestedSizes.length > 0 && <p className="mt-3 text-sm text-amber-200">Faltan tallas en catálogo: {missingSuggestedSizes.join(', ')}.</p>}
                            </div>

                            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
                                <button type="button" onClick={goBack} className="sa-button-ghost inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    <ArrowLeft size={14} />
                                    Volver
                                </button>
                                <button type="button" onClick={goNext} className="sa-button-primary inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    Continuar con configuración comercial
                                    <ArrowRight size={14} />
                                </button>
                            </div>
                        </section>
                    )}

                    {currentStep === 'commercial' && (
                        <section className="sa-panel rounded-[1.6rem] p-5">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 4</p>
                                    <h2 className="mt-2 text-lg text-white">Configuración comercial</h2>
                                    <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">{data.usa_variantes ? 'Prepara el criterio comercial antes de cargar cada variante.' : 'Define color, talla, precio, existencias y ubicación de la prenda.'}</p>
                                </div>
                                <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] px-4 py-3">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Código interno</p>
                                    <p className="mt-2 text-sm text-white">{previewVariantCode(previewBaseCode, 1, data.usa_variantes, activeVariantRows[0]?.sku_prv ?? '')}</p>
                                </div>
                            </div>

                            {!data.usa_variantes ? (
                                <div className="mt-6 rounded-[1.45rem] border border-white/8 bg-[#0d141d] p-5">
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <SuperadminComboboxField
                                            label="Color"
                                            value={activeVariantRows[0]?.cod_colores_prv ?? ''}
                                            options={orderedColorOptions}
                                            placeholder="Selecciona color"
                                            searchPlaceholder="Buscar color"
                                            error={errors['variant_rows.0.cod_colores_prv']}
                                            hint={colorHint}
                                            createLabel="OTRO COLOR"
                                            createMode="list"
                                            onChange={(value) => updateVariantRow(firstActiveVariantIndex, 'cod_colores_prv', value)}
                                            onCreate={(seed) => openInlineCreate('colors', { type: 'variant', field: 'cod_colores_prv', rowIndex: Math.max(firstActiveVariantIndex, 0) }, seed)}
                                        />

                                        <SuperadminComboboxField
                                            label="Talla"
                                            value={activeVariantRows[0]?.cod_tallas_prv ?? ''}
                                            options={orderedSizeOptions}
                                            placeholder="Selecciona talla"
                                            searchPlaceholder="Buscar talla"
                                            error={errors['variant_rows.0.cod_tallas_prv']}
                                            hint={`Sugerencia principal: ${sizeProfile.values.join(', ')}`}
                                            createLabel="OTRA TALLA"
                                            createMode="list"
                                            onChange={(value) => updateVariantRow(firstActiveVariantIndex, 'cod_tallas_prv', value)}
                                            onCreate={(seed) => openInlineCreate('sizes', { type: 'variant', field: 'cod_tallas_prv', rowIndex: Math.max(firstActiveVariantIndex, 0) }, seed)}
                                        />

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Precio de venta (Bs.)</span>
                                            <input type="number" min="0.01" step="0.01" value={activeVariantRows[0]?.pve_prv ?? ''} onChange={(event) => updateVariantRow(firstActiveVariantIndex, 'pve_prv', event.target.value)} className="sa-input w-full" placeholder="0.00" />
                                            {errors['variant_rows.0.pve_prv'] && <p className="text-sm text-rose-300">{errors['variant_rows.0.pve_prv']}</p>}
                                        </label>

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Cantidad inicial</span>
                                            <input type="number" min="1" step="1" value={activeVariantRows[0]?.stock_inicial ?? '1'} onChange={(event) => updateVariantRow(firstActiveVariantIndex, 'stock_inicial', event.target.value)} className="sa-input w-full" placeholder="1" />
                                            {errors['variant_rows.0.stock_inicial'] && <p className="text-sm text-rose-300">{errors['variant_rows.0.stock_inicial']}</p>}
                                        </label>

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Ubicación</span>
                                            <select value={activeVariantRows[0]?.ubi_inv ?? 'TIENDA'} onChange={(event) => updateVariantRow(firstActiveVariantIndex, 'ubi_inv', event.target.value)} className="sa-input w-full">
                                                {optionSets.locations.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                                            </select>
                                            {errors['variant_rows.0.ubi_inv'] && <p className="text-sm text-rose-300">{errors['variant_rows.0.ubi_inv']}</p>}
                                        </label>

                                        <label className="space-y-2">
                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Código interno</span>
                                            <input value={previewVariantCode(previewBaseCode, 1, false, activeVariantRows[0]?.sku_prv ?? '')} disabled className="sa-input w-full" />
                                        </label>
                                    </div>

                                    <div className="mt-5 rounded-[1.25rem] border border-white/8 bg-[#0b1118] p-4">
                                        <button type="button" onClick={() => setShowInternalFields((current) => !current)} className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-300 transition hover:text-white">
                                            {showInternalFields ? 'Ocultar datos internos opcionales' : 'Agregar costo o código adicional'}
                                        </button>
                                        {showInternalFields && (
                                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                                <label className="space-y-2">
                                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Costo interno (Bs.)</span>
                                                    <input type="number" min="0" step="0.01" value={activeVariantRows[0]?.cos_prv ?? ''} onChange={(event) => updateVariantRow(firstActiveVariantIndex, 'cos_prv', event.target.value)} className="sa-input w-full" placeholder="0.00" />
                                                    <p className="text-sm text-stone-400">Dato interno para margen y rentabilidad.</p>
                                                    {errors['variant_rows.0.cos_prv'] && <p className="text-sm text-rose-300">{errors['variant_rows.0.cos_prv']}</p>}
                                                </label>
                                                <label className="space-y-2">
                                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Código adicional (opcional)</span>
                                                    <input value={activeVariantRows[0]?.cob_prv ?? ''} onChange={(event) => updateVariantRow(firstActiveVariantIndex, 'cob_prv', event.target.value)} className="sa-input w-full" placeholder="Código adicional o referencia" />
                                                    {errors['variant_rows.0.cob_prv'] && <p className="text-sm text-rose-300">{errors['variant_rows.0.cob_prv']}</p>}
                                                </label>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <div className="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                                    <div className="rounded-[1.45rem] border border-white/8 bg-[#0d141d] p-5">
                                        <p className="text-sm text-white">La prenda usará configuraciones separadas.</p>
                                        <p className="mt-3 text-sm leading-6 text-stone-400">En el siguiente paso cargarás precio, existencias y ubicación para cada combinación de color y talla. El sistema ya prepara el código interno automático y el perfil de tallas sugerido.</p>
                                        <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                            <div className="rounded-[1.2rem] border border-white/8 bg-[#0b1118] p-4">
                                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Color sugerido</p>
                                                <p className="mt-2 text-sm text-white">{colorInsight?.suggestedColor?.label ?? 'Selección manual en el siguiente paso'}</p>
                                                <p className="mt-2 text-sm text-stone-400">{colorInsight?.family?.label ? `Familia dominante: ${colorInsight.family.label}` : 'La sugerencia aparecerá cuando cargues una portada.'}</p>
                                            </div>
                                            <div className="rounded-[1.2rem] border border-white/8 bg-[#0b1118] p-4">
                                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Perfil de talla</p>
                                                <p className="mt-2 text-sm text-white">{sizeProfile.label}</p>
                                                <p className="mt-2 text-sm text-stone-400">{sizeProfile.values.join(', ')}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="rounded-[1.45rem] border border-white/8 bg-[#0d141d] p-5">
                                        <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Preparacion de variantes</p>
                                        <div className="mt-4 space-y-3">
                                            {availableSuggestedSizes.length > 0 && <button type="button" onClick={applySuggestedSizes} className="sa-button-secondary w-full px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">Aplicar tallas sugeridas</button>}
                                            {missingSuggestedSizes.length > 0 && <button type="button" onClick={() => openInlineCreate('sizes', { type: 'variant', field: 'cod_tallas_prv', rowIndex: 0 }, missingSuggestedSizes[0])} className="sa-button-ghost w-full px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">Crear talla sugerida</button>}
                                            <button type="button" onClick={() => setShowInternalFields((current) => !current)} className="sa-button-ghost w-full px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">{showInternalFields ? 'Ocultar datos internos' : 'Mostrar datos internos opcionales'}</button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
                                <button type="button" onClick={goBack} className="sa-button-ghost inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    <ArrowLeft size={14} />
                                    Volver
                                </button>
                                <button type="button" onClick={goNext} className="sa-button-primary inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    {data.usa_variantes ? 'Continuar con variantes' : 'Continuar con revisión'}
                                    <ArrowRight size={14} />
                                </button>
                            </div>
                        </section>
                    )}

                    {currentStep === 'variants' && (
                        <section className="sa-panel rounded-[1.6rem] p-5">
                            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 5</p>
                                    <h2 className="mt-2 text-lg text-white">Variantes</h2>
                                    <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">Registra cada combinación de color y talla con su precio y existencias correspondientes.</p>
                                </div>
                                <button type="button" onClick={addVariantRow} className="sa-button-secondary inline-flex items-center gap-2 px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    <Plus size={14} />
                                    Agregar variante
                                </button>
                            </div>

                            <div className="mt-5 space-y-4">
                                {data.variant_rows.map((row, index) => (
                                    <div key={`variant-row-${index}-${row.id || 'new'}`} className={`rounded-[1.4rem] border p-4 transition ${row.remove ? 'border-dashed border-white/10 bg-[#0a1016] opacity-65' : 'border-white/8 bg-[#0d141d]'}`}>
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm text-white">Configuración {index + 1}</p>
                                                <p className="mt-1 text-sm text-stone-400">{previewVariantCode(previewBaseCode, index + 1, true, row.sku_prv)}</p>
                                            </div>
                                            <button type="button" onClick={() => (row.remove ? restoreVariantRow(index) : removeVariantRow(index))} className="sa-button-ghost px-4 py-3 text-[0.7rem] uppercase tracking-[0.18em]">{row.remove ? 'Restaurar' : 'Quitar'}</button>
                                        </div>

                                        {!row.remove && (
                                            <>
                                                <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                    <SuperadminComboboxField
                                                        label="Color"
                                                        value={row.cod_colores_prv}
                                                        options={orderedColorOptions}
                                                        placeholder="Selecciona color"
                                                        searchPlaceholder="Buscar color"
                                                        error={errors[`variant_rows.${index}.cod_colores_prv`]}
                                                        hint={index === 0 ? colorHint : undefined}
                                                        createLabel="OTRO COLOR"
                                                        createMode="list"
                                                        onChange={(value) => updateVariantRow(index, 'cod_colores_prv', value)}
                                                        onCreate={(seed) => openInlineCreate('colors', { type: 'variant', field: 'cod_colores_prv', rowIndex: index }, seed)}
                                                    />

                                                    <SuperadminComboboxField
                                                        label="Talla"
                                                        value={row.cod_tallas_prv}
                                                        options={orderedSizeOptions}
                                                        placeholder="Selecciona talla"
                                                        searchPlaceholder="Buscar talla"
                                                        error={errors[`variant_rows.${index}.cod_tallas_prv`]}
                                                        hint={index === 0 ? `Sugerencia principal: ${sizeProfile.values.join(', ')}` : undefined}
                                                        createLabel="OTRA TALLA"
                                                        createMode="list"
                                                        onChange={(value) => updateVariantRow(index, 'cod_tallas_prv', value)}
                                                        onCreate={(seed) => openInlineCreate('sizes', { type: 'variant', field: 'cod_tallas_prv', rowIndex: index }, seed)}
                                                    />

                                                    <label className="space-y-2">
                                                        <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Código interno</span>
                                                        <input value={previewVariantCode(previewBaseCode, index + 1, true, row.sku_prv)} disabled className="sa-input w-full" />
                                                    </label>

                                                    <label className="space-y-2">
                                                        <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Precio de venta (Bs.)</span>
                                                        <input type="number" min="0.01" step="0.01" value={row.pve_prv} onChange={(event) => updateVariantRow(index, 'pve_prv', event.target.value)} className="sa-input w-full" placeholder="0.00" />
                                                        {errors[`variant_rows.${index}.pve_prv`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.pve_prv`]}</p>}
                                                    </label>

                                                    <label className="space-y-2">
                                                        <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Cantidad inicial</span>
                                                        <input type="number" min="1" step="1" value={row.stock_inicial} onChange={(event) => updateVariantRow(index, 'stock_inicial', event.target.value)} className="sa-input w-full" placeholder="1" />
                                                        {errors[`variant_rows.${index}.stock_inicial`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.stock_inicial`]}</p>}
                                                    </label>

                                                    <label className="space-y-2">
                                                        <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Ubicación</span>
                                                        <select value={row.ubi_inv} onChange={(event) => updateVariantRow(index, 'ubi_inv', event.target.value)} className="sa-input w-full">
                                                            {optionSets.locations.map((option) => <option key={`${option.value}-${index}`} value={option.value}>{option.label}</option>)}
                                                        </select>
                                                        {errors[`variant_rows.${index}.ubi_inv`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.ubi_inv`]}</p>}
                                                    </label>
                                                </div>

                                                {showInternalFields && (
                                                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                                                        <label className="space-y-2">
                                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Costo interno (Bs.)</span>
                                                            <input type="number" min="0" step="0.01" value={row.cos_prv} onChange={(event) => updateVariantRow(index, 'cos_prv', event.target.value)} className="sa-input w-full" placeholder="0.00" />
                                                            <p className="text-sm text-stone-400">Dato interno para margen y rentabilidad.</p>
                                                            {errors[`variant_rows.${index}.cos_prv`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.cos_prv`]}</p>}
                                                        </label>
                                                        <label className="space-y-2">
                                                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Código adicional (opcional)</span>
                                                            <input value={row.cob_prv} onChange={(event) => updateVariantRow(index, 'cob_prv', event.target.value)} className="sa-input w-full" placeholder="Código adicional o referencia" />
                                                            {errors[`variant_rows.${index}.cob_prv`] && <p className="text-sm text-rose-300">{errors[`variant_rows.${index}.cob_prv`]}</p>}
                                                        </label>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </div>
                                ))}

                                {errors.variant_rows && <p className="text-sm text-rose-300">{errors.variant_rows}</p>}
                            </div>

                            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
                                <button type="button" onClick={goBack} className="sa-button-ghost inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    <ArrowLeft size={14} />
                                    Volver
                                </button>
                                <button type="button" onClick={goNext} className="sa-button-primary inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    Continuar con revisión
                                    <ArrowRight size={14} />
                                </button>
                            </div>
                        </section>
                    )}

                    {currentStep === 'review' && (
                        <section className="sa-panel rounded-[1.6rem] p-5">
                            <div>
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso 6</p>
                                <h2 className="mt-2 text-lg text-white">Revisión y guardado</h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-300">Revisa la ficha, completa la galería si hace falta y confirma el guardado final.</p>
                            </div>

                            <div className="mt-6 grid gap-3 sm:grid-cols-4">
                                <div className="rounded-[1.3rem] border border-white/8 bg-[#0d141d] p-4">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Configuración</p>
                                    <p className="mt-3 text-2xl text-white">{data.usa_variantes ? 'Variantes' : 'Única'}</p>
                                </div>
                                <div className="rounded-[1.3rem] border border-white/8 bg-[#0d141d] p-4">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Precio principal</p>
                                    <p className="mt-3 text-2xl text-white">Bs {Number(summary.price || 0).toFixed(2)}</p>
                                </div>
                                <div className="rounded-[1.3rem] border border-white/8 bg-[#0d141d] p-4">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Existencias totales</p>
                                    <p className="mt-3 text-2xl text-white">{summary.stock}</p>
                                </div>
                                <div className="rounded-[1.3rem] border border-white/8 bg-[#0d141d] p-4">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Imágenes</p>
                                    <p className="mt-3 text-2xl text-white">{summary.images}</p>
                                </div>
                            </div>

                            <div className="mt-5 grid gap-4 lg:grid-cols-[minmax(0,0.95fr)_minmax(340px,1.05fr)]">
                                <div className="rounded-[1.45rem] border border-white/8 bg-[#0d141d] p-5">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Resumen de la prenda</p>
                                    <dl className="mt-4 space-y-3">
                                        <div className="flex items-start justify-between gap-4"><dt className="text-sm text-stone-400">Nombre</dt><dd className="text-sm text-white">{data.nom_pre || 'Pendiente'}</dd></div>
                                        <div className="flex items-start justify-between gap-4"><dt className="text-sm text-stone-400">Categoría</dt><dd className="text-sm text-white">{selectedCategory || 'Pendiente'}</dd></div>
                                        <div className="flex items-start justify-between gap-4"><dt className="text-sm text-stone-400">Marca</dt><dd className="text-sm text-white">{optionSets.brands.find((option) => option.value === data.cod_marcas_pre)?.label ?? 'Pendiente'}</dd></div>
                                        <div className="flex items-start justify-between gap-4"><dt className="text-sm text-stone-400">Temporada</dt><dd className="text-sm text-white">{selectedSeason || 'Pendiente'}</dd></div>
                                        <div className="flex items-start justify-between gap-4"><dt className="text-sm text-stone-400">Género</dt><dd className="text-sm text-white">{data.gen_pre ? normalizeText(data.gen_pre) : 'Pendiente'}</dd></div>
                                        <div className="flex items-start justify-between gap-4"><dt className="text-sm text-stone-400">Portada</dt><dd className="text-sm text-white">{currentPrimaryLabel ?? 'Pendiente'}</dd></div>
                                    </dl>
                                </div>

                                <div className="rounded-[1.45rem] border border-white/8 bg-[#0d141d] p-5">
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Galería adicional</p>
                                            <p className="mt-2 text-sm text-stone-300">Puedes agregar vistas de apoyo antes de guardar.</p>
                                        </div>
                                        <button type="button" onClick={addGalleryRow} className="sa-button-secondary inline-flex items-center gap-2 px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                            <Plus size={14} />
                                            Agregar imagen
                                        </button>
                                    </div>

                                    <div className="mt-4 space-y-3">
                                        {data.existing_images.map((image, index) => (
                                            <div key={`existing-image-${image.id}`} className={`rounded-[1.2rem] border p-3 transition ${image.remove ? 'border-dashed border-white/10 bg-[#0a1016] opacity-65' : 'border-white/8 bg-[#0a1016]'}`}>
                                                <div className="flex flex-col gap-4 lg:flex-row">
                                                    <div className="h-28 w-full overflow-hidden rounded-[1rem] border border-white/8 bg-[#070b10] lg:w-28">
                                                        {image.url_igp ? <img src={image.url_igp} alt={image.rut_igp || `Imagen ${index + 1}`} className="h-full w-full object-cover" /> : <div className="flex h-full items-center justify-center text-stone-500"><ImagePlus size={18} /></div>}
                                                    </div>
                                                    <div className="min-w-0 flex-1 space-y-3">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <p className="text-sm text-white">{image.rut_igp ? filenameLabel(image.rut_igp) : `Imagen ${index + 1}`}</p>
                                                            {image.pri_igp && <SuperadminBadge tone="accent">Principal</SuperadminBadge>}
                                                            {image.variant_label && <SuperadminBadge tone="neutral">{image.variant_label}</SuperadminBadge>}
                                                        </div>
                                                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                            <label className="space-y-2">
                                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Orden</span>
                                                                <input type="number" min="0" value={image.ord_igp} onChange={(event) => setData('existing_images', data.existing_images.map((item, itemIndex) => itemIndex === index ? { ...item, ord_igp: event.target.value } : item))} className="sa-input w-full" />
                                                            </label>
                                                            <label className="space-y-2">
                                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Uso</span>
                                        <input value={image.fue_igp} onChange={(event) => setData('existing_images', data.existing_images.map((item, itemIndex) => itemIndex === index ? { ...item, fue_igp: event.target.value } : item))} className="sa-input w-full" placeholder="catálogo" />
                                                            </label>
                                                            <div className="flex flex-wrap gap-2 pt-6">
                                                                <button type="button" onClick={() => setExistingPrimary(index)} className="sa-button-ghost px-4 py-3 text-[0.7rem] uppercase tracking-[0.18em]">Usar como portada</button>
                                                                <button type="button" onClick={() => toggleExistingImageRemoval(index)} className="sa-button-ghost px-4 py-3 text-[0.7rem] uppercase tracking-[0.18em]">{image.remove ? 'Restaurar' : 'Quitar'}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}

                                        {data.gallery_images.map((image, index) => (
                                            <div key={`new-gallery-image-${index}`} className="rounded-[1.2rem] border border-white/8 bg-[#0a1016] p-3">
                                                <div className="grid gap-4 lg:grid-cols-[160px_minmax(0,1fr)]">
                                                    <div className="h-36 overflow-hidden rounded-[1rem] border border-white/8 bg-[#070b10]">
                                                        {galleryPreviewUrls.current[index] ? <img src={galleryPreviewUrls.current[index]} alt={`Nueva imagen ${index + 1}`} className="h-full w-full object-cover" /> : <div className="flex h-full items-center justify-center text-stone-500"><ImagePlus size={18} /></div>}
                                                    </div>
                                                    <div className="space-y-3">
                                                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                            <label className="space-y-2 md:col-span-2">
                                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Archivo</span>
                                                                <input type="file" accept="image/*" onChange={(event) => updateGalleryFile(index, event.target.files?.[0] ?? null)} className="sa-input w-full file:mr-4 file:rounded-full file:border-0 file:bg-white/8 file:px-4 file:py-2 file:text-xs file:uppercase file:tracking-[0.18em] file:text-stone-200" />
                                                            </label>
                                                            <label className="space-y-2">
                                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Orden</span>
                                                                <input type="number" min="0" value={image.ord_igp} onChange={(event) => updateGalleryRow(index, 'ord_igp', event.target.value)} className="sa-input w-full" />
                                                            </label>
                                                            <label className="space-y-2">
                                                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Uso</span>
                                        <input value={image.fue_igp} onChange={(event) => updateGalleryRow(index, 'fue_igp', event.target.value)} className="sa-input w-full" placeholder="galería" />
                                                            </label>
                                                            <div className="pt-6">
                                                                <button type="button" onClick={() => removeGalleryRow(index)} className="sa-button-ghost px-4 py-3 text-[0.7rem] uppercase tracking-[0.18em]">Quitar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                                <button type="button" onClick={goBack} className="sa-button-ghost inline-flex items-center justify-center gap-2 px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                    <ArrowLeft size={14} />
                                    Volver
                                </button>
                                <Link href={meta.indexHref} className="sa-button-ghost px-5 py-3 text-center text-[0.72rem] uppercase tracking-[0.18em]">Cancelar</Link>
                                {stateAction && <button type="button" onClick={handleStateAction} disabled={processing} className="rounded-full border border-[rgba(183,108,112,0.28)] bg-[rgba(183,108,112,0.12)] px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] text-[rgb(245,208,210)] transition hover:bg-[rgba(183,108,112,0.18)] disabled:cursor-not-allowed disabled:opacity-70">{stateAction.label}</button>}
                                <button type="submit" disabled={processing} className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70">{processing ? 'Guardando' : isEdit ? 'Actualizar prenda' : 'Guardar prenda'}</button>
                            </div>
                        </section>
                    )}
                </form>
            </div>
            <SuperadminDrawer open={Boolean(inlineDrawer)} title={inlineTitle} description={inlineDescription} onClose={closeInlineCreate}>
                {inlineDrawer && (
                    <form onSubmit={submitInlineCreate} className="space-y-4">
                        <label className="space-y-2">
                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">{inlineDrawer.resource === 'colors' ? 'Tono real' : 'Nombre'}</span>
                            <input value={inlineForm.name} onChange={(event) => setInlineForm((current) => ({ ...current, name: event.target.value.toUpperCase(), family: inlineDrawer.resource === 'colors' ? defaultColorFamily(event.target.value) : current.family, order: inlineDrawer.resource === 'sizes' ? String(suggestSizeOrder(event.target.value)) : current.order }))} className="sa-input w-full" />
                            {inlineErrors.nom_cat && <p className="text-sm text-rose-300">{inlineErrors.nom_cat}</p>}
                            {inlineErrors.nom_mar && <p className="text-sm text-rose-300">{inlineErrors.nom_mar}</p>}
                            {inlineErrors.nom_col && <p className="text-sm text-rose-300">{inlineErrors.nom_col}</p>}
                            {inlineErrors.nom_tal && <p className="text-sm text-rose-300">{inlineErrors.nom_tal}</p>}
                            {inlineErrors.nom_tem && <p className="text-sm text-rose-300">{inlineErrors.nom_tem}</p>}
                        </label>
                        {inlineDrawer.resource === 'colors' && (
                            <>
                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Familia dominante</span>
                                    <select value={inlineForm.family} onChange={(event) => setInlineForm((current) => ({ ...current, family: event.target.value }))} className="sa-input w-full">
                                        <option value="">Selecciona familia</option>
                                        {colorFamilyOptions.map((option) => <option key={`${option.value}-family`} value={option.value}>{option.label}</option>)}
                                    </select>
                                </label>
                                <label className="space-y-2">
                                    <span className="inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400"><Palette size={12} />Muestra del tono</span>
                                    <div className="flex items-center gap-3">
                                        <input type="color" value={inlineForm.hex} onChange={(event) => setInlineForm((current) => ({ ...current, hex: event.target.value.toUpperCase() }))} className="h-11 w-16 rounded-xl border border-white/10 bg-[#0d141d]" />
                                        <input value={inlineForm.hex} onChange={(event) => setInlineForm((current) => ({ ...current, hex: event.target.value.toUpperCase() }))} className="sa-input w-full" placeholder="#D8A7B1" />
                                    </div>
                                </label>
                            </>
                        )}
                        {inlineDrawer.resource === 'sizes' && (
                            <label className="space-y-2">
                                <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Orden</span>
                                <input value={inlineForm.order} onChange={(event) => setInlineForm((current) => ({ ...current, order: event.target.value }))} className="sa-input w-full" />
                            </label>
                        )}
                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button" onClick={closeInlineCreate} className="sa-button-ghost px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">Cancelar</button>
                            <button type="submit" disabled={inlineProcessing} className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70">{inlineProcessing ? 'Guardando' : 'Crear y seleccionar'}</button>
                        </div>
                    </form>
                )}
            </SuperadminDrawer>
        </SuperadminLayout>
    );
}
