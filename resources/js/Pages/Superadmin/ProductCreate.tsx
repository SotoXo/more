import { preparedImageUrl } from '@/lib/garmentAssistant';
import GarmentIntelligentAssistant from '@/Components/Superadmin/GarmentIntelligentAssistant';
import type { ProductEditorForm, ProductVariantRow, ExistingImageRow, NewGalleryImageRow } from '@/types/productEditor';
import SuperadminBadge from '@/Components/Superadmin/SuperadminBadge';
import SuperadminComboboxField from '@/Components/Superadmin/SuperadminComboboxField';
import ProductColorPicker from '@/Components/Superadmin/ProductColorPicker';
import { PaletteEditor, VisualAttributePicker } from '@/Components/Superadmin/ProductAttributeFields';
import SuperadminDrawer from '@/Components/Superadmin/SuperadminDrawer';
import SuperadminFlashNotice from '@/Components/Superadmin/SuperadminFlashNotice';
import SuperadminLayout from '@/Components/Superadmin/SuperadminLayout';
import SuperadminPageHeader from '@/Components/Superadmin/SuperadminPageHeader';
import type { LandingPageProps } from '@/types/landing';
import type { SuperadminBreadcrumb, SuperadminFormFieldOption, SuperadminFormStateAction, SuperadminFormSubmit, SuperadminResourceMeta } from '@/types/superadmin';
import axios from 'axios';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, ImagePlus, Layers3, Palette, Plus, Sparkles, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react';

type InlineCatalogKey = 'categories' | 'brands' | 'colors' | 'sizes' | 'seasons';
type ProductStepId = 'base' | 'image' | 'type' | 'commercial' | 'variants' | 'review';
type InlineTarget = { type: 'product'; field: 'cod_categorias_pre' | 'cod_marcas_pre' | 'cod_temporadas_pre' } | { type: 'variant'; field: 'cod_colores_prv' | 'cod_tallas_prv'; rowIndex: number };
type InlineDrawerState = { resource: InlineCatalogKey; target: InlineTarget; seed: string; };
type InlineForm = { name: string; family: string; hex: string; order: string; code: string; startDate: string; endDate: string; };
type CategorySizeProfile = { system?: string | null; group?: string | null; segment?: string | null };
type SizeProfile = { key: 'alpha' | 'numeric' | 'unit'; label: string; values: string[]; segment: string; system: string; group: string; source: 'category' | 'fallback'; };
type ProductStep = { id: ProductStepId; number: number; title: string; description: string; };
type ColorInsight = { hex: string; family: SuperadminFormFieldOption | null; suggestedColor: SuperadminFormFieldOption | null; };
type PickedColorSample = { hex: string; relativeX: number; relativeY: number; };
type ProductCreateProps = { meta: SuperadminResourceMeta; breadcrumbs: SuperadminBreadcrumb[]; mode: 'create' | 'edit'; recordLabel?: string | null; submit: SuperadminFormSubmit; stateAction?: SuperadminFormStateAction | null; editor: { title: string; description: string; currentPrimaryUrl?: string | null; currentPrimaryLabel?: string | null; }; options: { categories: SuperadminFormFieldOption[]; brands: SuperadminFormFieldOption[]; seasons: SuperadminFormFieldOption[]; genders: SuperadminFormFieldOption[]; colors: SuperadminFormFieldOption[]; patterns: SuperadminFormFieldOption[]; textures: SuperadminFormFieldOption[]; materials: SuperadminFormFieldOption[]; sizes: SuperadminFormFieldOption[]; sizeSegments?: SuperadminFormFieldOption[]; locations: SuperadminFormFieldOption[]; colorFamilies: SuperadminFormFieldOption[]; categoryProfiles: Record<string, CategorySizeProfile>; }; initialData: ProductEditorForm; };

function createVariantRow(defaultPrice = '', defaultLocation = 'TIENDA'): ProductVariantRow { return { id: '', sku_prv: '', cod_colores_prv: '', tip_pal_prv: 'dominante', colores_adicionales: [], cod_tallas_prv: '', cob_prv: '', pve_prv: defaultPrice, cos_prv: '', stock_inicial: '1', ubi_inv: defaultLocation, est_prv: true, remove: false }; }
function createGalleryRow(order = '1', source = 'galeria'): NewGalleryImageRow { return { archivo: null, ord_igp: order, pri_igp: false, fue_igp: source }; }
function normalizeText(value: string) { return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase(); }
function inferCategoryProfile(name: string): CategorySizeProfile { const haystack = normalizeText(name); if (haystack.includes('PANTAL')) return { segment: 'ADULTO', system: 'NUMERICA', group: 'INFERIOR' }; if (haystack.includes('ACCESOR')) return { segment: 'ADULTO', system: 'UNICA', group: 'GENERAL' }; return { segment: 'ADULTO', system: 'ALFA', group: 'SUPERIOR' }; }
function normalizeCategorySystem(value?: string | null) { const system = normalizeText(value ?? ''); return system === 'UNITALLA' ? 'UNICA' : system; }
function fallbackSizeValues(system: string) { if (system === 'NUMERICA') return ['32', '34', '36', '38', '40', '42', '44', '46', '48']; if (system === 'UNICA') return ['UNITALLA']; return ['XS', 'S', 'M', 'L', 'XL']; }
function sizeProfileLabel(segment: string, system: string, group: string) { const prefix = segment ? `${segment} / ` : ''; if (system === 'NUMERICA') return `${prefix}Tallas numéricas sugeridas${group ? ` (${group})` : ''}`; if (system === 'UNICA') return `${prefix}Talla sugerida${group ? ` (${group})` : ''}`; return `${prefix}Tallas sugeridas${group ? ` (${group})` : ''}`; }
function inferSizeProfile(productName: string, categoryLabel: string, categoryProfile?: CategorySizeProfile): SizeProfile { const fallback = inferCategoryProfile(`${productName} ${categoryLabel}`); const segment = normalizeText(categoryProfile?.segment ?? fallback.segment ?? 'ADULTO'); const system = normalizeCategorySystem(categoryProfile?.system) || normalizeCategorySystem(fallback.system) || 'ALFA'; const group = normalizeText(categoryProfile?.group ?? fallback.group ?? (system === 'NUMERICA' ? 'INFERIOR' : system === 'UNICA' ? 'GENERAL' : 'SUPERIOR')); const key = system === 'NUMERICA' ? 'numeric' : system === 'UNICA' ? 'unit' : 'alpha'; return { key, label: sizeProfileLabel(segment, system, group), values: fallbackSizeValues(system), segment, system, group, source: categoryProfile?.system || categoryProfile?.group ? 'category' : 'fallback' }; }
function previewCodePrefix(source: string) { const letters = normalizeText(source).replace(/[^A-Z]/g, ''); const prefix = (letters || 'PRE').slice(0, 3).padEnd(3, 'X'); return `${prefix}-XXXXX`; }
function previewVariantCode(baseCode: string, position: number, hasVariants: boolean, persistedCode = '') { if (persistedCode) return persistedCode; return hasVariants ? `${baseCode}-${String(position).padStart(2, '0')}` : baseCode; }
function suggestSizeOrder(name: string) { const normalized = normalizeText(name).replace('Ú', 'U'); const alphaMap: Record<string, number> = { XXXS: 5, XXS: 10, XS: 20, S: 30, M: 40, L: 50, XL: 60, XXL: 70, XXXL: 80, XXXXL: 90, '5XL': 100, UNITALLA: 10, UNICA: 10 }; if (alphaMap[normalized]) return alphaMap[normalized]; if (/^\d+$/.test(normalized)) return 1000 + Number(normalized) * 10; const us = normalized.replace(/\s*US$/, '').replace(' 1/2', '.5'); if (/^\d+(\.5)?$/.test(us)) return 3000 + Math.round(Number(us) * 10); const eu = normalized.match(/^(\d+)\s*EU$/); if (eu) return 4000 + Number(eu[1]); const age = normalized.match(/^(\d+)A$/); if (age) return 5000 + Number(age[1]) * 10; const height = normalized.match(/^(\d+)\s*CM$/); if (height) return 6000 + Number(height[1]); const months = normalized.match(/^\d+-(\d+)M$/); if (months) return 7000 + Number(months[1]); return 9999; }
function sortOptions(resource: InlineCatalogKey, items: SuperadminFormFieldOption[]) { const unique = Array.from(new Map(items.map((item) => [item.value, item])).values()); if (resource === 'sizes') return unique.sort((left, right) => suggestSizeOrder(left.label) - suggestSizeOrder(right.label)); return unique.sort((left, right) => left.label.localeCompare(right.label, 'es', { sensitivity: 'base' })); }
function prioritizeOptions(options: SuperadminFormFieldOption[], preferredValues: string[]) { const order = preferredValues.filter(Boolean); if (order.length === 0) return options; return [...options].sort((left, right) => { const leftRank = order.indexOf(left.value); const rightRank = order.indexOf(right.value); if (leftRank === -1 && rightRank === -1) return 0; if (leftRank === -1) return 1; if (rightRank === -1) return -1; return leftRank - rightRank; }); }
function predominantSizeSegment(rows: ProductVariantRow[], sizes: SuperadminFormFieldOption[]) {
    const sizeMap = new Map(sizes.map((option) => [option.value, normalizeText(option.segment ?? 'ADULTO')]));
    const counts = rows.reduce<Record<string, number>>((carry, row) => {
        if (row.remove || !row.cod_tallas_prv) return carry;
        const segment = sizeMap.get(row.cod_tallas_prv);
        if (!segment) return carry;
        carry[segment] = (carry[segment] ?? 0) + 1;
        return carry;
    }, {});

    return Object.entries(counts).sort((left, right) => right[1] - left[1])[0]?.[0] ?? 'ADULTO';
}
function defaultColorFamily(seed: string) { const normalized = normalizeText(seed); if (normalized.includes('ROSA')) return 'ROSA'; if (normalized.includes('AZUL')) return 'AZUL'; if (normalized.includes('VERDE')) return 'VERDE'; if (normalized.includes('BEIGE')) return 'BEIGE'; if (normalized.includes('CAMEL')) return 'CAMEL'; if (normalized.includes('CREMA') || normalized.includes('BLANCO')) return 'BLANCO'; if (normalized.includes('BORGONA') || normalized.includes('ROJO')) return 'ROJO'; if (normalized.includes('MARRON') || normalized.includes('CAFE')) return 'MARRON'; if (normalized.includes('NEGRO')) return 'NEGRO'; if (normalized.includes('GRIS')) return 'GRIS'; return normalized.split(' ')[0] || 'NEUTRO'; }
function defaultSeasonCode(seed: string) { const words = normalizeText(seed).replace(/[^A-Z\s-]/g, '').split(/[\s-]+/).filter(Boolean); return (words.map((word) => word[0]).join('') || normalizeText(seed).slice(0, 3) || 'TMP').slice(0, 10); }
function inlineDefaults(resource: InlineCatalogKey, seed: string): InlineForm { return { name: seed.trim().toUpperCase(), family: resource === 'colors' ? defaultColorFamily(seed) : '', hex: '#D8C3A5', order: resource === 'sizes' && seed ? String(suggestSizeOrder(seed)) : '', code: resource === 'seasons' ? defaultSeasonCode(seed) : '', startDate: '', endDate: '' }; }
function valuesHasFiles(values: ProductEditorForm) { return values.imagen_principal instanceof File || values.gallery_images.some((image) => image.archivo instanceof File); }
function normalizeHex(value: string) { const hex = value.trim().replace('#', '').toUpperCase(); return /^([A-F0-9]{6})$/.test(hex) ? `#${hex}` : null; }
function hexToRgb(value: string) { const hex = normalizeHex(value); if (!hex) return null; return { r: Number.parseInt(hex.slice(1, 3), 16), g: Number.parseInt(hex.slice(3, 5), 16), b: Number.parseInt(hex.slice(5, 7), 16) }; }
function rgbToHex(r: number, g: number, b: number) { return `#${[r, g, b].map((value) => Math.max(0, Math.min(255, Math.round(value))).toString(16).padStart(2, '0')).join('').toUpperCase()}`; }
function colorDistance(left: string, right: string) { const a = hexToRgb(left); const b = hexToRgb(right); if (!a || !b) return Number.MAX_SAFE_INTEGER; return Math.sqrt(((a.r - b.r) ** 2) + ((a.g - b.g) ** 2) + ((a.b - b.b) ** 2)); }
const familyPalette: Record<string, string> = { NEGRO: '#111111', BLANCO: '#F7F6F2', GRIS: '#8A8F98', BEIGE: '#D8C3A5', CAMEL: '#B88956', MARRON: '#6F4E37', AZUL: '#1D4ED8', CELESTE: '#6F9FD8', VERDE: '#16A34A', ROJO: '#B91C1C', BORGONA: '#8A2332', ROSA: '#D28FA0', AMARILLO: '#EAB308', NARANJA: '#EA580C', MORADO: '#7E22CE', DORADO: '#C8A96A', PLATEADO: '#C0C0C0' };
function hexToHsl(hex: string) { const rgb = hexToRgb(hex) ?? { r: 0, g: 0, b: 0 }; const r = rgb.r / 255; const g = rgb.g / 255; const b = rgb.b / 255; const max = Math.max(r, g, b); const min = Math.min(r, g, b); const lightness = (max + min) / 2; const delta = max - min; const saturation = delta === 0 ? 0 : delta / (1 - Math.abs((2 * lightness) - 1)); let hue = 0; if (delta !== 0) { if (max === r) hue = 60 * (((g - b) / delta) % 6); else if (max === g) hue = 60 * (((b - r) / delta) + 2); else hue = 60 * (((r - g) / delta) + 4); } if (hue < 0) hue += 360; return { h: Math.round(hue), s: Math.round(saturation * 100), l: Math.round(lightness * 100) }; }
function familyFromHex(hex: string) { const hsl = hexToHsl(hex); if (hsl.s >= 20) { if (hsl.h >= 15 && hsl.h < 35 && hsl.l < 38) return 'MARRON'; if (hsl.h >= 15 && hsl.h < 35 && hsl.s < 65) return 'CAMEL'; if (hsl.h >= 15 && hsl.h < 35) return 'NARANJA'; if (hsl.h >= 35 && hsl.h < 65 && hsl.l >= 75) return 'BEIGE'; if (hsl.h >= 35 && hsl.h < 75) return 'AMARILLO'; if (hsl.h >= 75 && hsl.h < 165) return 'VERDE'; if (hsl.h >= 165 && hsl.h < 195) return 'CELESTE'; if (hsl.h >= 195 && hsl.h < 255) return 'AZUL'; if (hsl.h >= 255 && hsl.h < 310) return 'MORADO'; if (hsl.h >= 310 && hsl.h < 345) return 'ROSA'; return 'ROJO'; } return Object.entries(familyPalette).map(([label, swatch]) => ({ label, distance: colorDistance(hex, swatch) })).sort((left, right) => left.distance - right.distance)[0]?.label ?? 'AZUL'; }
const knownColorTones: Record<string, Record<string, string>> = { VERDE: { 'VERDE BOSQUE OSCURO': '#0D2608', 'VERDE BOTELLA': '#0B3D2E', 'VERDE PINO OSCURO': '#174A2A', 'VERDE OLIVA': '#66704A', 'VERDE ESMERALDA': '#047857', 'VERDE MENTA': '#98D8B7', 'VERDE SALVIA': '#9CAF88', 'VERDE LIMON': '#B7D93D' }, AZUL: { 'AZUL MARINO': '#1F2A44', 'AZUL PETROLEO': '#0F4C5C', 'AZUL NOCHE': '#0B1F3A', 'AZUL REY': '#1D4ED8', 'AZUL CIELO': '#7DB7E8', CELESTE: '#6F9FD8', TURQUESA: '#14B8A6' }, CELESTE: { CELESTE: '#6F9FD8', 'AZUL CIELO': '#7DB7E8', TURQUESA: '#14B8A6' }, ROJO: { 'ROJO VINO': '#6F1D2C', 'ROJO CEREZA': '#B91C1C', 'ROJO RUBI': '#9B111E', 'ROJO LADRILLO': '#8B3A2E', 'ROJO INTENSO': '#DC2626', BORGONA: '#8A2332' }, BORGONA: { BORGONA: '#8A2332', 'ROJO VINO': '#6F1D2C' }, ROSA: { 'ROSA PALO': '#D8A7B1', 'ROSA PASTEL': '#F4B6C2', 'ROSA FUCSIA': '#C026D3', 'ROSA EMPOLVADO': '#C894A2' }, BEIGE: { CREMA: '#F2E8D8', 'BEIGE ARENA': '#D8C3A5', 'BEIGE CLARO': '#E8D8C3', MARFIL: '#F7F0DF' }, CAMEL: { CAMEL: '#B88956', 'BEIGE CAMEL': '#D3A478' }, MARRON: { 'MARRON CHOCOLATE': '#4E2F1E', 'MARRON CAFE': '#6F4E37', 'MARRON TABACO': '#8B5A2B' }, GRIS: { 'GRIS PERLA': '#D1D5DB', 'GRIS CEMENTO': '#9CA3AF', 'GRIS ACERO': '#64748B', 'GRIS OSCURO': '#374151' }, MORADO: { LILA: '#C8A2C8', LAVANDA: '#BFA7E6', 'MORADO UVA': '#6D28D9', 'MORADO OSCURO': '#3B0764' }, NEGRO: { NEGRO: '#111111', 'NEGRO CARBON': '#1F1F1F' }, BLANCO: { BLANCO: '#F7F6F2', 'BLANCO HUESO': '#EDE6D6', MARFIL: '#F7F0DF' } };
function suggestedColorName(hex: string, family: string) { const candidates = knownColorTones[family] ?? {}; const best = Object.entries(candidates).map(([name, toneHex]) => ({ name, distance: colorDistance(hex, toneHex) })).sort((left, right) => left.distance - right.distance)[0]; if (best && best.distance <= 95) return best.name; const hsl = hexToHsl(hex); const modifier = hsl.l <= 24 ? 'OSCURO' : hsl.l >= 78 ? 'CLARO' : hsl.s <= 18 ? 'SUAVE' : hsl.s >= 70 && hsl.l >= 35 && hsl.l <= 65 ? 'INTENSO' : ''; return `${family} ${modifier}`.trim(); }
function inferColorFamily(hex: string, families: SuperadminFormFieldOption[]) { const bestFamily = familyFromHex(hex); return families.find((option) => normalizeText(option.label) === bestFamily || normalizeText(option.value) === bestFamily) ?? families.find((option) => normalizeText(option.label).includes(bestFamily)) ?? { label: bestFamily, value: bestFamily, swatch: familyPalette[bestFamily] }; }
function closestColorOption(colors: SuperadminFormFieldOption[], hex: string, familyLabel?: string) { const normalizedHex = normalizeHex(hex); const family = normalizeText(familyLabel ?? ''); const exact = colors.find((option) => normalizeHex(option.hex ?? option.swatch ?? '') === normalizedHex); if (exact) return exact; const best = [...colors].filter((option) => Boolean(option.swatch) && (!family || normalizeText(option.group ?? '').includes(family))).map((option) => ({ option, distance: colorDistance(option.swatch ?? '#000000', hex) })).sort((left, right) => left.distance - right.distance)[0]; return best && best.distance <= 24 ? best.option : null; }
function variantIssue(row?: ProductVariantRow | null) {
    if (!row || row.remove) return 'Completa la configuración de la variante.';
    if (row.colores_adicionales.includes(row.cod_colores_prv) || new Set(row.colores_adicionales).size !== row.colores_adicionales.length) return 'No repitas colores en la paleta.';
    if (row.tip_pal_prv === 'equilibrada' && row.colores_adicionales.length < 1) return 'Agrega al menos dos colores para una paleta equilibrada.';
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
function normalizeEditorData(data: ProductEditorForm): ProductEditorForm { return { ...data, modelo_pre: data.modelo_pre ?? '', ai_run_ids: data.ai_run_ids ?? [], ai_image_tokens: data.ai_image_tokens ?? [] }; }
function filenameLabel(path: string) { return path.split('/').pop() ?? path; }

export default function ProductCreate({ meta, breadcrumbs, mode, recordLabel, submit, stateAction, editor, options, initialData }: ProductCreateProps) {
    const page = usePage<LandingPageProps>();
    const flash = page.props.flash;
    const isEdit = mode === 'edit';
    const displayTitle = isEdit ? recordLabel ?? editor.title : editor.title;
    const [optionSets, setOptionSets] = useState({ categories: options.categories, brands: options.brands, seasons: options.seasons, genders: options.genders, colors: options.colors, sizes: options.sizes, locations: options.locations });
    const [categoryProfiles, setCategoryProfiles] = useState(options.categoryProfiles);
    const [selectedSizeSegment, setSelectedSizeSegment] = useState(() => predominantSizeSegment(initialData.variant_rows, options.sizes));
    const [colorFamilyOptions, setColorFamilyOptions] = useState(options.colorFamilies);
    const [primaryPreview, setPrimaryPreview] = useState<string | null>(editor.currentPrimaryUrl ?? null);
    const [currentStep, setCurrentStep] = useState<ProductStepId>('base');
    const [stepError, setStepError] = useState<string | null>(null);
    const [materialTouched, setMaterialTouched] = useState(false);
    const [materialAttempted, setMaterialAttempted] = useState(false);
    const [showInternalFields, setShowInternalFields] = useState(false);
    const [inlineDrawer, setInlineDrawer] = useState<InlineDrawerState | null>(null);
    const [inlineForm, setInlineForm] = useState<InlineForm>(inlineDefaults('categories', ''));
    const [inlineErrors, setInlineErrors] = useState<Record<string, string>>({});
    const [inlineMessage, setInlineMessage] = useState<string | null>(null);
    const [inlineProcessing, setInlineProcessing] = useState(false);
    const [colorInsight, setColorInsight] = useState<ColorInsight | null>(null);
    const [pickedColorSample, setPickedColorSample] = useState<PickedColorSample | null>(null);
    const galleryPreviewUrls = useRef<Record<number, string>>({});
    const { data, setData, post, put, transform, processing, errors } = useForm<ProductEditorForm>(normalizeEditorData(initialData));

    const steps = useMemo<ProductStep[]>(() => {
        const base: ProductStep[] = [
            { id: 'base', number: 1, title: 'Datos base', description: 'Nombre, categoría, marca, temporada y género.' },
            { id: 'image', number: 2, title: 'Imagen principal', description: 'Portada principal de la prenda.' },
            { id: 'type', number: 3, title: 'Tipo de prenda', description: 'Define si será única o tendrá más tallas o colores.' },
            { id: 'commercial', number: 4, title: 'Configuración comercial', description: data.usa_variantes ? 'Preparación antes de cargar variantes.' : 'Color, talla, precio y existencias base.' },
            { id: 'review', number: data.usa_variantes ? 6 : 5, title: 'Revisión y guardado', description: 'Confirma la ficha, la galería y el cierre del alta.' },
        ];

        if (data.usa_variantes) {
            base.splice(4, 0, { id: 'variants', number: 5, title: 'Variantes', description: 'Carga cada combinación con precio y existencias.' });
        }

        return base;
    }, [data.usa_variantes]);
    const selectedCategory = useMemo(() => optionSets.categories.find((option) => option.value === data.cod_categorias_pre)?.label ?? '', [data.cod_categorias_pre, optionSets.categories]);
    const selectedSeason = useMemo(() => optionSets.seasons.find((option) => option.value === data.cod_temporadas_pre)?.label ?? '', [data.cod_temporadas_pre, optionSets.seasons]);
    const sizeSegmentOptions = useMemo(() => (options.sizeSegments ?? []).length > 0 ? options.sizeSegments ?? [] : [{ label: 'ADULTO', value: 'ADULTO' }, { label: 'INFANTIL', value: 'INFANTIL' }, { label: 'BEBE', value: 'BEBE' }], [options.sizeSegments]);
    const selectedCategoryProfile = useMemo(() => ({ ...(categoryProfiles[data.cod_categorias_pre] ?? inferCategoryProfile(selectedCategory)), segment: selectedSizeSegment }), [categoryProfiles, data.cod_categorias_pre, selectedCategory, selectedSizeSegment]);
    const sizeProfile = useMemo(() => inferSizeProfile(data.nom_pre, selectedCategory, selectedCategoryProfile), [data.nom_pre, selectedCategory, selectedCategoryProfile]);
    const previewBaseCode = useMemo(() => previewCodePrefix(selectedCategory || data.nom_pre || 'PRENDA'), [data.nom_pre, selectedCategory]);
    const activeVariantRows = useMemo(() => data.variant_rows.filter((row) => !row.remove), [data.variant_rows]);
    const firstActiveVariantIndex = useMemo(() => data.variant_rows.findIndex((row) => !row.remove), [data.variant_rows]);
    const resolvedBasePrice = useMemo(() => { const prices = activeVariantRows.map((row) => Number(row.pve_prv || 0)).filter((value) => Number.isFinite(value) && value > 0); if (prices.length > 0) return String(prices[0]); return data.prb_pre || '0'; }, [activeVariantRows, data.prb_pre]);
    const activeSizeOptions = useMemo(() => optionSets.sizes.filter((option) => option.active !== false), [optionSets.sizes]);
    const selectedSizeValues = useMemo(() => activeVariantRows.map((row) => row.cod_tallas_prv).filter(Boolean), [activeVariantRows]);
    const sizeOptionsForSelector = useMemo(() => {
        const activeValues = new Set(activeSizeOptions.map((option) => option.value));
        return sortOptions('sizes', [...activeSizeOptions, ...optionSets.sizes.filter((option) => selectedSizeValues.includes(option.value) && !activeValues.has(option.value))]);
    }, [activeSizeOptions, optionSets.sizes, selectedSizeValues]);
    const exactProfileSizes = useMemo(() => sortOptions('sizes', activeSizeOptions.filter((option) => normalizeText(option.segment ?? 'ADULTO') === sizeProfile.segment && normalizeCategorySystem(option.system) === sizeProfile.system && normalizeText(option.group ?? '') === sizeProfile.group)), [activeSizeOptions, sizeProfile.group, sizeProfile.segment, sizeProfile.system]);
    const segmentSizeOptions = useMemo(() => sortOptions('sizes', activeSizeOptions.filter((option) => normalizeText(option.segment ?? 'ADULTO') === sizeProfile.segment)), [activeSizeOptions, sizeProfile.segment]);
    const availableSuggestedSizes = exactProfileSizes;
    const missingSuggestedSizes = useMemo(() => exactProfileSizes.length > 0 ? [] : sizeProfile.values.filter((sizeName) => !activeSizeOptions.some((option) => normalizeText(option.label) === normalizeText(sizeName) && normalizeText(option.segment ?? 'ADULTO') === sizeProfile.segment)), [activeSizeOptions, exactProfileSizes.length, sizeProfile.segment, sizeProfile.values]);
    const fallbackSizeValues = useMemo(() => (exactProfileSizes.length > 0 ? [] : (segmentSizeOptions.length > 0 ? segmentSizeOptions : activeSizeOptions).map((option) => option.value)), [activeSizeOptions, exactProfileSizes.length, segmentSizeOptions]);
    const orderedSizeOptions = useMemo(() => {
        const recommendedValues = exactProfileSizes.map((option) => option.value);
        const orderedValues = [...recommendedValues, ...fallbackSizeValues];
        return prioritizeOptions(sizeOptionsForSelector, orderedValues).map((option) => ({ ...option, recommended: recommendedValues.includes(option.value) }));
    }, [exactProfileSizes, fallbackSizeValues, sizeOptionsForSelector]);
    const suggestedSizeLabels = useMemo(() => (exactProfileSizes.length > 0 ? exactProfileSizes.map((option) => option.label) : sizeProfile.values), [exactProfileSizes, sizeProfile.values]);
    const sizeFallbackMessage = exactProfileSizes.length === 0 ? 'No hay tallas recomendadas exactas para esta categoría. Puedes elegir una talla disponible.' : null;
    const orderedColorOptions = useMemo(() => prioritizeOptions(sortOptions('colors', optionSets.colors), colorInsight?.suggestedColor ? [colorInsight.suggestedColor.value] : []), [colorInsight, optionSets.colors]);
    const hasPrimaryImage = useMemo(() => Boolean(data.imagen_principal || data.ai_image_tokens.length > 0 || data.existing_images.some((image) => !image.remove && image.pri_igp) || primaryPreview), [data.existing_images, data.imagen_principal, data.ai_image_tokens, primaryPreview]);
    const currentPrimaryLabel = useMemo(() => { if (data.imagen_principal) return data.imagen_principal.name; const existingPrimary = data.existing_images.find((image) => !image.remove && image.pri_igp); if (existingPrimary) return filenameLabel(existingPrimary.rut_igp); if (data.ai_image_tokens.length > 0) return 'Imagen externa aprobada'; return data.existing_images.length ? null : editor.currentPrimaryLabel ?? null; }, [data.existing_images, data.imagen_principal, data.ai_image_tokens, editor.currentPrimaryLabel]);
    const galleryCount = useMemo(() => data.existing_images.filter((image) => !image.remove).length + data.gallery_images.filter((image) => image.archivo).length + (data.imagen_principal ? 1 : 0) + data.ai_image_tokens.length, [data.existing_images, data.gallery_images, data.imagen_principal, data.ai_image_tokens]);
    const summary = useMemo(() => ({ variants: activeVariantRows.length, stock: activeVariantRows.reduce((total, row) => total + (Number(row.stock_inicial) || 0), 0), price: resolvedBasePrice, images: galleryCount }), [activeVariantRows, galleryCount, resolvedBasePrice]);
    const colorHint = colorInsight ? `Familia sugerida: ${colorInsight.family?.label ?? 'Sin familia'} · tono sugerido: ${colorInsight.suggestedColor?.label ?? 'Revisar manualmente'}` : undefined;
    const selectedCommercialColor = useMemo(() => optionSets.colors.find((option) => option.value === activeVariantRows[0]?.cod_colores_prv) ?? null, [activeVariantRows, optionSets.colors]);
    const isSuggestedColorApplied = Boolean(colorInsight?.suggestedColor && selectedCommercialColor?.value === colorInsight.suggestedColor.value);
    const inlineColorHex = normalizeHex(inlineForm.hex) ?? '#D8C3A5';
    const inlineColorFamily = normalizeText(inlineForm.family || inferColorFamily(inlineColorHex, colorFamilyOptions)?.value || familyFromHex(inlineColorHex));
    const inlineColorName = suggestedColorName(inlineColorHex, inlineColorFamily);
    const inlineExistingColor = inlineDrawer?.resource === 'colors' ? closestColorOption(optionSets.colors, inlineColorHex, inlineColorFamily) : null;
    const inlineTitle = inlineDrawer ? { categories: 'Otra categoría', brands: 'Otra marca', colors: 'Seleccionar color', sizes: 'Otra talla', seasons: 'Otra temporada' }[inlineDrawer.resource] : '';
    const inlineDescription = inlineDrawer ? { categories: 'Registra la categoría y vuelve al formulario con esa opción seleccionada.', brands: 'Registra la marca y vuelve a la ficha de la prenda.', colors: 'Captura o selecciona el color específico de la prenda. Si ya existe, se reutiliza; si no, se crea y queda seleccionado.', sizes: 'Agrega la talla sin salir del registro de la prenda.', seasons: 'Registra una temporada nueva solo si la prenda no corresponde a las ya disponibles.' }[inlineDrawer.resource] : '';

    useEffect(() => { setData(normalizeEditorData(initialData)); setSelectedSizeSegment(predominantSizeSegment(initialData.variant_rows, options.sizes)); setPrimaryPreview(editor.currentPrimaryUrl ?? null); }, [editor.currentPrimaryUrl, initialData, options.sizes, setData]);
    useEffect(() => () => { Object.values(galleryPreviewUrls.current).forEach((url) => URL.revokeObjectURL(url)); }, []);
    useEffect(() => { if (!data.imagen_principal) { const currentPrimary = data.existing_images.find((image) => !image.remove && image.pri_igp); setPrimaryPreview(currentPrimary?.url_igp ?? preparedImageUrl(data.ai_image_tokens[0]) ?? (data.existing_images.length ? null : editor.currentPrimaryUrl ?? null)); return; } const objectUrl = URL.createObjectURL(data.imagen_principal); setPrimaryPreview(objectUrl); return () => URL.revokeObjectURL(objectUrl); }, [data.existing_images, data.imagen_principal, data.ai_image_tokens, editor.currentPrimaryUrl]);
    useEffect(() => { if (mode !== 'create' || data.cod_temporadas_pre) return; const permanentSeason = optionSets.seasons.find((option) => normalizeText(option.label).startsWith('PERMANENTE')); if (permanentSeason) setData('cod_temporadas_pre', permanentSeason.value); }, [data.cod_temporadas_pre, mode, optionSets.seasons, setData]);
    useEffect(() => { if (availableSuggestedSizes.length !== 1) return; const firstIndex = data.variant_rows.findIndex((row) => !row.remove); if (firstIndex === -1 || data.variant_rows[firstIndex].cod_tallas_prv) return; setData('variant_rows', data.variant_rows.map((row, rowIndex) => rowIndex === firstIndex ? { ...row, cod_tallas_prv: availableSuggestedSizes[0].value } : row)); }, [availableSuggestedSizes, data.variant_rows, setData]);
    useEffect(() => { setPickedColorSample(null); setColorInsight(null); }, [primaryPreview]);
    useEffect(() => { if (steps.some((step) => step.id === currentStep)) return; setCurrentStep(steps[steps.length - 1]?.id ?? 'base'); }, [currentStep, steps]);
    useEffect(() => { setStepError(null); }, [currentStep, data.cod_categorias_pre, data.cod_marcas_pre, data.cod_temporadas_pre, data.gen_pre, data.imagen_principal, data.nom_pre, data.usa_variantes, data.variant_rows, data.patron_ids, data.textura_ids, data.material_rows]);

    const setAdditionalColor = (index: number, colorId: string, selected: boolean) => {
        setData('variant_rows', data.variant_rows.map((row, rowIndex) => rowIndex !== index ? row : {
            ...row,
            colores_adicionales: selected ? [...row.colores_adicionales, colorId] : row.colores_adicionales.filter((id) => id !== colorId),
        }));
    };
    const toggleAttribute = (field: 'patron_ids' | 'textura_ids', id: string) => {
        setData(field, data[field].includes(id) ? data[field].filter((value) => value !== id) : [...data[field], id]);
    };
    const knownMaterialTotal = data.material_rows.reduce((total, row) => total + (Number(row.porcentaje) || 0), 0);
    const materialPercentagesMissing = data.material_rows.some((row) => !row.porcentaje.trim());
    const attributeIssue = () => {
        const values = data.material_rows.map((row) => row.porcentaje.trim()).filter(Boolean);
        if (data.material_rows.some((row) => !row.cod_material)) return 'Selecciona el material de cada fila para continuar.';
        if (new Set(data.material_rows.map((row) => row.cod_material)).size !== data.material_rows.length) return 'No repitas el mismo material.';
        if (values.some((value) => !/^\d{1,3}(\.\d{1,2})?$/.test(value) || Number(value) <= 0 || Number(value) > 100)) return 'Cada porcentaje debe estar entre 0 y 100 con hasta dos decimales.';
        if (values.reduce((sum, value) => sum + Math.round(Number(value) * 100), 0) > 10000) return 'Los porcentajes conocidos de materiales no pueden superar el 100 %.';
        return null;
    };
    const normalizeVariantValue = (field: keyof ProductVariantRow, value: string | boolean) =>
        typeof value === 'string' && field === 'sku_prv'
            ? normalizeText(value).replace(/[^A-Z0-9_-]/g, '')
            : typeof value === 'string' && field === 'cob_prv'
            ? normalizeText(value).replace(/[^A-Z0-9-]/g, '')
            : value;
    const commercialVariantSeed = () => activeVariantRows[0] ?? data.variant_rows.find((row) => row.cod_colores_prv) ?? createVariantRow(summary.price);
    const createVariantRowFromSeed = (seed = commercialVariantSeed(), overrides: Partial<ProductVariantRow> = {}) => ({
        ...createVariantRow(seed.pve_prv || summary.price, seed.ubi_inv || 'TIENDA'),
        cod_colores_prv: seed.cod_colores_prv,
        tip_pal_prv: seed.tip_pal_prv,
        colores_adicionales: [...seed.colores_adicionales],
        pve_prv: seed.pve_prv || summary.price,
        cos_prv: seed.cos_prv,
        stock_inicial: seed.stock_inicial || '1',
        est_prv: seed.est_prv,
        ...overrides,
    });
    const updateVariantRow = (index: number, field: keyof ProductVariantRow, value: string | boolean) => {
        const normalizedValue = normalizeVariantValue(field, value);
        if (index === -1) {
            setData('variant_rows', [...data.variant_rows, { ...createVariantRowFromSeed(), [field]: normalizedValue }]);
            return;
        }

        setData('variant_rows', data.variant_rows.map((row, rowIndex) => rowIndex === index ? { ...row, [field]: normalizedValue, ...(field === 'cod_colores_prv' ? { colores_adicionales: row.colores_adicionales.filter((id) => id !== normalizedValue) } : {}) } : row));
    };
    const updateInlineVariantTarget = (target: Extract<InlineTarget, { type: 'variant' }>, value: string) => {
        const targetIndex = target.rowIndex >= 0 && !data.variant_rows[target.rowIndex]?.remove ? target.rowIndex : firstActiveVariantIndex;
        updateVariantRow(targetIndex, target.field, value);
    };
    const addVariantRow = () => {
        const seed = commercialVariantSeed();
        setData('variant_rows', [...data.variant_rows, createVariantRowFromSeed(seed, { cod_tallas_prv: '', cob_prv: '', stock_inicial: '1' })]);
    };
    const removeVariantRow = (index: number) => { const row = data.variant_rows[index]; if (!row) return; if (row.id) { updateVariantRow(index, 'remove', true); return; } if (activeVariantRows.length <= 1) return; setData('variant_rows', data.variant_rows.filter((_, rowIndex) => rowIndex !== index)); };
    const restoreVariantRow = (index: number) => updateVariantRow(index, 'remove', false);
    const toggleVariants = (enabled: boolean) => {
        const seed = commercialVariantSeed();
        if (enabled) {
            const nextRows = data.variant_rows.map((row) => (!row.remove && seed.cod_colores_prv && !row.cod_colores_prv ? { ...row, cod_colores_prv: seed.cod_colores_prv } : row));
            const rowsWithActiveVariant = nextRows.some((row) => !row.remove) ? nextRows : [createVariantRowFromSeed(seed), ...nextRows];
            setData((current) => ({ ...current, usa_variantes: true, variant_rows: rowsWithActiveVariant }));
            return;
        }
        let keptOne = false;
        const nextRows = data.variant_rows.map((row) => { if (row.remove) return row; if (!keptOne) { keptOne = true; return row; } return row.id ? { ...row, remove: true } : null; }).filter((row): row is ProductVariantRow => Boolean(row));
        const rowsWithUniqueVariant = nextRows.some((row) => !row.remove) ? nextRows : [createVariantRowFromSeed(seed), ...nextRows];
        setData((current) => ({ ...current, usa_variantes: false, variant_rows: rowsWithUniqueVariant }));
    };
    const applySuggestedSizes = () => { if (availableSuggestedSizes.length === 0) return; const seed = commercialVariantSeed(); const preservedRows = data.variant_rows.filter((row) => row.id && row.remove); const suggestedRows = availableSuggestedSizes.map((option, index) => createVariantRowFromSeed(seed, { cod_tallas_prv: option.value, cob_prv: index === 0 ? seed.cob_prv : '', stock_inicial: index === 0 ? seed.stock_inicial || '1' : '1', est_prv: true, remove: false })); setData('variant_rows', [...suggestedRows, ...preservedRows]); };
    const nextImageOrder = () => String(Math.max(1, ...data.existing_images.filter((image) => !image.remove).map((image) => Number(image.ord_igp || 0) + 1), ...data.gallery_images.map((image) => Number(image.ord_igp || 0) + 1)));
    const addGalleryRow = () => setData('gallery_images', [...data.gallery_images, createGalleryRow(nextImageOrder())]);
    const updateGalleryRow = (index: number, field: keyof NewGalleryImageRow, value: string | boolean | File | null) => setData('gallery_images', data.gallery_images.map((row, rowIndex) => rowIndex === index ? { ...row, [field]: value } : row));
    const updateGalleryFile = (index: number, file: File | null) => { const currentUrl = galleryPreviewUrls.current[index]; if (currentUrl) { URL.revokeObjectURL(currentUrl); delete galleryPreviewUrls.current[index]; } if (file) galleryPreviewUrls.current[index] = URL.createObjectURL(file); updateGalleryRow(index, 'archivo', file); };
    const removeGalleryRow = (index: number) => { const currentUrl = galleryPreviewUrls.current[index]; if (currentUrl) { URL.revokeObjectURL(currentUrl); delete galleryPreviewUrls.current[index]; } galleryPreviewUrls.current = Object.fromEntries(Object.entries(galleryPreviewUrls.current).map(([key, url]) => { const numericKey = Number(key); if (numericKey > index) return [String(numericKey - 1), url]; return [key, url]; })); setData('gallery_images', data.gallery_images.filter((_, rowIndex) => rowIndex !== index)); };
    const rebalancePrimary = (images: ExistingImageRow[]) => { const activeImages = images.filter((image) => !image.remove); if (activeImages.length === 0 || activeImages.some((image) => image.pri_igp)) return images; const nextPrimaryId = activeImages[0].id; return images.map((image) => ({ ...image, pri_igp: !image.remove && image.id === nextPrimaryId })); };
    const setExistingPrimary = (index: number) => { setData('imagen_principal', null); setData('existing_images', data.existing_images.map((image, imageIndex) => ({ ...image, pri_igp: imageIndex === index && !image.remove }))); };
    const toggleExistingImageRemoval = (index: number) => { const nextImages = data.existing_images.map((image, imageIndex) => imageIndex === index ? { ...image, remove: !image.remove } : image); setData('existing_images', rebalancePrimary(nextImages)); };
    const openInlineCreate = (resource: InlineCatalogKey, target: InlineTarget, seed = '', overrides?: Partial<InlineForm>) => { const defaults = inlineDefaults(resource, seed); const nextForm = { ...defaults, ...overrides }; if (resource === 'colors') { const hex = normalizeHex(nextForm.hex) ?? defaults.hex; const family = inferColorFamily(hex, colorFamilyOptions); nextForm.hex = hex; nextForm.family = nextForm.family || family?.value || familyFromHex(hex); nextForm.name = suggestedColorName(hex, normalizeText(nextForm.family)); } setInlineDrawer({ resource, target, seed }); setInlineForm(nextForm); setInlineErrors({}); setInlineMessage(null); };
    const closeInlineCreate = () => { setInlineDrawer(null); setInlineErrors({}); setInlineMessage(null); };
    const handleColorSamplePick = (sample: PickedColorSample) => { const family = inferColorFamily(sample.hex, colorFamilyOptions); const suggestedColor = closestColorOption(optionSets.colors, sample.hex, family?.label); setPickedColorSample(sample); setColorInsight({ hex: sample.hex, family, suggestedColor }); };
    const applySuggestedColor = () => { if (!colorInsight?.suggestedColor) return; updateVariantRow(firstActiveVariantIndex, 'cod_colores_prv', colorInsight.suggestedColor.value); };
    const createColorFromSample = () => { if (!colorInsight) return; openInlineCreate('colors', { type: 'variant', field: 'cod_colores_prv', rowIndex: Math.max(firstActiveVariantIndex, 0) }, colorInsight.suggestedColor?.label ?? colorInsight.family?.label ?? '', { family: colorInsight.family?.value ?? familyFromHex(colorInsight.hex), hex: colorInsight.hex }); };
    const issueForStep = (stepId: ProductStepId) => {
        const firstRow = activeVariantRows[0] ?? null;

        if (stepId === 'base') {
            const missing = [];
            if (!data.nom_pre.trim()) missing.push('nombre');
            if (!data.cod_categorias_pre) missing.push('categoría');
            if (!data.cod_marcas_pre) missing.push('marca');
            if (!data.cod_temporadas_pre) missing.push('temporada');
            if (!data.gen_pre) missing.push('género');

            return missing.length > 0 ? `Completa ${missing.join(', ')} para continuar.` : attributeIssue();
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
    const goNext = () => { const issue = issueForStep(currentStep); if (issue) { if (currentStep === 'base' && attributeIssue()) setMaterialAttempted(true); setStepError(issue); return; } const currentIndex = steps.findIndex((step) => step.id === currentStep); const nextStep = steps[currentIndex + 1]; if (nextStep) setCurrentStep(nextStep.id); };
    const goBack = () => { const currentIndex = steps.findIndex((step) => step.id === currentStep); const previousStep = steps[currentIndex - 1]; if (previousStep) setCurrentStep(previousStep.id); };
    const submitForm = (overrides: Partial<ProductEditorForm> = {}) => { const nextValues = { ...data, ...overrides }; const hasFiles = valuesHasFiles(nextValues); transform(() => ({ ...nextValues, prb_pre: resolvedBasePrice, gallery_images: nextValues.gallery_images.map((image) => ({ ...image, pri_igp: false })), ...(submit.method === 'put' && hasFiles ? { _method: 'put' } : {}) })); const requestOptions = { preserveScroll: true, forceFormData: hasFiles }; if (submit.method === 'put' && !hasFiles) { put(submit.action, requestOptions); return; } if (submit.method === 'put' && hasFiles) { post(submit.action, requestOptions); return; } post(submit.action, requestOptions); };
    const handleSubmit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); const issue = validateBeforeSubmit(); if (issue) { if (issue.stepId === 'base' && attributeIssue()) setMaterialAttempted(true); setCurrentStep(issue.stepId); setStepError(issue.message); return; } submitForm(); };
    const handleStateAction = () => { if (!stateAction) return; if (stateAction.confirm && !window.confirm(stateAction.confirm)) return; submitForm({ est_pre: stateAction.value === '1' }); };
    const completedSteps: Record<ProductStepId, boolean> = { base: !issueForStep('base'), image: !issueForStep('image'), type: true, commercial: !issueForStep('commercial'), variants: !data.usa_variantes || !issueForStep('variants'), review: false };
    const serverErrorCount = Object.keys(errors).length;
    const submitInlineCreate = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!inlineDrawer) return;

        const resourcePath = { categories: 'categorias', brands: 'marcas', colors: 'colores', sizes: 'tallas', seasons: 'temporadas' }[inlineDrawer.resource];
        const selectedFamily = inlineDrawer.resource === 'colors' ? inlineColorFamily : inlineForm.family || defaultColorFamily(inlineForm.name);
        const payload = inlineDrawer.resource === 'categories'
            ? { nom_cat: inlineForm.name, est_cat: true }
            : inlineDrawer.resource === 'brands'
            ? { nom_mar: inlineForm.name, est_mar: true }
            : inlineDrawer.resource === 'colors'
            ? { hex_col: inlineColorHex, fam_col: selectedFamily, est_col: true }
            : inlineDrawer.resource === 'sizes'
            ? { nom_tal: inlineForm.name, seg_tal: sizeProfile.segment, sis_tal: sizeProfile.system, gru_tal: sizeProfile.group, ord_tal: inlineForm.order || String(suggestSizeOrder(inlineForm.name)), est_tal: true }
            : { nom_tem: inlineForm.name, cod_tem: inlineForm.code || defaultSeasonCode(inlineForm.name), fec_ini_tem: inlineForm.startDate, fec_fin_tem: inlineForm.endDate, est_tem: true };

        setInlineProcessing(true);
        setInlineErrors({});
        setInlineMessage(null);

        try {
            const response = await axios.post(`/superadmin/catalogo/${resourcePath}/inline`, payload);
            const option = response.data.option as SuperadminFormFieldOption;

            if (inlineDrawer.resource === 'categories') {
                setOptionSets((current) => ({ ...current, categories: sortOptions('categories', [...current.categories, option]) }));
                setCategoryProfiles((current) => ({ ...current, [option.value]: { segment: option.segment ?? 'ADULTO', system: option.system, group: option.group } }));
            }
            if (inlineDrawer.resource === 'brands') setOptionSets((current) => ({ ...current, brands: sortOptions('brands', [...current.brands, option]) }));
            if (inlineDrawer.resource === 'colors') {
                setOptionSets((current) => ({ ...current, colors: sortOptions('colors', [...current.colors.filter((item) => item.value !== option.value), option]) }));
                const familyValue = option.group ?? selectedFamily;
                if (!colorFamilyOptions.some((family) => family.value === familyValue)) setColorFamilyOptions((current) => sortOptions('colors', [...current, { label: familyValue, value: familyValue, swatch: familyPalette[normalizeText(familyValue)] }]));
            }
            if (inlineDrawer.resource === 'sizes') setOptionSets((current) => ({ ...current, sizes: sortOptions('sizes', [...current.sizes, option]) }));
            if (inlineDrawer.resource === 'seasons') setOptionSets((current) => ({ ...current, seasons: sortOptions('seasons', [...current.seasons, option]) }));
            if (inlineDrawer.target.type === 'product') setData(inlineDrawer.target.field, option.value);
            else updateInlineVariantTarget(inlineDrawer.target, option.value);

            setInlineMessage(response.data.message ?? (response.data.reused ? 'Color existente encontrado.' : 'Registro creado correctamente.'));
            closeInlineCreate();
        } catch (error) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                setInlineErrors((error.response.data.errors ?? {}) as Record<string, string>);
                return;
            }
            setInlineMessage(inlineDrawer.resource === 'colors' ? 'No se pudo guardar el nuevo color.' : 'No se pudo crear el registro en este momento.');
        } finally {
            setInlineProcessing(false);
        }
    };

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
                <GarmentIntelligentAssistant
                    key={String(page.props.auth.user?.id ?? 'anonymous') + ':' + submit.action}
                    feedbackStorageKey={String(page.props.auth.user?.id ?? 'anonymous') + ':' + submit.action}
                    form={data}
                    catalogs={{ categories: optionSets.categories, brands: optionSets.brands, colors: optionSets.colors, sizes: optionSets.sizes, patterns: options.patterns, categoryProfiles, sizeSegment: selectedSizeSegment }}
                    primaryPreview={primaryPreview}
                    updateForm={(updater) => setData(updater)}
                />
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

                                <label className="space-y-2 md:col-span-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Modelo / código de proveedor</span>
                                    <input value={data.modelo_pre} onChange={(event) => setData('modelo_pre', event.target.value)} className="sa-input w-full" maxLength={150} placeholder="Modelo visible en etiqueta o catálogo del proveedor" />
                                    <p className="text-sm text-stone-400">Referencia opcional de la prenda. El SKU de Mati se genera por separado.</p>
                                    {errors.modelo_pre && <p className="text-sm text-rose-300">{errors.modelo_pre}</p>}
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

                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Segmento de talla</span>
                                    <select value={selectedSizeSegment} onChange={(event) => setSelectedSizeSegment(normalizeText(event.target.value || 'ADULTO'))} className="sa-input w-full">
                                        {sizeSegmentOptions.map((option) => <option key={`size-segment-${option.value}`} value={option.value}>{option.label}</option>)}
                                    </select>
                                    <p className="text-sm leading-6 text-stone-400">Define si la prenda usará tallas de adulto, infantil o bebé.</p>
                                </label>

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

                            <div className="mt-6 grid min-w-0 gap-5 lg:grid-cols-2">
                                <VisualAttributePicker title="Patrones" kind="pattern" items={options.patterns} selected={data.patron_ids} onToggle={(id) => toggleAttribute('patron_ids', id)} error={errors.patron_ids} />
                                <VisualAttributePicker title="Texturas" kind="texture" items={options.textures} selected={data.textura_ids} onToggle={(id) => toggleAttribute('textura_ids', id)} error={errors.textura_ids} />
                            </div>
                            <div className="mt-5 rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4 sm:p-5">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div><p className="text-sm text-white">Materiales y composición</p><p className="mt-1 text-sm text-stone-400">Añade los materiales conocidos. El porcentaje es opcional.</p></div>
                                    {data.material_rows.length > 0 && <div className="rounded-xl border border-white/10 px-3 py-2 text-xs text-stone-300">{materialPercentagesMissing && knownMaterialTotal === 0 ? 'Composición porcentual no especificada' : `Composición conocida: ${Number(knownMaterialTotal.toFixed(2))} %`}</div>}
                                </div>
                                <div className="mt-4 space-y-2">
                                    {data.material_rows.map((row, index) => <div key={index} className="grid min-w-0 gap-3 rounded-xl border border-white/10 bg-[#0b1118] p-3 sm:grid-cols-[minmax(0,1fr)_8rem_auto] sm:items-center">
                                        <div className="flex min-w-0 items-center gap-3"><Layers3 aria-hidden="true" size={18} className="shrink-0 text-[#bfa786]" /><select aria-label={`Material ${index + 1}`} className="sa-input min-w-0 w-full" value={row.cod_material} onChange={(event) => { setMaterialTouched(true); setData('material_rows', data.material_rows.map((item, rowIndex) => rowIndex === index ? { ...item, cod_material: event.target.value } : item)); }}>
                                            <option value="">Selecciona material</option>
                                            {options.materials.map((item) => <option key={item.value} value={item.value} disabled={item.active === false && row.cod_material !== item.value}>{item.label}</option>)}
                                        </select></div>
                                        <div className="flex items-center gap-2"><input aria-label={`Porcentaje de material ${index + 1}`} type="number" min="0.01" max="100" step="0.01" placeholder="—" className="sa-input min-w-0 w-full" value={row.porcentaje} onChange={(event) => { setMaterialTouched(true); setData('material_rows', data.material_rows.map((item, rowIndex) => rowIndex === index ? { ...item, porcentaje: event.target.value } : item)); }} /><span className="text-sm text-stone-400">%</span></div>
                                        <button type="button" aria-label={`Quitar material ${index + 1}`} className="sa-button-ghost inline-flex h-10 w-10 items-center justify-center p-0" onClick={() => { setMaterialTouched(true); setData('material_rows', data.material_rows.filter((_, rowIndex) => rowIndex !== index)); }}><X size={16} /></button>
                                    </div>)}
                                </div>
                                <button type="button" className="sa-button-ghost mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm" onClick={() => setData('material_rows', [...data.material_rows, { cod_material: '', porcentaje: '' }])}><Plus size={14} />Agregar material</button>
                                {data.material_rows.length > 0 && materialPercentagesMissing && knownMaterialTotal > 0 && <p className="mt-3 text-xs text-stone-400">Hay materiales sin porcentaje especificado.</p>}
                                {(materialTouched || materialAttempted) && attributeIssue() && (materialAttempted || attributeIssue() !== 'Selecciona el material de cada fila para continuar.') && <p className="mt-3 text-sm text-rose-300">{attributeIssue()}</p>}
                                {errors.material_rows && <p className="mt-2 text-sm text-rose-300">{errors.material_rows}</p>}
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
                                        <div className="inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400"><Palette size={12} />Color sugerido detectado</div>
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
                                                    <p className="mt-2 text-sm leading-6 text-stone-400">Esta muestra se guarda al aplicarla o al crear un color desde la muestra.</p>
                                                </div>
                                                <div className="flex flex-col gap-3">
                                                    {colorInsight.suggestedColor && (
                                                        <button type="button" onClick={applySuggestedColor} className="sa-button-secondary px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                                            {isSuggestedColorApplied ? 'Color aplicado a la configuración comercial' : 'Aplicar color a la prenda'}
                                                        </button>
                                                    )}
                                                    <button type="button" onClick={createColorFromSample} className="sa-button-ghost px-4 py-3 text-[0.72rem] uppercase tracking-[0.18em]">
                                                        Crear color desde esta muestra
                                                    </button>
                                                </div>
                                                {selectedCommercialColor && (
                                                    <div className="rounded-[1.1rem] border border-white/8 bg-[#0a1016] p-3">
                                                        <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Color aplicado a la configuración comercial</p>
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
                                <p className="mt-2 text-sm text-stone-400">{suggestedSizeLabels.join(', ')}</p>
                                {sizeFallbackMessage && <p className="mt-3 text-sm text-amber-200">{sizeFallbackMessage}</p>}
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
                                    <div className="rounded-[1.25rem] border border-white/8 bg-[#0b1118] p-4">
                                        <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Características de la variante</p>
                                        <div className="mt-4 grid min-w-0 gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                                            <PaletteEditor row={activeVariantRows[0] ?? createVariantRow()} colors={orderedColorOptions} referenceError={errors[`variant_rows.${firstActiveVariantIndex}.cod_colores_prv`]} paletteError={errors[`variant_rows.${firstActiveVariantIndex}.colores_adicionales`]} hint={colorHint} onTypeChange={(value) => updateVariantRow(firstActiveVariantIndex, 'tip_pal_prv', value)} onReferenceChange={(value) => updateVariantRow(firstActiveVariantIndex, 'cod_colores_prv', value)} onCreateColor={(seed) => openInlineCreate('colors', { type: 'variant', field: 'cod_colores_prv', rowIndex: Math.max(firstActiveVariantIndex, 0) }, seed)} onAdditionalToggle={(value, selected) => setAdditionalColor(firstActiveVariantIndex, value, selected)} />
                                            <div className="min-w-0">
                                        <SuperadminComboboxField
                                            label="Talla"
                                            value={activeVariantRows[0]?.cod_tallas_prv ?? ''}
                                            options={orderedSizeOptions}
                                            placeholder="Selecciona talla"
                                            searchPlaceholder="Buscar talla"
                                            error={errors['variant_rows.0.cod_tallas_prv']}
                                            hint={sizeFallbackMessage ?? `Sugerencia principal: ${suggestedSizeLabels.join(', ')}`}
                                            createLabel="OTRA TALLA"
                                            createMode="list"
                                            onChange={(value) => updateVariantRow(firstActiveVariantIndex, 'cod_tallas_prv', value)}
                                            onCreate={(seed) => openInlineCreate('sizes', { type: 'variant', field: 'cod_tallas_prv', rowIndex: Math.max(firstActiveVariantIndex, 0) }, seed)}
                                        />

                                            </div>
                                        </div>
                                    </div>
                                    <div className="mt-5 rounded-[1.25rem] border border-white/8 bg-[#0b1118] p-4">
                                        <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Inventario y precio</p>
                                        <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                                                <p className="mt-2 text-sm text-stone-400">{colorInsight?.family?.label ? `Familia sugerida: ${colorInsight.family.label}` : 'La sugerencia aparecerá cuando cargues una portada.'}</p>
                                            </div>
                                            <div className="rounded-[1.2rem] border border-white/8 bg-[#0b1118] p-4">
                                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Perfil de talla</p>
                                                <p className="mt-2 text-sm text-white">{sizeProfile.label}</p>
                                                <p className="mt-2 text-sm text-stone-400">{suggestedSizeLabels.join(', ')}</p>
                                                {sizeFallbackMessage && <p className="mt-3 text-sm text-amber-200">{sizeFallbackMessage}</p>}
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
                                                <div className="mt-4 rounded-[1.25rem] border border-white/8 bg-[#0b1118] p-4">
                                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Características de la variante</p>
                                                    <div className="mt-4 grid min-w-0 gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                                                        <PaletteEditor row={row} colors={orderedColorOptions} referenceError={errors[`variant_rows.${index}.cod_colores_prv`]} paletteError={errors[`variant_rows.${index}.colores_adicionales`]} hint={index === 0 ? colorHint : undefined} onTypeChange={(value) => updateVariantRow(index, 'tip_pal_prv', value)} onReferenceChange={(value) => updateVariantRow(index, 'cod_colores_prv', value)} onCreateColor={(seed) => openInlineCreate('colors', { type: 'variant', field: 'cod_colores_prv', rowIndex: index }, seed)} onAdditionalToggle={(value, selected) => setAdditionalColor(index, value, selected)} />
                                                        <div className="min-w-0">
                                                    <SuperadminComboboxField
                                                        label="Talla"
                                                        value={row.cod_tallas_prv}
                                                        options={orderedSizeOptions}
                                                        placeholder="Selecciona talla"
                                                        searchPlaceholder="Buscar talla"
                                                        error={errors[`variant_rows.${index}.cod_tallas_prv`]}
                                                        hint={index === 0 ? (sizeFallbackMessage ?? `Sugerencia principal: ${suggestedSizeLabels.join(', ')}`) : undefined}
                                                        createLabel="OTRA TALLA"
                                                        createMode="list"
                                                        onChange={(value) => updateVariantRow(index, 'cod_tallas_prv', value)}
                                                        onCreate={(seed) => openInlineCreate('sizes', { type: 'variant', field: 'cod_tallas_prv', rowIndex: index }, seed)}
                                                    />

                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="mt-4 rounded-[1.25rem] border border-white/8 bg-[#0b1118] p-4">
                                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Inventario y precio</p>
                                                    <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                                <p className="text-[0.72rem] uppercase tracking-[0.18em] text-stone-400">Paso {data.usa_variantes ? 6 : 5}</p>
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
                        {inlineDrawer.resource !== 'colors' && (
                        <label className="space-y-2">
                            <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Nombre</span>
                            <input value={inlineForm.name} onChange={(event) => setInlineForm((current) => ({ ...current, name: event.target.value.toUpperCase(), order: inlineDrawer.resource === 'sizes' ? String(suggestSizeOrder(event.target.value)) : current.order, code: inlineDrawer.resource === 'seasons' && !current.code ? defaultSeasonCode(event.target.value) : current.code }))} className="sa-input w-full" />
                            {inlineErrors.nom_cat && <p className="text-sm text-rose-300">{inlineErrors.nom_cat}</p>}
                            {inlineErrors.nom_mar && <p className="text-sm text-rose-300">{inlineErrors.nom_mar}</p>}
                            {inlineErrors.nom_col && <p className="text-sm text-rose-300">{inlineErrors.nom_col}</p>}
                            {inlineErrors.nom_tal && <p className="text-sm text-rose-300">{inlineErrors.nom_tal}</p>}
                            {inlineErrors.nom_tem && <p className="text-sm text-rose-300">{inlineErrors.nom_tem}</p>}
                        </label>
                        )}
                        {inlineDrawer.resource === 'seasons' && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Código corto</span>
                                    <input value={inlineForm.code} onChange={(event) => setInlineForm((current) => ({ ...current, code: event.target.value.toUpperCase() }))} className="sa-input w-full" placeholder="Ej.: OI, PV, PER" />
                                    {inlineErrors.cod_tem && <p className="text-sm text-rose-300">{inlineErrors.cod_tem}</p>}
                                </label>
                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Fecha de inicio</span>
                                    <input type="date" value={inlineForm.startDate} onChange={(event) => setInlineForm((current) => ({ ...current, startDate: event.target.value }))} className="sa-input w-full" />
                                    {inlineErrors.fec_ini_tem && <p className="text-sm text-rose-300">{inlineErrors.fec_ini_tem}</p>}
                                </label>
                                <label className="space-y-2 sm:col-span-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Fecha de fin</span>
                                    <input type="date" value={inlineForm.endDate} onChange={(event) => setInlineForm((current) => ({ ...current, endDate: event.target.value }))} className="sa-input w-full" />
                                    {inlineErrors.fec_fin_tem && <p className="text-sm text-rose-300">{inlineErrors.fec_fin_tem}</p>}
                                </label>
                            </div>
                        )}
                        {inlineDrawer.resource === 'colors' && (
                            <>
                                {primaryPreview && (
                                    <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-3">
                                        <ProductColorPicker
                                            src={primaryPreview}
                                            selectedSample={pickedColorSample}
                                            onPick={(sample) => {
                                                handleColorSamplePick(sample);
                                                const family = inferColorFamily(sample.hex, colorFamilyOptions);
                                                setInlineForm((current) => ({
                                                    ...current,
                                                    hex: sample.hex,
                                                    family: family?.value ?? familyFromHex(sample.hex),
                                                    name: suggestedColorName(sample.hex, normalizeText(family?.value ?? familyFromHex(sample.hex))),
                                                }));
                                            }}
                                        />
                                    </div>
                                )}
                                <label className="space-y-2">
                                    <span className="inline-flex items-center gap-2 text-[0.68rem] uppercase tracking-[0.18em] text-stone-400"><Palette size={12} />Color exacto</span>
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="color"
                                            value={inlineColorHex}
                                            onChange={(event) => {
                                                const hex = event.target.value.toUpperCase();
                                                const family = inferColorFamily(hex, colorFamilyOptions);
                                                setInlineForm((current) => ({
                                                    ...current,
                                                    hex,
                                                    family: family?.value ?? familyFromHex(hex),
                                                    name: suggestedColorName(hex, normalizeText(family?.value ?? familyFromHex(hex))),
                                                }));
                                            }}
                                            className="h-11 w-16 rounded-xl border border-white/10 bg-[#0d141d]"
                                        />
                                        <div className="sa-input flex w-full items-center text-sm text-white">{inlineColorHex}</div>
                                    </div>
                                    {inlineErrors.hex_col && <p className="text-sm text-rose-300">{inlineErrors.hex_col}</p>}
                                </label>
                                <div className="rounded-[1.2rem] border border-white/8 bg-[#0d141d] p-4">
                                    <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">
                                        {inlineExistingColor ? 'Color existente encontrado' : 'Nuevo color detectado'}
                                    </p>
                                    <div className="mt-3 flex items-center gap-3">
                                        <span className="inline-flex h-12 w-12 rounded-full border border-white/15" style={{ backgroundColor: inlineColorHex }} />
                                        <div>
                                            <p className="text-sm text-white">{inlineExistingColor?.label ?? inlineColorName}</p>
                                            <p className="mt-1 text-sm text-stone-400">Familia: {inlineExistingColor?.group ?? inlineColorFamily}</p>
                                            <p className="mt-1 text-xs uppercase tracking-[0.14em] text-stone-500">Tono de familia: {familyPalette[normalizeText(inlineExistingColor?.group ?? inlineColorFamily)] ?? inlineColorHex}</p>
                                        </div>
                                    </div>
                                </div>
                                <label className="space-y-2">
                                    <span className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Familia de color</span>
                                    <select value={inlineColorFamily} onChange={(event) => setInlineForm((current) => ({ ...current, family: event.target.value, name: suggestedColorName(inlineColorHex, normalizeText(event.target.value)) }))} className="sa-input w-full">
                                        {colorFamilyOptions.map((option) => <option key={`${option.value}-family`} value={option.value}>{option.label}</option>)}
                                    </select>
                                    {inlineErrors.fam_col && <p className="text-sm text-rose-300">{inlineErrors.fam_col}</p>}
                                </label>
                            </>
                        )}
                        {inlineDrawer.resource === 'sizes' && (
                            <div className="rounded-[1.1rem] border border-white/8 bg-[#0d141d] p-4">
                                <p className="text-[0.68rem] uppercase tracking-[0.18em] text-stone-400">Perfil de talla</p>
                                <p className="mt-2 text-sm text-white">{sizeProfile.segment} / {sizeProfile.group} / {sizeProfile.system}</p>
                                <p className="mt-2 text-sm leading-6 text-stone-400">El orden se genera automáticamente.</p>
                            </div>
                        )}
                        <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button" onClick={closeInlineCreate} className="sa-button-ghost px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em]">Cancelar</button>
                            <button type="submit" disabled={inlineProcessing} className="sa-button-primary px-5 py-3 text-[0.72rem] uppercase tracking-[0.18em] disabled:cursor-not-allowed disabled:opacity-70">{inlineProcessing ? 'Guardando' : inlineDrawer.resource === 'colors' ? (inlineExistingColor ? 'Usar color existente' : 'Crear y usar color') : 'Crear y seleccionar'}</button>
                        </div>
                    </form>
                )}
            </SuperadminDrawer>
        </SuperadminLayout>
    );
}
