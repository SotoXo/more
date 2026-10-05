import type { ProductEditorForm } from '../types/productEditor';
import type { SuperadminFormFieldOption } from '../types/superadmin';

export type AiSuggestion = {
    id: string | number;
    field: string;
    value: unknown;
    label: string;
    source?: string;
    confidence_score?: number | null;
    similarity_score?: number | null;
};
export type AiClassification = {
    abstained?: boolean;
    top_k?: { category_id?: string | number; category_name?: string; similarity_score?: number }[];
    margin?: number | null;
};
export type AiVisualDecisionContext = {
    status: string;
    data: {
        classification?: AiClassification;
        attributes?: { pattern?: { name?: string; abstained?: boolean } | null; abstained?: boolean };
    };
};

export function aiApplicableSuggestions(response: AiVisualDecisionContext & { suggestions?: AiSuggestion[] }): AiSuggestion[] {
    return (response.suggestions ?? []).filter((suggestion) => {
        if (suggestion.source !== 'vision' || !['category', 'pattern'].includes(suggestion.field)) return true;
        if (!['completed', 'partial'].includes(response.status)) return false;
        if (suggestion.field === 'category') return response.data.classification?.abstained === false;
        const attributes = response.data.attributes;
        return attributes?.pattern != null && attributes.pattern.abstained !== true && attributes.abstained !== true;
    });
}

export function aiResultStatusLabel(status: string): string {
    return ({ completed: 'Completado', partial: 'Parcial', insufficient_evidence: 'Evidencia insuficiente',
        degraded: 'Servicio no disponible', failed: 'Error del servicio' } as Record<string, string>)[status] ?? 'Revisar resultado';
}

export function aiResultNotice(status: string): string | null {
    return ({ partial: 'Resultado parcial: revisa las advertencias y completa los datos pendientes manualmente.',
        insufficient_evidence: 'No hay evidencia suficiente para proponer estos datos. Puedes seleccionarlos manualmente.',
        degraded: 'El servicio no está disponible. Puedes continuar y guardar la ficha manualmente.' } as Record<string, string>)[status] ?? null;
}

export type AiCatalogs = {
    categories: SuperadminFormFieldOption[];
    brands: SuperadminFormFieldOption[];
    colors: SuperadminFormFieldOption[];
    sizes: SuperadminFormFieldOption[];
    patterns: SuperadminFormFieldOption[];
    categoryProfiles?: Record<string, { segment?: string | null; system?: string | null; group?: string | null }>;
    sizeSegment?: string;
};
export const aiFieldLabels: Record<string, string> = {
    category: 'Categoría', brand: 'Marca', color: 'Color', size: 'Talla',
    pattern: 'Patrón', model_code: 'Modelo / código de proveedor',
    gtin: 'GTIN / código de barras', name: 'Nombre', description: 'Descripción',
};
export const variantSuggestionFields = new Set(['color', 'size', 'gtin']);

function scalar(value: unknown): string | null {
    return typeof value === 'string' || (typeof value === 'number' && Number.isFinite(value)) ? String(value) : null;
}

export function suggestionValue(suggestion: AiSuggestion): string | null {
    const value = scalar(suggestion.value);
    if (value === null) return null;
    if (suggestion.field === 'description') return value;
    return ['name', 'gtin'].includes(suggestion.field) ? value.trim().toUpperCase() : value.trim();
}

export function currentSuggestionValue(form: ProductEditorForm, field: string, variantIndex: number): string {
    switch (field) {
        case 'category': return form.cod_categorias_pre;
        case 'brand': return form.cod_marcas_pre;
        case 'name': return form.nom_pre;
        case 'description': return form.des_pre;
        case 'model_code': return form.modelo_pre ?? '';
        case 'pattern': return form.patron_ids.join(',');
        case 'color': return form.variant_rows[variantIndex]?.cod_colores_prv ?? '';
        case 'size': return form.variant_rows[variantIndex]?.cod_tallas_prv ?? '';
        case 'gtin': return form.variant_rows[variantIndex]?.cob_prv ?? '';
        default: return '';
    }
}

function sizeFitsProfile(size: SuperadminFormFieldOption | undefined, profile: { segment?: string | null; system?: string | null; group?: string | null } | undefined, segment?: string): boolean {
    const normalize = (item?: string | null) => (item ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().replace('UNITALLA', 'UNICA');
    const differs = (left?: string | null, right?: string | null) => Boolean(left && right && normalize(left) !== normalize(right));
    return !differs(size?.segment, segment || profile?.segment) && !differs(size?.system, profile?.system) && !differs(size?.group, profile?.group);
}

export function suggestionIssue(suggestion: AiSuggestion, form: ProductEditorForm, catalogs: AiCatalogs, variantIndex: number): string | null {
    const value = suggestionValue(suggestion);
    if (!Object.hasOwn(aiFieldLabels, suggestion.field) || value === null || !value.trim()) return 'Solo disponible como referencia.';
    if (variantSuggestionFields.has(suggestion.field) && (!form.variant_rows[variantIndex] || form.variant_rows[variantIndex].remove)) {
        return 'Selecciona una variante activa para aplicar este campo.';
    }
    const catalog = ({ category: catalogs.categories, brand: catalogs.brands, color: catalogs.colors, size: catalogs.sizes, pattern: catalogs.patterns } as Record<string, SuperadminFormFieldOption[]>)[suggestion.field];
    if (catalog && !catalog.some((option) => option.value === value && option.active !== false)) return 'El valor no pertenece al catálogo activo. Revísalo manualmente.';
    if (suggestion.field === 'gtin' && !/^[A-Z0-9-]{1,100}$/i.test(value)) return 'Este código no es compatible con el campo comercial.';
    if (suggestion.field === 'gtin' && form.variant_rows.some((row, index) => index !== variantIndex && !row.remove && row.cob_prv.toUpperCase() === value)) return 'Este código ya está asignado a otra variante de esta ficha.';
    if (suggestion.field === 'name' && (value.length > 150 || !/^[\p{L}\p{N}\s\-_.]+$/u.test(value))) return 'Revisa el nombre: admite hasta 150 caracteres, letras, números, espacios, guiones y puntos.';
    if (suggestion.field === 'size') {
        const size = catalogs.sizes.find((option) => option.value === value);
        const profile = catalogs.categoryProfiles?.[form.cod_categorias_pre];
        if (!sizeFitsProfile(size, profile, catalogs.sizeSegment)) {
            return 'La talla detectada no coincide con el perfil seleccionado. Revisa categoría y segmento antes de aplicarla.';
        }
    }
    if (suggestion.field === 'category' && form.variant_rows.some((row) => !row.remove && row.cod_tallas_prv
        && !sizeFitsProfile(catalogs.sizes.find((option) => option.value === row.cod_tallas_prv), catalogs.categoryProfiles?.[value], catalogs.sizeSegment))) {
        return 'Esta categoría no coincide con alguna talla ya seleccionada. Revisa las tallas antes de aplicarla.';
    }
    if (suggestion.field === 'model_code' && value.length > 150) return 'El modelo supera el límite del formulario.';
    return null;
}

export function wouldReplaceValue(suggestion: AiSuggestion, form: ProductEditorForm, variantIndex: number): boolean {
    const current = currentSuggestionValue(form, suggestion.field, variantIndex);
    return Boolean(current) && current !== suggestionValue(suggestion);
}

export function applyAiSuggestion(form: ProductEditorForm, suggestion: AiSuggestion, variantIndex: number): ProductEditorForm {
    const value = suggestionValue(suggestion);
    if (value === null) return form;
    switch (suggestion.field) {
        case 'category': return { ...form, cod_categorias_pre: value };
        case 'brand': return { ...form, cod_marcas_pre: value };
        case 'name': return { ...form, nom_pre: value.toUpperCase() };
        case 'description': return { ...form, des_pre: value };
        case 'model_code': return { ...form, modelo_pre: value };
        case 'pattern': return { ...form, patron_ids: [value] };
        case 'color':
        case 'size':
        case 'gtin': {
            if (!form.variant_rows[variantIndex] || form.variant_rows[variantIndex].remove) return form;
            const field = { color: 'cod_colores_prv', size: 'cod_tallas_prv', gtin: 'cob_prv' }[suggestion.field];
            return {
                ...form,
                variant_rows: form.variant_rows.map((row, index) => index !== variantIndex ? row : {
                    ...row, [field]: value,
                    ...(suggestion.field === 'color' ? { colores_adicionales: row.colores_adicionales.filter((id) => id !== value) } : {}),
                }),
            };
        }
        default: return form;
    }
}

export function compatibleSuggestionBatch(form: ProductEditorForm, suggestions: AiSuggestion[], catalogs: AiCatalogs, variantIndex: number) {
    const accepted: AiSuggestion[] = [];
    const skipped: AiSuggestion[] = [];
    let next = form;
    const seen = new Set<string>();
    // Apply category first so a label size is checked against the proposed category.
    const ordered = [...suggestions].sort((left, right) => Number(right.field === 'category') - Number(left.field === 'category'));
    for (const suggestion of ordered) {
        if (seen.has(suggestion.field) || suggestionIssue(suggestion, next, catalogs, variantIndex) || wouldReplaceValue(suggestion, next, variantIndex)) {
            skipped.push(suggestion);
            continue;
        }
        next = applyAiSuggestion(next, suggestion, variantIndex);
        seen.add(suggestion.field);
        accepted.push(suggestion);
    }
    return { form: next, accepted, skipped };
}

export function safeExternalUrl(value?: string | null): string | null {
    if (!value) return null;
    try {
        const url = new URL(value);
        return ['https:', 'http:'].includes(url.protocol) && !url.username && !url.password ? url.href : null;
    } catch { return null; }
}

export function imageRightsAllowPublication(status?: string | null, evidence?: string | null): boolean {
    return ['owned', 'licensed', 'provider_authorized', 'public_domain'].includes(status ?? '') && Boolean(evidence?.trim());
}

export function existingAnalysisImageId(form: ProductEditorForm): string | null {
    if (form.imagen_principal) return null;
    return form.existing_images.find((image) => image.pri_igp && !image.remove)?.id || null;
}

export function preparedImageUrl(token?: string): string | null {
    return token && /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(token)
        ? '/superadmin/ai/prepared-images/' + token : null;
}

export function externalReferencePreviewUrl(value?: string): string | null {
    return value && /^\/superadmin\/ai\/external-reference\/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\/\d+\/\d+$/i.test(value) ? value : null;
}

export function visualSimilarityImageUrl(value?: string | null): string | null {
    return value && /^\/superadmin\/ai\/catalog-image\/[1-9]\d*$/.test(value) ? value : null;
}

export function visualSimilarityScoreText(score?: number | null): string {
    return typeof score === 'number' && Number.isFinite(score) && score >= -1 && score <= 1
        ? score.toFixed(3) + ' · similitud visual' : 'Similitud visual sin puntuación';
}
export type AiWarning = string | { code?: string; message?: string };

const warningMessages: Record<string, string> = {
    TARGET_GARMENT_IDENTITY_UNVERIFIED: 'La imagen no verifica qué píxeles pertenecen a la prenda objetivo. La paleta es informativa; elige el color manualmente.',
    BACKGROUND_MAY_AFFECT_COLOR: 'El fondo puede alterar el color detectado. Comprueba el tono en la prenda.',
    BACKGROUND_INTERFERENCE: 'No se pudo separar la prenda del fondo con suficiente fiabilidad. Usa una foto centrada sobre un fondo uniforme.',
    FOREGROUND_TOO_SMALL: 'La prenda ocupa poco espacio en la foto. Acerca el encuadre para distinguir sus colores.',
    MULTIPLE_FOREGROUND_REGIONS: 'Hay varias zonas que podrían pertenecer a objetos distintos. Fotografía una sola prenda para mejorar el análisis.',
    GARMENT_MAY_BE_CROPPED: 'La prenda puede estar cortada por el encuadre. Los colores describen solo la parte visible.',
    COLOR_OVEREXPOSURE_SUSPECTED: 'La luz puede haber aclarado demasiado los colores. Revisa una foto sin zonas quemadas.',
    COLOR_UNDEREXPOSURE_SUSPECTED: 'La foto está demasiado oscura para identificar con fiabilidad el tono comercial.',
    COLOR_CHANNEL_CLIPPING: 'La imagen perdió detalle en algunos tonos por la exposición. Revisa el color con luz uniforme.',
    WARM_LIGHTING_SUSPECTED: 'La iluminación parece muy cálida y puede volver los tonos más amarillos. Comprueba el color de la prenda.',
    COOL_LIGHTING_SUSPECTED: 'La iluminación parece muy fría y puede volver los tonos más azules. Comprueba el color de la prenda.',
    WHITE_BALANCE_SUSPECTED: 'La iluminación puede estar alterando el equilibrio de los colores. No hay un tono comercial confirmado.',
    NO_SINGLE_DOMINANT_COLOR: 'La prenda presenta varios colores sin un único dominante fiable. Conserva y revisa la paleta completa.',
    PALETTE_COVERAGE_LOW: 'La paleta resume solo parte de los colores visibles. Los porcentajes son aproximados.',
    CATALOG_MATCH_AMBIGUOUS: 'Varios colores del catálogo son parecidos al tono observado. Elige el color comercial manualmente.',
    CATALOG_COLOR_UNMATCHED: 'Algún tono observado no coincide suficientemente con los colores disponibles en el catálogo.',
    CATALOG_REFERENCE_APPROXIMATE: 'Alguna referencia de color del catálogo es aproximada. Confirma el tono antes de aplicarlo.',
    METALLIC_FINISH_NOT_VERIFIABLE: 'La fotografía no permite confirmar un acabado metalizado. Revisa el material y el brillo de la prenda.',
    COLOR_PROPERTIES_UNCERTAIN: 'La calidad o la iluminación impiden describir con fiabilidad las propiedades de color.',
    COLORIMETRY_INSUFFICIENT_EVIDENCE: 'No hay evidencia suficiente para analizar los colores de la prenda con fiabilidad. Puedes elegirlos manualmente o volver a fotografiarla.',
    COLORIMETRY_PARTIAL_EVIDENCE: 'El análisis de color es parcial. Revisa la paleta y los avisos antes de elegir un color del catálogo.',
    COLORIMETRY_INVALID_RESPONSE: 'No se pudo interpretar la colorimetría. Revisa los colores manualmente.',
    CATEGORY_LOW_MARGIN_OR_SIMILARITY: 'No hay suficiente diferencia entre las categorías sugeridas. Elige la categoría manualmente.',
    VISUAL_MODEL_UNAVAILABLE_MANUAL_ENTRY_AVAILABLE: 'El análisis visual no está disponible. Puedes continuar completando los datos manualmente.',
    MODEL_UNAVAILABLE: 'El análisis visual no está disponible en este momento.',
    GPU_BUSY: 'El análisis visual está ocupado. Puedes intentarlo de nuevo más tarde.',
    OCR_UNAVAILABLE: 'No se pudo leer la etiqueta automáticamente. Puedes escribir los datos visibles.',
    OCR_BUSY: 'La lectura de etiquetas está ocupada. Puedes intentarlo de nuevo más tarde.',
    POSSIBLE_BLUR_OR_UNIFORM_SURFACE: 'La imagen puede estar desenfocada o mostrar una superficie uniforme.',
    LOW_CONTRAST: 'La imagen tiene poco contraste. Una foto con luz uniforme puede ayudar.',
    LOW_RESOLUTION: 'La imagen tiene poca resolución para distinguir los detalles.',
    UNDEREXPOSED: 'La foto está oscura. Comprueba la iluminación.',
    OVEREXPOSED_OR_WHITE_BACKGROUND: 'La foto tiene muchas zonas claras o un fondo blanco. Revisa la exposición.',
    COMPOSITION_TOTAL_REQUIRES_REVIEW: 'Los porcentajes detectados no suman 100 %. Revisa la composición en la etiqueta.',
    INVALID_CHECKSUM: 'El código no supera la comprobación de dígitos. Revisa la foto o el código escrito.',
    INVALID_GTIN: 'El GTIN no es válido. Revisa los dígitos antes de usarlo.',
    INVALID_GTIN_NOT_IDENTITY_EVIDENCE: 'Este GTIN no es válido y no puede confirmar la identidad de una prenda.',
    GTIN_CONFLICT: 'El código del candidato es distinto al código buscado.',
    BRAND_CONFLICT: 'La marca del candidato no coincide con la marca buscada.',
    MODEL_CODE_CONFLICT: 'El modelo del candidato no coincide con el modelo buscado.',
    MODEL_CONFLICT: 'El modelo del candidato no coincide con el modelo buscado.',
    EMBEDDING_MODEL_MISMATCH: 'Estas imágenes se analizaron con modelos distintos y no se compararon visualmente.',
    VECTOR_STORE_UNAVAILABLE: 'La búsqueda visual no está disponible en este momento. Puedes continuar con el registro manual.',
    VECTOR_INDEX_EMPTY: 'Todavía no hay prendas preparadas para comparar por fotografía. Puedes buscar en el catálogo o completar la ficha manualmente.',
    VECTOR_COMPATIBLE_INDEX_EMPTY: 'Las prendas disponibles todavía no se pueden comparar con esta foto. Requieren un análisis compatible.',
    VECTOR_SEARCH_UNAVAILABLE: 'No fue posible consultar prendas similares. Puedes continuar y guardar la ficha manualmente.',
    IMAGE_EMBEDDING_UNAVAILABLE: 'No se pudo preparar la fotografía para compararla. Prueba otra foto o continúa manualmente.',
    NO_SIMILAR_PRODUCTS_ABOVE_THRESHOLD: 'No se encontraron prendas con suficiente parecido visual. Puedes buscar por código o continuar manualmente.',
    VISUAL_SIMILARITY_DOES_NOT_CONFIRM_IDENTITY: 'El parecido visual no confirma que sea la misma prenda. Revisa las fotos y los datos del catálogo.',
    NO_COMPATIBLE_CATALOG_EMBEDDINGS: 'Las prendas disponibles todavía no se pueden comparar con esta foto. Requieren un análisis compatible.',
    NO_EXTERNAL_SOURCES_CONFIGURED: 'Todavía no hay fuentes externas configuradas para consultar.',
    SOURCE_NOT_CONFIGURED: 'Esta fuente externa no está configurada para consulta.',
    INSUFFICIENT_SEARCH_EVIDENCE: 'Escribe un código, modelo o texto de etiqueta para orientar la búsqueda.',
    IMAGE_RIGHTS_UNKNOWN: 'No hay autorización de uso confirmada para esta imagen. Úsala solo como referencia.',
    RIGHTS_NOT_APPROVED: 'Esta imagen no tiene autorización para publicarse.',
    SOURCE_UNAVAILABLE: 'La fuente externa no está disponible en este momento.',
    SOURCE_TIMEOUT: 'La fuente externa tardó demasiado en responder.',
    SOURCE_RATE_LIMITED: 'La fuente externa ha limitado las consultas. Inténtalo más tarde.',
    SOURCE_ROBOTS_RATE_LIMIT: 'La fuente externa ha limitado las consultas. Inténtalo más tarde.',
    SOURCE_ROBOTS_BLOCKED: 'La fuente no permite esta consulta automática.',
    SOURCE_TERMS_BLOCKED: 'La fuente no autoriza esta consulta automática.',
    SOURCE_ACCESS_BLOCKED: 'La fuente no permite acceder a esta información.',
    SOURCE_PRODUCT_NOT_FOUND: 'No se encontró el producto en esta fuente.',
    SOURCE_PARSER_CHANGED: 'La página de la fuente cambió y no se pudo extraer el producto.',
    SOURCE_CONFIGURATION_INVALID: 'La fuente necesita una actualización de su configuración.',
    QR_URL_NOT_FETCHED: 'El QR contiene una dirección web. Se muestra como texto y no se visita automáticamente.',
    UNSAFE_REMOTE_URL: 'No se pudo consultar esta dirección de forma segura.',
    UNCALIBRATED_VISUAL_SUGGESTION: 'La sugerencia visual requiere revisión humana.',
    VISUAL_SCORES_ARE_NOT_CALIBRATED_PROBABILITIES: 'Las puntuaciones expresan similitud y no porcentajes de certeza.',
    VISUAL_SIMILARITY_DOES_NOT_PROVE_IDENTITY: 'Parecerse en una foto no confirma que sea la misma prenda.',
    CANDIDATE_REQUIRES_HUMAN_REVIEW: 'Comprueba los datos del candidato antes de aplicarlos.',
};

export function aiWarningText(warning: AiWarning): string {
    const code = typeof warning === 'string' ? warning : warning.code;
    if (code && warningMessages[code]) return warningMessages[code];
    if (code?.startsWith('SOURCE_')) return 'No se pudo obtener información de esta fuente. Puedes continuar con datos propios.';
    const message = typeof warning === 'string' ? warning : warning.message;
    return message && !/^[A-Z_]+(?::.*)?$/.test(message) ? message : 'Este resultado requiere revisión antes de aplicarlo.';
}

export type AiReviewDecision = {
    suggestion_id: string | number;
    action: 'accepted' | 'corrected' | 'rejected';
    confirmed_value: unknown;
    variant_index?: number;
};

export function aiReviewKey(review: Pick<AiReviewDecision, 'suggestion_id' | 'variant_index'>): string {
    return String(review.suggestion_id) + (review.variant_index == null ? '' : ':' + review.variant_index);
}

export function queueAiReview(queue: AiReviewDecision[], review: AiReviewDecision): AiReviewDecision[] {
    return [...queue.filter((item) => aiReviewKey(item) !== aiReviewKey(review)), review];
}

export function readAiReviewQueue(serialized: string | null, now = Date.now()): AiReviewDecision[] {
    if (!serialized || serialized.length > 1_100_000) return [];
    try {
        const payload = JSON.parse(serialized);
        if (!payload || typeof payload.saved_at !== 'number' || now - payload.saved_at > 86_400_000
            || payload.saved_at > now + 60_000 || !Array.isArray(payload.reviews)) return [];
        return payload.reviews.slice(-100).filter((review: AiReviewDecision) => {
            if (!review || !/^\d+$/.test(String(review.suggestion_id)) || !['accepted', 'corrected', 'rejected'].includes(review.action)) return false;
            if (review.variant_index != null && (!Number.isInteger(review.variant_index) || review.variant_index < 0 || review.variant_index > 999)) return false;
            const value = review.confirmed_value;
            return value === null || (typeof value === 'string' && value.length <= 10000) || (typeof value === 'number' && Number.isFinite(value));
        });
    } catch { return []; }
}
