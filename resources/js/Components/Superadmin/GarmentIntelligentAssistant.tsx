import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import { Check, ExternalLink, Loader2, Search, Sparkles, X } from 'lucide-react';
import type { ProductEditorForm } from '@/types/productEditor';
import {
    aiApplicableSuggestions, aiFieldLabels, aiResultNotice, aiResultStatusLabel, aiReviewKey, aiWarningText, applyAiSuggestion, compatibleSuggestionBatch, currentSuggestionValue,
    existingAnalysisImageId, externalReferencePreviewUrl, preparedImageUrl,
    imageRightsAllowPublication, queueAiReview, readAiReviewQueue, safeExternalUrl, suggestionIssue, suggestionValue,
    variantSuggestionFields, visualSimilarityImageUrl, visualSimilarityScoreText, wouldReplaceValue, type AiCatalogs, type AiClassification, type AiReviewDecision, type AiSuggestion,
} from '@/lib/garmentAssistant';

type Warning = string | { code?: string; message?: string };
type InternalMatch = { id: string | number; title: string; sku?: string; score?: number; conflicts?: string[]; warnings?: Warning[]; exact_identifier?: boolean; href?: string };
type ExternalImage = { original_url: string; preview_url?: string; source?: string; width?: number; height?: number; fetched_at?: string; rights_status?: string; rights_evidence?: string | null; warning?: string };
type ExternalCandidate = {
    title: string; source?: string; source_id?: string; brand?: string; model_code?: string; gtin?: string;
    canonical_url?: string; images?: ExternalImage[]; final_score?: number; rights_status?: string; rights_evidence?: string | null; conflicts?: string[];
    warnings?: Warning[]; suggestions?: AiSuggestion[];
};
type PaletteColor = { hex: string; percentage: number; catalog_name?: string | null; confidence_score?: number; };
type ColorLevel = 'low' | 'medium' | 'high' | 'unknown';
type ColorTemperature = 'warm' | 'cool' | 'neutral' | 'warm_neutral' | 'cool_neutral' | 'unknown';
type ColorFamily = 'black' | 'white' | 'gray' | 'beige_cream' | 'brown' | 'red' | 'orange' | 'yellow' | 'green' | 'blue' | 'violet' | 'pink' | 'metallic' | 'multicolor';
type ColorimetryColor = {
    rgb: [number, number, number]; hex: string; lab: [number, number, number]; percentage: number;
    catalog_color_id: string | number | null; catalog_color_name: string | null; delta_e: number | null;
    confidence_score: number; evidence: Record<string, unknown>; family: ColorFamily | null; descriptive_name: string;
};
type Colorimetry = {
    status: 'completed' | 'partial' | 'insufficient_evidence'; foreground_confidence: number;
    commercial_color_status?: 'completed' | 'insufficient_evidence';
    evidence?: { target_garment_identity_verified?: boolean };
    primary_color: ColorimetryColor | null; secondary_colors: ColorimetryColor[]; accent_colors: ColorimetryColor[]; palette: ColorimetryColor[];
    temperature: ColorTemperature; lightness: ColorLevel; chroma: ColorLevel; saturation: ColorLevel; neutrality: ColorLevel;
    internal_contrast: ColorLevel; color_variety: 'monochrome' | 'bicolor' | 'multicolor' | 'unknown';
    dominant_family: ColorFamily | null; warnings: Warning[];
};
type Code = { symbology: string; raw_value: string; normalized_value: string; checksum_valid: boolean | null; contains_url?: boolean; warnings?: Warning[] };
type PreparedImage = { token: string; url: string; name: string };
type VisualSimilarity = {
    prenda_id: string | number; image_id: string | number | null; title: string; image_url: string | null; href: string;
    similarity_score: number | null; distance: number | null; possible_duplicate: boolean;
    duplicate_evidence?: string[] | Record<string, unknown> | null;
};
type VectorSearch = {
    status: 'completed' | 'empty' | 'unavailable' | 'incompatible' | 'no_embedding'; total_candidates?: number;
    dimension?: number; model_id?: string; model_revision?: string; pipeline_version?: string; metric?: string; exact?: boolean;
};
type AiResponse = {
    request_id: string; run_id?: string | number; status: string; suggestions?: AiSuggestion[];
    warnings?: Warning[]; abstentions?: Warning[]; human_review_required?: boolean;
    model?: { model_id?: string; model_revision?: string; pipeline_version?: string };
    timing_ms?: Record<string, number>;
    data: {
        internal_matches?: InternalMatch[]; external_search_allowed?: boolean;
        visual_similarities?: VisualSimilarity[]; vector_search?: VectorSearch;
        candidates?: ExternalCandidate[]; codes?: Code[]; duplicates?: InternalMatch[];
        raw_text?: string; model_code_candidate?: string | null; sku_candidate?: string | null;
        gtin_candidates?: string[]; brand_candidate?: { name?: string } | null; size_candidate?: { name?: string } | null;
        composition?: { material: string; percentage: number }[];
        quality?: { status?: string; width?: number; height?: number; blur_score?: number; contrast?: number; warnings?: Warning[] };
        colors?: { palette?: PaletteColor[]; warnings?: Warning[] };
        colorimetry?: Colorimetry;
        classification?: AiClassification;
        attributes?: { pattern?: { name?: string; similarity_score?: number; abstained?: boolean; warnings?: Warning[] } | null; abstained?: boolean; warnings?: Warning[] };
        warnings?: Warning[];
        image?: { width?: number; height?: number };
        prepared_image?: PreparedImage;
    };
};
type Result = { operation: string; response: AiResponse; analysisImageKey?: File | string | null };
type Review = AiReviewDecision;
type Props = { form: ProductEditorForm; catalogs: AiCatalogs; primaryPreview: string | null; feedbackStorageKey: string; updateForm: (updater: (current: ProductEditorForm) => ProductEditorForm) => void };
const labels: Record<string, string> = { analyze: 'Análisis de imagen', similar: 'Búsqueda de prendas similares', barcode: 'Código / QR', label: 'Etiqueta', match: 'Catálogo interno', 'external-search': 'Fuentes externas', 'prepare-publication': 'Preparación de imagen' };
const button = 'sa-button-secondary inline-flex items-center justify-center gap-2 px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50';
const box = 'rounded-xl border border-white/10 p-4';
function warningText(warning: Warning): string { return aiWarningText(warning); }
function reviewKey(suggestionId: string | number, index?: number): string { return aiReviewKey({ suggestion_id: suggestionId, variant_index: index }); }
const sourceLabels: Record<string, string> = { vision: 'Análisis visual', color_lab: 'Coincidencia de color', colorimetry: 'Coincidencia de color', garment_colorimetry: 'Coincidencia de color', label_ocr: 'Lectura de etiqueta', barcode: 'Lectura del código', external: 'Fuente externa' };
const rightsLabels: Record<string, string> = { owned: 'Propia', licensed: 'Con licencia', provider_authorized: 'Autorizada por proveedor', public_domain: 'Dominio público', unknown: 'Sin autorización confirmada', prohibited: 'Publicación prohibida' };
const temperatureLabels: Record<ColorTemperature, string> = { warm: 'Cálida', cool: 'Fría', neutral: 'Neutra', warm_neutral: 'Cálida / neutra', cool_neutral: 'Fría / neutra', unknown: 'Por determinar' };
const lightnessLabels: Record<ColorLevel, string> = { low: 'Oscura', medium: 'Media', high: 'Clara', unknown: 'Por determinar' };
const levelLabels: Record<ColorLevel, string> = { low: 'Baja', medium: 'Media', high: 'Alta', unknown: 'Por determinar' };
const contrastLabels: Record<ColorLevel, string> = { low: 'Bajo', medium: 'Medio', high: 'Alto', unknown: 'Por determinar' };
const varietyLabels: Record<Colorimetry['color_variety'], string> = { monochrome: 'Un color predominante', bicolor: 'Bicolor', multicolor: 'Multicolor', unknown: 'Por determinar' };
const familyLabels: Record<ColorFamily, string> = { black: 'Negros', white: 'Blancos', gray: 'Grises', beige_cream: 'Beige / crema', brown: 'Marrones', red: 'Rojos', orange: 'Naranjas', yellow: 'Amarillos', green: 'Verdes', blue: 'Azules', violet: 'Violetas', pink: 'Rosados', metallic: 'Metalizados', multicolor: 'Multicolor' };
function colorName(color: ColorimetryColor): string { return color.descriptive_name || (color.family && familyLabels[color.family]) || 'Tono observado'; }
function colorPercentage(value: number): string { return Number.isFinite(value) ? Math.round(Math.max(0, Math.min(1, value)) * 100) + ' % aprox.' : 'Proporción por determinar'; }
function ColorimetrySummary({ colorimetry, catalogs }: { colorimetry: Colorimetry; catalogs: AiCatalogs }) {
    const primary = colorimetry.primary_color;
    const palette = colorimetry.palette ?? [];
    const commercialVerified = colorimetry.commercial_color_status === 'completed' && colorimetry.evidence?.target_garment_identity_verified === true;
    const commercialColor = (color: ColorimetryColor) => !commercialVerified || color.catalog_color_id == null ? undefined
        : catalogs.colors.find((item) => item.value === String(color.catalog_color_id) && item.active !== false);
    const properties: [string, string | null | undefined][] = [
        ['Temperatura', temperatureLabels[colorimetry.temperature]], ['Luminosidad', lightnessLabels[colorimetry.lightness]],
        ['Saturación', levelLabels[colorimetry.saturation]], ['Contraste', contrastLabels[colorimetry.internal_contrast]],
        ['Variedad', varietyLabels[colorimetry.color_variety]],
        ['Familia de color', colorimetry.dominant_family && familyLabels[colorimetry.dominant_family]],
    ];
    return <section className={box} aria-label="Colorimetría de la prenda">
        <h3 className="text-sm text-white">Tonos observados en la fotografía</h3>
        {!commercialVerified && <p className="mt-2 text-sm text-amber-200">Evidencia insuficiente para color comercial. La paleta es informativa; elige el color manualmente.</p>}
        <div className="mt-3 rounded-lg bg-white/5 p-3">
            <p className="text-xs text-stone-400">Tono principal observado</p>
            <p className="mt-1 text-base text-stone-100">{primary ? colorName(primary) : palette.length ? 'Sin un único dominante fiable' : 'Por determinar'}</p>
            {primary && <p className="mt-1 text-xs text-stone-400">{commercialColor(primary) ? 'Color del catálogo: ' + commercialColor(primary)?.label : 'Sin coincidencia comercial fiable.'}</p>}
        </div>
        {palette.length > 0 ? <div className="mt-4">
            <p className="text-xs text-stone-400">Paleta</p>
            <div className="mt-2 flex flex-wrap gap-3">{palette.map((color, index) => {
                const catalog = commercialColor(color);
                const role = primary?.hex === color.hex ? 'Principal'
                    : colorimetry.accent_colors?.some((accent) => accent.hex === color.hex) ? 'Acento' : 'Secundario';
                return <div key={color.hex + ':' + index} className="flex items-center gap-2 rounded-lg bg-white/5 p-3">
                    <span className="h-8 w-8 shrink-0 rounded-full border border-white/20" style={{ backgroundColor: /^#[0-9a-f]{6}$/i.test(color.hex) ? color.hex : undefined }} aria-hidden="true" />
                    <div>
                        <p className="text-xs text-stone-200">{colorName(color)}</p>
                        <p className="mt-1 text-xs text-stone-400">{role} · {colorPercentage(color.percentage)}</p>
                        <p className="mt-1 text-xs text-stone-400">{catalog ? 'Catálogo: ' + catalog.label : 'Sin coincidencia comercial fiable'}</p>
                    </div>
                </div>;
            })}</div>
        </div> : <p className="mt-3 text-sm text-amber-200">No hay evidencia suficiente para obtener una paleta fiable. Prueba una foto de la prenda centrada, con fondo uniforme y luz natural indirecta.</p>}
        <dl className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{properties.map(([label, value]) => <div key={label}>
            <dt className="text-xs text-stone-400">{label}</dt><dd className="mt-1 text-sm text-stone-200">{value && value !== 'Por determinar' ? value : colorimetry.status === 'partial' ? 'Por revisar' : 'Por determinar'}</dd>
        </div>)}</dl>
        <p className="mt-3 text-xs leading-5 text-stone-400">Estimaciones de la fotografía. La luz y el fondo pueden alterar el tono observado; compáralo con la prenda. {commercialVerified ? 'Revisa las sugerencias antes de elegir el color del catálogo.' : 'La separación geométrica del fondo no identifica semánticamente la prenda objetivo.'}</p>
        {colorimetry.warnings?.map((warning, index) => <p key={index} className="mt-2 text-xs text-amber-200">{warningText(warning)}</p>)}
    </section>;
}
function scoreText(score?: number): string { return typeof score === 'number' && Number.isFinite(score) ? score.toFixed(3) + ' · puntuación de coincidencia' : 'Coincidencia sin puntuación'; }
function VisualSimilarityResults({ response }: { response: AiResponse }) {
    const items = (response.data.visual_similarities ?? []).filter((item, index, all) =>
        all.findIndex((candidate) => String(candidate.prenda_id) === String(item.prenda_id)) === index);
    const warnings = [...(response.warnings ?? []), ...(response.abstentions ?? []), ...(response.data.warnings ?? [])]
        .map(warningText).filter((warning, index, all) => all.indexOf(warning) === index);
    const emptyMessages: Record<VectorSearch['status'], string> = {
        completed: 'No se encontraron prendas comparables para esta fotografía.',
        empty: 'Todavía no hay prendas preparadas para comparar por fotografía.',
        unavailable: 'La búsqueda visual no está disponible en este momento. Puedes continuar manualmente.',
        incompatible: 'Las prendas disponibles todavía no se pueden comparar con esta fotografía.',
        no_embedding: 'No se pudo preparar esta fotografía para la comparación. Prueba otra foto o continúa manualmente.',
    };
    return <section className={box} aria-label="Prendas similares por fotografía">
        <h3 className="text-sm text-white">Prendas similares por fotografía</h3>
        <p className="mt-2 text-xs leading-5 text-stone-400">La puntuación mide parecido visual; no es un porcentaje de certeza ni confirma que sea la misma prenda. Revisa cada candidata antes de decidir.</p>
        {items.length ? <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{items.map((item) => {
            const imageUrl = visualSimilarityImageUrl(item.image_url);
            const href = /^\/superadmin\/catalogo\/prendas\/[1-9]\d*$/.test(item.href) ? item.href : null;
            return <article key={item.prenda_id} className="rounded-lg border border-white/10 bg-[#0a1016] p-3">
                {imageUrl ? <img src={imageUrl} alt={item.title} loading="lazy" className="h-36 w-full rounded-lg bg-white/5 object-contain" />
                    : <div className="flex h-36 items-center justify-center rounded-lg bg-white/5 text-xs text-stone-400">Sin imagen disponible</div>}
                <h4 className="mt-3 break-words text-sm text-white">{item.title}</h4>
                <p className="mt-2 text-xs text-stone-400">{visualSimilarityScoreText(item.similarity_score)}</p>
                {item.possible_duplicate && <p className="mt-2 text-xs text-amber-200">Posible duplicado: compara las fotos y los datos del catálogo.</p>}
                {href && <a href={href} target="_blank" rel="noopener noreferrer" className="mt-3 inline-flex items-center gap-1 text-xs text-[#bfa786]">Revisar prenda <ExternalLink size={12} aria-hidden="true" /></a>}
            </article>;
        })}</div> : <p className="mt-3 text-sm text-stone-300">{emptyMessages[response.data.vector_search?.status ?? 'completed'] || emptyMessages.unavailable}</p>}
        {warnings.map((warning, index) => <p key={index} className="mt-2 text-xs text-amber-200">{warning}</p>)}
    </section>;
}

export default function GarmentIntelligentAssistant({ form, catalogs, primaryPreview, feedbackStorageKey, updateForm }: Props) {
    const [capture, setCapture] = useState<{ barcode: File | null; label: File | null }>({ barcode: null, label: null });
    const [query, setQuery] = useState({ sku: '', gtin: '', brand: '', model_code: '', query: '' });
    const [results, setResults] = useState<Result[]>([]);
    const [busy, setBusy] = useState<string | null>(null);
    const [notice, setNotice] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [variantIndex, setVariantIndex] = useState(Math.max(0, form.variant_rows.findIndex((row) => !row.remove)));
    const [reviews, setReviews] = useState<Record<string, string>>({});
    const storageKey = 'mati-ai:pending-reviews:' + feedbackStorageKey;
    const [pendingReviews, setPendingReviews] = useState<Review[]>(() => {
        try { return readAiReviewQueue(window.sessionStorage.getItem(storageKey)); } catch { return []; }
    });
    const [sendingReviews, setSendingReviews] = useState(false);
    const pendingReviewsRef = useRef(pendingReviews);
    const sendingReviewsRef = useRef(false);
    const [replacement, setReplacement] = useState<{ suggestion: AiSuggestion; index: number; previous: string } | null>(null);
    const [checkedQuery, setCheckedQuery] = useState<string | null>(null);
    const [externalAllowed, setExternalAllowed] = useState(false);
    const [analysisRun, setAnalysisRun] = useState<string | null>(null);
    const [prepared, setPrepared] = useState<PreparedImage[]>([]);
    const [references, setReferences] = useState<string[]>([]);
    const abort = useRef<AbortController | null>(null);
    const formRef = useRef(form);
    formRef.current = form;
    useEffect(() => () => abort.current?.abort(), []);
    const existingImageId = existingAnalysisImageId(form);
    const analysisImageKey = form.imagen_principal ?? existingImageId;
    const analysisImageRef = useRef(analysisImageKey);
    analysisImageRef.current = analysisImageKey;
    useEffect(() => { setAnalysisRun(null); setCheckedQuery(null); }, [analysisImageKey]);
    useEffect(() => {
        if (!form.variant_rows[variantIndex] || form.variant_rows[variantIndex].remove) setVariantIndex(Math.max(0, form.variant_rows.findIndex((row) => !row.remove)));
    }, [form.variant_rows, variantIndex]);
    const searchPayload = {
        ...query, brand: query.brand || catalogs.brands.find((item) => item.value === form.cod_marcas_pre)?.label || '',
        model_code: query.model_code || form.modelo_pre || '', category_id: form.cod_categorias_pre || null,
        color_id: form.variant_rows[variantIndex]?.cod_colores_prv || null, analysis_run_id: analysisRun,
    };
    const queryKey = JSON.stringify(searchPayload);
    const latestInternal = [...results].reverse().find((result) => result.response.data.internal_matches)?.response.data.internal_matches;
    const suggestions = results.filter((result) => result.operation !== 'external-search').flatMap((result) => aiApplicableSuggestions(result.response));
    const latestColorimetry = [...results].reverse().find((result) => result.operation === 'analyze' && result.analysisImageKey === analysisImageKey)?.response.data.colorimetry;
    const latestSimilar = [...results].reverse().find((result) => result.operation === 'similar' && result.analysisImageKey === analysisImageKey);
    const topSuggestions = [...results].reverse().filter((result) => result.operation !== 'external-search')
        .flatMap((result) => aiApplicableSuggestions(result.response))
        .filter((suggestion, index, items) => items.findIndex((item) => item.field === suggestion.field) === index)
        .filter((suggestion) => suggestion.field !== 'color' || !latestColorimetry || latestColorimetry.primary_color != null);

    function selectFile(kind: 'main' | 'barcode' | 'label', file: File | null) {
        if (file && (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 6 * 1024 * 1024)) {
            setError('Selecciona una imagen JPEG, PNG o WebP de hasta 6 MB.'); return;
        }
        setError(null);
        if (kind === 'main') updateForm((current) => ({ ...current, imagen_principal: file }));
        else setCapture((current) => ({ ...current, [kind]: file }));
    }
    function persistReviewQueue(queue: Review[]) {
        pendingReviewsRef.current = queue;
        setPendingReviews(queue);
        try {
            if (queue.length) window.sessionStorage.setItem(storageKey, JSON.stringify({ saved_at: Date.now(), reviews: queue }));
            else window.sessionStorage.removeItem(storageKey);
        } catch { /* The in-memory queue remains available when session storage is blocked. */ }
    }
    async function flushReviews() {
        if (sendingReviewsRef.current) return;
        sendingReviewsRef.current = true;
        setSendingReviews(true);
        try {
            while (pendingReviewsRef.current.length > 0) {
                const decision = pendingReviewsRef.current[0];
                try {
                    await axios.post('/superadmin/ai/feedback', decision, { timeout: 10000 });
                    const key = aiReviewKey(decision);
                    // A later decision on the same field must survive an earlier response.
                    if (pendingReviewsRef.current.some((item) => item === decision)) {
                        setReviews((current) => ({ ...current, [key]: decision.action }));
                        persistReviewQueue(pendingReviewsRef.current.filter((item) => item !== decision));
                    }
                } catch { break; }
            }
        } finally {
            sendingReviewsRef.current = false;
            setSendingReviews(false);
        }
    }
    function sendReview(decision: Review) {
        persistReviewQueue(queueAiReview(pendingReviewsRef.current, decision));
        void flushReviews();
    }
    function review(suggestion: AiSuggestion, action: Review['action'], value: unknown, target = variantIndex) {
        sendReview({ suggestion_id: suggestion.id, action, confirmed_value: value,
            ...(variantSuggestionFields.has(suggestion.field) ? { variant_index: target } : {}) });
    }
    function applyOne(suggestion: AiSuggestion, confirmed = false, target = variantIndex) {
        const current = formRef.current;
        const issue = suggestionIssue(suggestion, current, catalogs, target);
        if (issue) { setNotice(issue); return; }
        if (!confirmed && wouldReplaceValue(suggestion, current, target)) {
            setReplacement({ suggestion, index: target, previous: currentSuggestionValue(current, suggestion.field, target) }); return;
        }
        updateForm((value) => applyAiSuggestion(value, suggestion, target));
        review(suggestion, 'accepted', suggestionValue(suggestion), target);
        setReplacement(null);
        setNotice((aiFieldLabels[suggestion.field] ?? suggestion.field) + ' aplicado. Puedes seguir editando antes de guardar.');
    }
    function applyCompatible(items: AiSuggestion[]) {
        const batch = compatibleSuggestionBatch(formRef.current, items, catalogs, variantIndex);
        updateForm((current) => compatibleSuggestionBatch(current, batch.accepted, catalogs, variantIndex).form);
        batch.accepted.forEach((suggestion) => review(suggestion, 'accepted', suggestionValue(suggestion)));
        setNotice(batch.accepted.length + ' campos aplicados. ' + (batch.skipped.length
            ? batch.skipped.length + ' sugerencias requieren revisión individual; se conservaron los valores existentes.'
            : 'Los datos siguen editables.'));
    }
    async function request(operation: string, payload: FormData | Record<string, unknown>, checkedKey?: string) {
        if (busy) return;
        setBusy(operation); setError(null); setNotice(null);
        const controller = new AbortController(); abort.current = controller;
        const imageAtRequest = analysisImageRef.current;
        try {
            const { data: response } = await axios.post<AiResponse>('/superadmin/ai/' + operation, payload,
                { timeout: 65000, signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response || !response.data || typeof response.data !== 'object' || typeof response.status !== 'string' || response.status === 'failed') throw new Error('INVALID_RESPONSE');
            if (response.run_id != null) updateForm((current) => ({ ...current, ai_run_ids: Array.from(new Set([...(current.ai_run_ids ?? []), String(response.run_id)])) }));
            setResults((current) => [...current, { operation, response, analysisImageKey: operation === 'analyze' || operation === 'similar' ? imageAtRequest : undefined }].slice(-8));
            if (operation === 'analyze' && response.run_id != null && imageAtRequest === analysisImageRef.current) { setAnalysisRun(String(response.run_id)); setCheckedQuery(null); }
            if (operation === 'match') { setCheckedQuery(checkedKey ?? null); setExternalAllowed(response.data.external_search_allowed === true); }
            if (operation === 'barcode' || operation === 'label') {
                const code = response.data.codes?.find((item) => !item.contains_url && item.checksum_valid !== false);
                const gtin = response.data.gtin_candidates?.[0] ?? (code?.checksum_valid === true ? code.normalized_value : '');
                setQuery((current) => ({ ...current, gtin: gtin || current.gtin, sku: response.data.sku_candidate || current.sku,
                    model_code: response.data.model_code_candidate || current.model_code,
                    brand: response.data.brand_candidate?.name || current.brand,
                    query: code && !gtin ? code.normalized_value : current.query }));
                setCheckedQuery(null);
            }
            const image = response.data.prepared_image;
            if (image) {
                setPrepared((current) => [...current.filter((item) => item.token !== image.token), image]);
                updateForm((current) => ({ ...current, ai_image_tokens: Array.from(new Set([...(current.ai_image_tokens ?? []), image.token])) }));
                setNotice('Imagen preparada y añadida a la ficha. Se guardará como portada si todavía no tienes una; en otro caso irá a la galería.');
            }
            const resultNotice = aiResultNotice(response.status);
            if (resultNotice) setNotice(resultNotice);
        } catch (caught) {
            if (axios.isCancel(caught)) return;
            setError(axios.isAxiosError(caught) && caught.response?.status === 422
                ? 'Revisa el archivo o los datos de búsqueda. El servicio no pudo validar esta entrada.'
                : operation === 'similar' ? 'No fue posible buscar prendas similares. Puedes continuar y guardar la ficha manualmente.'
                    : 'No fue posible obtener sugerencias automáticas. Puedes continuar y guardar la prenda manualmente.');
        } finally { if (abort.current === controller) { abort.current = null; setBusy(null); } }
    }
    function analyzeImage(operation: 'analyze' | 'similar' | 'barcode' | 'label') {
        const image = operation === 'analyze' || operation === 'similar' ? form.imagen_principal : capture[operation];
        if (!image && (operation === 'analyze' || operation === 'similar') && existingImageId) {
            void request(operation, { existing_image_id: existingImageId }); return;
        }
        if (!image) { setError('Selecciona una fotografía de la prenda.'); return; }
        const payload = new FormData(); payload.append('image', image); void request(operation, payload);
    }
    function suggestionCard(suggestion: AiSuggestion) {
        const issue = suggestionIssue(suggestion, form, catalogs, variantIndex);
        const value = currentSuggestionValue(form, suggestion.field, variantIndex);
        const key = reviewKey(suggestion.id, variantSuggestionFields.has(suggestion.field) ? variantIndex : undefined);
        const score = suggestion.similarity_score ?? suggestion.confidence_score;
        return <div key={suggestion.id} className={box + ' bg-[#0a1016]'}>
            <p className="text-xs text-stone-400">{aiFieldLabels[suggestion.field] ?? suggestion.field}</p>
            <p className="mt-1 break-words text-sm text-white">{suggestion.label || suggestionValue(suggestion)}</p>
            <p className="mt-2 text-xs text-stone-400">Origen: {sourceLabels[suggestion.source ?? ''] ?? 'Análisis registrado'}{typeof score === 'number' ? ' · puntuación ' + score.toFixed(3) : ''}</p>
            {reviews[key] && <p className="mt-2 text-xs text-emerald-300">{({ accepted: 'Aplicada', corrected: 'Corrección registrada', rejected: 'Descartada' } as Record<string, string>)[reviews[key]]}</p>}
            {issue && <p className="mt-2 text-xs text-amber-200">{issue}</p>}
            <div className="mt-3 flex flex-wrap gap-2">
                <button type="button" disabled={Boolean(issue)} onClick={() => applyOne(suggestion)} className={button}>Aplicar {aiFieldLabels[suggestion.field]?.toLowerCase() ?? 'campo'}</button>
                <button type="button" onClick={() => review(suggestion, 'rejected', null)} className="sa-button-ghost px-3 py-2 text-xs">Descartar</button>
                {value && value !== suggestionValue(suggestion) && <button type="button" onClick={() => review(suggestion, 'corrected', value)} className="sa-button-ghost px-3 py-2 text-xs">Confirmar mi valor actual</button>}
            </div>
        </div>;
    }

    return <details open className="sa-panel rounded-[1.6rem] border border-[#7e9ab2]/25 p-5">
        <summary className="cursor-pointer"><span className="inline-flex items-center gap-3"><Sparkles aria-hidden="true" size={22} className="text-[#bfa786]" /><span className="text-lg text-white">Asistente inteligente</span></span></summary>
        <p className="mt-2 text-sm text-stone-300">Analiza fotos, códigos y etiquetas. Revisa cada propuesta antes de aplicarla.</p>
        <div className="mt-5 space-y-5">
            <div className="grid gap-4 lg:grid-cols-3">
                <div className={box}>
                    <h3 className="text-sm text-white">Imagen principal</h3>
                    {primaryPreview && <img src={primaryPreview} alt="Imagen principal de la prenda" className="mt-3 h-32 w-full rounded-lg bg-[#0a1016] object-contain" />}
                    <label className="mt-3 block text-xs text-stone-400">Cargar fotografía principal
                        <input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" disabled={Boolean(busy)} onChange={(event) => selectFile('main', event.target.files?.[0] ?? null)} className="mt-2 block w-full text-xs" />
                    </label>
                    {form.imagen_principal && <p className="mt-2 break-all text-xs text-stone-400">{form.imagen_principal.name}</p>}
                    {!form.imagen_principal && existingImageId && <p className="mt-2 text-xs text-stone-400">Puedes analizar la portada guardada de esta prenda.</p>}
                    <button type="button" disabled={Boolean(busy) || (!form.imagen_principal && !existingImageId)} onClick={() => analyzeImage('analyze')} className={button + ' mt-3'}>Analizar imagen</button>
                    <button type="button" disabled={Boolean(busy) || (!form.imagen_principal && !existingImageId)} onClick={() => analyzeImage('similar')} className={button + ' mt-3'}><Search size={14} aria-hidden="true" />Buscar similares</button>
                    <p className="mt-2 text-xs leading-5 text-stone-400">Busca prendas parecidas del catálogo con esta fotografía. Puedes revisar coincidencias sin modificar la ficha.</p>
                </div>
                {(['barcode', 'label'] as const).map((kind) => <div key={kind} className={box}>
                    <h3 className="text-sm text-white">{kind === 'barcode' ? 'Escanear código / QR' : 'Analizar etiqueta'}</h3>
                    <p className="mt-2 text-xs leading-5 text-stone-400">{kind === 'barcode' ? 'Usa una foto enfocada del código de barras o QR.' : 'Fotografía marca, talla, composición y modelo en una etiqueta dedicada.'}</p>
                    <label className="mt-3 block text-xs text-stone-400">{kind === 'barcode' ? 'Foto del código o cámara' : 'Foto de etiqueta o cámara'}
                        <input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" disabled={Boolean(busy)} onChange={(event) => selectFile(kind, event.target.files?.[0] ?? null)} className="mt-2 block w-full text-xs" />
                    </label>
                    {capture[kind] && <p className="mt-2 break-all text-xs text-stone-400">{capture[kind]?.name}</p>}
                    <button type="button" disabled={Boolean(busy) || !capture[kind]} onClick={() => analyzeImage(kind)} className={button + ' mt-3'}>{kind === 'barcode' ? 'Leer código / QR' : 'Extraer etiqueta'}</button>
                </div>)}
            </div>
            <div className={box}>
                <h3 className="text-sm text-white">Identificar en catálogo</h3>
                <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {([['sku', 'SKU / código interno', 'Código de Mati'], ['gtin', 'GTIN / EAN / UPC', 'Conserva ceros iniciales'],
                        ['brand', 'Marca conocida', searchPayload.brand || 'Marca'], ['model_code', 'Modelo / proveedor', searchPayload.model_code || 'Modelo']] as const)
                        .map(([field, label, placeholder]) => <label key={field} className="text-xs text-stone-400">{label}<input value={query[field]} onChange={(event) => setQuery((current) => ({ ...current, [field]: event.target.value }))} maxLength={field === 'gtin' ? 30 : field === 'sku' ? 100 : 150} placeholder={placeholder} className="sa-input mt-2 w-full" /></label>)}
                </div>
                <label className="mt-3 block text-xs text-stone-400">Texto adicional<input value={query.query} onChange={(event) => setQuery((current) => ({ ...current, query: event.target.value }))} maxLength={300} className="sa-input mt-2 w-full" placeholder="Nombre o texto útil de la etiqueta" /></label>
                <div className="mt-3 flex flex-wrap gap-2">
                    <button type="button" disabled={Boolean(busy) || !Object.values(searchPayload).some(Boolean)} onClick={() => void request('match', searchPayload, queryKey)} className={button}>Buscar en catálogo</button>
                    <button type="button" disabled={Boolean(busy) || checkedQuery !== queryKey || !externalAllowed} onClick={() => void request('external-search', searchPayload)} className={button}>Buscar información externa</button>
                </div>
                <p className="mt-2 text-xs leading-5 text-stone-400">Primero comprueba el catálogo interno con estos datos. La búsqueda externa solo consulta fuentes configuradas.</p>
            </div>
            <div role="status" aria-live="polite" className="space-y-2">
                {busy && <p className="flex items-center gap-2 text-sm text-stone-200"><Loader2 size={16} className="animate-spin" />{labels[busy]} en curso. Puedes seguir editando.</p>}
                {notice && <p className={box + ' bg-[#7e9ab2]/10 text-sm text-stone-200'}>{notice}</p>}
                {error && <p className={box + ' bg-amber-300/5 text-sm text-amber-100'}>{error}</p>}
                {pendingReviews.length > 0 && <div className={box + ' text-sm text-amber-100'}>{sendingReviews ? 'Registrando' : 'Hay'} {pendingReviews.length} decisiones pendientes de registrar. El guardado manual sigue disponible.<button type="button" disabled={sendingReviews} className="ml-2 underline disabled:opacity-50" onClick={() => void flushReviews()}>Reintentar registro</button></div>}
            </div>
            {latestSimilar && <VisualSimilarityResults response={latestSimilar.response} />}
            {latestInternal && <div className={box}>
                <h3 className="text-sm text-white">Coincidencias internas / posibles duplicados</h3>
                {latestInternal.length === 0 ? <p className="mt-2 text-sm text-stone-400">Sin coincidencias internas con esta evidencia.</p>
                    : latestInternal.map((match) => <div key={match.id} className="mt-3 rounded-lg bg-[#0a1016] p-3"><p className="text-sm text-white">{match.title}</p><p className="mt-1 text-xs text-stone-400">{match.sku} · {scoreText(match.score)}{match.exact_identifier ? ' · coincidencia por código' : ''}</p>{match.conflicts?.map((conflict) => <p key={conflict} className="text-xs text-amber-200">{warningText(conflict)}</p>)}{match.warnings?.map((warning, i) => <p key={'warning-' + i} className="text-xs text-amber-200">{warningText(warning)}</p>)}{match.href?.startsWith('/superadmin/') && <a href={match.href} target="_blank" rel="noopener noreferrer" className="mt-2 inline-block text-xs text-[#bfa786]">Revisar prenda ↗</a>}</div>)}
                <p className="mt-2 text-xs text-stone-400">La similitud visual por sí sola no confirma identidad.</p>
            </div>}
            {(suggestions.length > 0 || results.some((result) => result.response.data.candidates?.length)) && <label className="block max-w-md text-xs text-stone-400">Variante que recibirá color, talla y código<select value={variantIndex} onChange={(event) => { setVariantIndex(Number(event.target.value)); setReplacement(null); }} className="sa-input mt-2 w-full">{form.variant_rows.map((row, index) => !row.remove && <option key={row.id || index} value={index}>Variante {index + 1}{row.sku_prv ? ' · ' + row.sku_prv : ''}</option>)}</select></label>}
            {suggestions.length > 0 && <div className="space-y-3">
                <div className="flex flex-wrap items-end justify-between gap-3"><div><h3 className="text-sm text-white">Sugerencias para revisar</h3><p className="mt-1 text-xs text-stone-400">Aplicar todos conserva los campos ya informados.{latestColorimetry && !latestColorimetry.primary_color ? ' Revisa y aplica cada color individualmente: no hay un único dominante fiable.' : ''}</p></div><button type="button" onClick={() => applyCompatible(topSuggestions)} className={button}><Check size={14} />Aplicar todos los campos compatibles</button></div>
                <div className="grid gap-3 md:grid-cols-2">{suggestions.map(suggestionCard)}</div>
            </div>}
            {replacement && <div role="alert" className={box + ' bg-amber-300/10'}>
                <p className="text-sm text-amber-100">Aplicar «{replacement.suggestion.label}» reemplazará tu selección actual de {aiFieldLabels[replacement.suggestion.field]?.toLowerCase()}.</p>
                <div className="mt-3 flex gap-2"><button type="button" className={button} onClick={() => {
                    if (currentSuggestionValue(formRef.current, replacement.suggestion.field, replacement.index) !== replacement.previous) { setReplacement(null); setNotice('El campo cambió mientras revisabas. Vuelve a seleccionar la sugerencia.'); return; }
                    applyOne(replacement.suggestion, true, replacement.index);
                }}>Reemplazar este valor</button><button type="button" className="sa-button-ghost px-3 py-2 text-sm" onClick={() => setReplacement(null)}>Conservar mi valor</button></div>
            </div>}

            {results.filter((result) => result.operation !== 'similar').map(({ operation, response }, resultIndex) => <div key={response.request_id || resultIndex} className="space-y-3">
                {response.data.codes?.map((code, index) => <div key={index} className={box}>
                    <p className="text-xs text-stone-400">{code.symbology} · checksum {code.checksum_valid === true ? 'válido' : code.checksum_valid === false ? 'inválido: revisar' : 'sin validar / no aplica'}</p>
                    <p className="mt-1 break-all font-mono text-sm text-white">{code.normalized_value || code.raw_value}</p>
                    {code.contains_url ? <p className="mt-2 text-xs text-amber-200">El QR contiene una URL. Se muestra como texto y no se visita automáticamente.</p>
                        : <button type="button" className="sa-button-ghost mt-2 px-3 py-2 text-xs" onClick={() => { setQuery((current) => ({ ...current, ...(code.checksum_valid === true ? { gtin: code.normalized_value } : { query: code.normalized_value }) })); setCheckedQuery(null); }}>Usar para buscar en catálogo</button>}
                    {code.warnings?.map((warning, i) => <p key={i} className="text-xs text-amber-200">{warningText(warning)}</p>)}
                </div>)}
                {operation === 'barcode' && response.data.codes?.length === 0 && <p className="text-sm text-stone-400">No se detectó un código legible. Prueba otra foto o escribe el código.</p>}
                {operation === 'label' && <details open className={box}><summary className="cursor-pointer text-sm text-white">Texto y composición de etiqueta</summary><pre className="mt-3 whitespace-pre-wrap break-words font-sans text-sm text-stone-300">{response.data.raw_text || 'No se extrajo texto legible.'}</pre><dl className="mt-3 grid gap-2 text-sm sm:grid-cols-2">{([
                    ['Marca', response.data.brand_candidate?.name], ['Talla', response.data.size_candidate?.name],
                    ['Modelo', response.data.model_code_candidate], ['SKU visible', response.data.sku_candidate],
                    ['GTIN', response.data.gtin_candidates?.join(' · ')],
                ] as const).map(([label, value]) => value ? <div key={label}><dt className="text-xs text-stone-400">{label}</dt><dd className="mt-1 break-all text-stone-200">{value}</dd></div> : null)}</dl>{response.data.composition?.length ? <p className="mt-3 text-sm text-stone-200">{response.data.composition.map((item) => item.percentage + '% ' + item.material).join(' · ')}</p> : null}<p className="mt-2 text-xs text-stone-400">Confirma la composición en Materiales después de revisar la etiqueta original.</p></details>}
                {response.data.quality && <div className={box}><h3 className="text-sm text-white">Calidad de imagen</h3><p className="mt-2 text-xs text-stone-300">{response.data.quality.status === 'acceptable' ? 'Calidad aceptable' : 'Revisar calidad'} · {response.data.quality.width ?? response.data.image?.width} × {response.data.quality.height ?? response.data.image?.height} px{typeof response.data.quality.blur_score === 'number' ? ' · nitidez técnica ' + response.data.quality.blur_score.toFixed(1) : ''}{typeof response.data.quality.contrast === 'number' ? ' · contraste ' + response.data.quality.contrast.toFixed(1) : ''}</p>{response.data.quality.warnings?.map((warning, index) => <p key={index} className="mt-2 text-xs text-amber-200">{warningText(warning)}</p>)}</div>}

                {response.data.classification && <section className={box} aria-label="Clasificación visual">
                    <h3 className="text-sm text-white">Clasificación visual</h3>
                    {response.data.classification.abstained !== false
                        ? <p className="mt-2 text-sm text-amber-200">El modelo no tiene evidencia suficiente para sugerir automáticamente una categoría. Elige la categoría manualmente.</p>
                        : <p className="mt-2 text-xs text-stone-400">Revisa las propuestas aplicables antes de elegir la categoría.</p>}
                    {Boolean(response.data.classification.top_k?.length) && <>
                        <p className="mt-3 text-xs text-stone-300">Posibles coincidencias · ranking informativo</p>
                        <ol className="mt-2 list-inside list-decimal space-y-2 text-sm text-stone-200">{response.data.classification.top_k?.map((item, index) => <li key={String(item.category_id ?? index)}>
                            {item.category_name || catalogs.categories.find((category) => category.value === String(item.category_id))?.label || 'Categoría por revisar'}
                            <span className="ml-2 text-xs text-stone-400">{visualSimilarityScoreText(item.similarity_score)}</span>
                        </li>)}</ol>
                        <p className="mt-2 text-xs text-stone-400">Los scores son similitud coseno, no probabilidades ni porcentajes de certeza. El ranking informativo no aplica campos al formulario.</p>
                    </>}
                </section>}
                {response.data.attributes && (!response.data.attributes.pattern || response.data.attributes.pattern.abstained === true || response.data.attributes.abstained === true)
                    && <p className={box + ' text-sm text-amber-200'}>No hay evidencia suficiente para una propuesta aplicable de patrón. Puedes seleccionarlo manualmente.</p>}
                {response.data.colorimetry ? <ColorimetrySummary colorimetry={response.data.colorimetry} catalogs={catalogs} /> : null}
                {!response.data.colorimetry && response.data.colors?.palette?.length ? <div className={box}><h3 className="text-sm text-white">Paleta de la fotografía</h3><div className="mt-3 flex flex-wrap gap-3">{response.data.colors.palette.map((color, index) => <div key={color.hex + index} className="flex items-center gap-2 rounded-lg bg-white/5 p-3"><span className="h-8 w-8 rounded-full border border-white/20" style={{ backgroundColor: /^#[0-9a-f]{6}$/i.test(color.hex) ? color.hex : undefined }} aria-hidden="true" /><div><p className="text-xs text-stone-200">{index === 0 ? 'Dominante: ' : ''}{color.catalog_name || color.hex}</p><p className="mt-1 text-xs text-stone-400">{Math.round(color.percentage * 100)} % de la muestra</p></div></div>)}</div><p className="mt-2 text-xs text-stone-400">La proporción describe la fotografía; comprueba los colores sobre la prenda.</p>{response.data.colors.warnings?.map((warning, index) => <p key={index} className="mt-2 text-xs text-amber-200">{warningText(warning)}</p>)}</div> : null}
                {response.data.attributes?.pattern && <div className={box}><h3 className="text-sm text-white">Patrón visual posible</h3><p className="mt-2 text-sm text-stone-200">{response.data.attributes.pattern.name}</p>{response.data.attributes.pattern.warnings?.map((warning, index) => <p key={index} className="mt-2 text-xs text-amber-200">{warningText(warning)}</p>)}</div>}
                {response.data.duplicates?.length ? <div className={box}><h3 className="text-sm text-amber-200">Esta imagen ya aparece en el catálogo</h3>{response.data.duplicates.map((match, index) => <p key={match.id ?? index} className="mt-2 text-sm text-stone-300">{match.title ?? 'Imagen duplicada'}{match.href?.startsWith('/superadmin/') && <a href={match.href} target="_blank" rel="noopener noreferrer" className="ml-2 underline">Revisar</a>}</p>)}</div> : null}
                {response.data.candidates && <div className="space-y-3"><h3 className="text-sm text-white">Candidatos externos</h3>{response.data.candidates.length === 0 && <p className="text-sm text-stone-400">No hay candidatos en las fuentes disponibles. Puedes continuar con los datos propios.</p>}<div className="grid gap-4 md:grid-cols-2">{response.data.candidates.map((candidate, candidateIndex) => {
                    const key = String(response.run_id) + ':' + candidateIndex;
                    const url = safeExternalUrl(candidate.canonical_url);
                    return <article key={key} className={box + ' bg-[#0a1016]'}>
                        <h4 className="text-base text-white">{candidate.title}</h4><p className="mt-2 text-sm text-stone-300">{candidate.brand} {candidate.model_code}{candidate.gtin ? ' · GTIN ' + candidate.gtin : ''}</p>
                        <p className="mt-2 text-xs text-stone-400">Fuente: {candidate.source ?? candidate.source_id} · {scoreText(candidate.final_score)}</p><p className="mt-1 text-xs text-stone-400">Derechos: {rightsLabels[candidate.rights_status ?? 'unknown'] ?? 'Sin autorización confirmada'}</p>
                        {[...(candidate.conflicts ?? []), ...(candidate.warnings ?? [])].map((warning, index) => <p key={index} className="mt-2 text-xs text-amber-200">{warningText(warning)}</p>)}
                        <div className="mt-3 flex flex-wrap gap-2">{url && <a href={url} target="_blank" rel="noopener noreferrer" className={button}>Ver fuente <ExternalLink size={12} /></a>}<button type="button" disabled={!candidate.suggestions?.length} onClick={() => applyCompatible(candidate.suggestions ?? [])} className={button}>Usar datos compatibles</button><button type="button" onClick={() => setReferences((current) => Array.from(new Set([...current, key])))} className="sa-button-ghost px-3 py-2 text-xs">{references.includes(key) ? 'Marcado como referencia' : 'Usar como referencia'}</button></div>
                        {(candidate.images ?? []).map((image, imageIndex) => {
                            const imageUrl = externalReferencePreviewUrl(image.preview_url);
                            const rightsStatus = image.rights_status ?? candidate.rights_status;
                            const rightsEvidence = image.rights_evidence ?? candidate.rights_evidence;
                            return <div key={image.original_url + imageIndex} className={box + ' mt-4'}>
                                {imageUrl && rightsStatus !== 'prohibited' ? <img src={imageUrl} alt={'Referencia externa: ' + candidate.title} loading="lazy" className="h-40 w-full rounded-lg object-contain" /> : <div className="flex h-32 items-center justify-center rounded-lg bg-white/5 text-xs text-stone-400">Vista previa no disponible</div>}
                                <p className="mt-2 break-all text-xs text-stone-400">Imagen: {image.source ?? candidate.source ?? candidate.source_id} · {rightsLabels[rightsStatus ?? 'unknown'] ?? 'Sin autorización confirmada'}{image.width && image.height ? ' · ' + image.width + ' × ' + image.height + ' px' : ''}</p>
                                {image.fetched_at && <p className="mt-1 text-xs text-stone-400">Consultada: {image.fetched_at}</p>}
                                {rightsEvidence && <p className="mt-1 break-words text-xs text-stone-400">Autorización: {rightsEvidence}</p>}
                                {image.warning && <p className="mt-2 text-xs text-amber-200">{warningText(image.warning)}</p>}
                                {imageRightsAllowPublication(rightsStatus, rightsEvidence) ? <button type="button" disabled={Boolean(busy)} className={button + ' mt-3'} onClick={() => void request('prepare-publication', { external_run_id: response.run_id, candidate_index: candidateIndex, image_index: imageIndex, approved: true })}>Usar imagen autorizada</button> : <p className="mt-2 text-xs text-amber-200">{rightsStatus === 'prohibited' ? 'Esta imagen está bloqueada por las condiciones de la fuente.' : 'Solo referencia: falta autorización de uso confirmada.'}</p>}
                            </div>;
                        })}
                        {candidate.suggestions?.length ? <details className="mt-3"><summary className="cursor-pointer text-xs text-stone-300">Revisar campos individualmente</summary><div className="mt-3 space-y-2">{candidate.suggestions.map(suggestionCard)}</div></details> : null}
                    </article>;
                })}</div></div>}
                <div className="space-y-1 text-xs text-stone-400">{[...(response.warnings ?? []), ...(response.abstentions ?? []), ...(response.data.warnings ?? []), ...(response.data.attributes?.warnings ?? [])].map((warning, index) => <p key={index} className="text-amber-200">{warningText(warning)}</p>)}<details><summary className="cursor-pointer">Procedencia de {labels[operation]?.toLowerCase()} · {aiResultStatusLabel(response.status)}</summary><p className="mt-2">Solicitud {response.request_id}</p>{response.model && <p className="break-all">Modelo: {response.model.model_id} · revisión {response.model.model_revision} · pipeline {response.model.pipeline_version}{response.timing_ms?.total != null ? ' · ' + Math.round(response.timing_ms.total) + ' ms' : ''}</p>}</details></div>
            </div>)}
            {prepared.length > 0 && <div className={box}><h3 className="text-sm text-white">Imágenes preparadas para guardar</h3><div className="mt-3 flex flex-wrap gap-3">{prepared.filter((image) => form.ai_image_tokens?.includes(image.token)).map((image) => <div key={image.token} className="w-36"><img src={preparedImageUrl(image.token) ?? undefined} alt={image.name} className="h-32 w-36 rounded-lg object-contain" /><button type="button" className="sa-button-ghost mt-2 inline-flex items-center gap-1 px-3 py-2 text-xs" onClick={() => updateForm((current) => ({ ...current, ai_image_tokens: current.ai_image_tokens.filter((token) => token !== image.token) }))}><X size={12} />Quitar de la ficha</button></div>)}</div></div>}
            <p className="text-xs leading-5 text-stone-400">Las sugerencias no guardan la prenda. Confirma los datos con el botón habitual del formulario; el registro manual sigue disponible si el asistente falla.</p>
        </div>
    </details>;
}
