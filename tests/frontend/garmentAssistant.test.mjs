import { strict as assert } from 'node:assert';
import { test } from 'node:test';
import {
    aiApplicableSuggestions, aiResultNotice, aiResultStatusLabel, aiReviewKey, aiWarningText, applyAiSuggestion, compatibleSuggestionBatch,
    currentSuggestionValue, existingAnalysisImageId, externalReferencePreviewUrl,
    imageRightsAllowPublication, preparedImageUrl, queueAiReview, readAiReviewQueue,
    safeExternalUrl, suggestionIssue, suggestionValue, wouldReplaceValue,
} from '../../resources/js/lib/garmentAssistant.ts';

const uuid = '088af838-8d17-4e37-8f50-e7404d2a0a99';
const option = (value, label = value, details = {}) => ({ value, label, ...details });
const catalogs = {
    categories: [option('top', 'POLERA'), option('bottom', 'PANTALÓN')],
    brands: [option('brand', 'MARCA'), option('retired', 'ANTIGUA', { active: false })],
    colors: [option('red', 'ROJO'), option('blue', 'AZUL')],
    sizes: [
        option('m', 'M', { segment: 'ADULTO', system: 'ALFA', group: 'SUPERIOR' }),
        option('38', '38', { segment: 'ADULTO', system: 'NUMERICA', group: 'INFERIOR' }),
        option('child', 'M', { segment: 'INFANTIL', system: 'ALFA', group: 'SUPERIOR' }),
    ],
    patterns: [option('plain', 'LISO')],
    categoryProfiles: {
        top: { segment: 'ADULTO', system: 'ALFA', group: 'SUPERIOR' },
        bottom: { segment: 'ADULTO', system: 'NUMERICA', group: 'INFERIOR' },
    },
    sizeSegment: 'ADULTO',
};
const variant = (overrides = {}) => ({
    id: '', sku_prv: '', cod_colores_prv: '', tip_pal_prv: 'dominante', colores_adicionales: [],
    cod_tallas_prv: '', cob_prv: '', pve_prv: '120', cos_prv: '50',
    stock_inicial: '3', ubi_inv: 'TIENDA', est_prv: true, remove: false, ...overrides,
});
const form = (overrides = {}) => ({
    patron_ids: [], textura_ids: [], material_rows: [],
    nom_pre: '', des_pre: '', modelo_pre: '', cod_categorias_pre: '', cod_marcas_pre: '',
    cod_temporadas_pre: '', gen_pre: '', prb_pre: '120', est_pre: true, usa_variantes: true,
    imagen_principal: null, fue_igp: 'propia', existing_images: [], gallery_images: [],
    variant_rows: [variant(), variant()], ai_run_ids: [], ai_image_tokens: [], ...overrides,
});
const suggestion = (field, value, id = field) => ({ id, field, value, label: String(value) });

test('only real active catalog values can be applied and unstructured data stays reference-only', () => {
    for (const [field, value] of [['brand', 'retired'], ['color', 'fake'], ['price', '100'], ['brand', {}]]) {
        assert.ok(suggestionIssue(suggestion(field, value), form(), catalogs, 0), field);
    }
    assert.equal(suggestionIssue(suggestion('brand', 'brand'), form(), catalogs, 0), null);
    assert.equal(suggestionValue(suggestion('brand', Number.NaN)), null);
});

test('visual suggestions never alter prices, quantities, location or unrelated variants', () => {
    const original = form();
    const next = applyAiSuggestion(original, suggestion('color', 'red'), 1);
    assert.notEqual(next, original);
    assert.equal(next.variant_rows[0], original.variant_rows[0]);
    assert.deepEqual(next.variant_rows[1], { ...original.variant_rows[1], cod_colores_prv: 'red' });
    assert.equal(next.prb_pre, original.prb_pre);
    assert.equal(applyAiSuggestion(original, suggestion('stock_inicial', '99'), 0), original);
});

test('applying a dominant color removes that same color from secondary colors without erasing other colors', () => {
    const original = form({ variant_rows: [variant({ colores_adicionales: ['red', 'blue'] })] });
    assert.deepEqual(applyAiSuggestion(original, suggestion('color', 'red'), 0).variant_rows[0].colores_adicionales, ['blue']);
    assert.deepEqual(original.variant_rows[0].colores_adicionales, ['red', 'blue']);
});

test('removed or missing variants cannot receive an automatic field', () => {
    const original = form({ variant_rows: [variant({ remove: true })] });
    assert.ok(suggestionIssue(suggestion('size', 'm'), original, catalogs, 0));
    assert.ok(suggestionIssue(suggestion('color', 'red'), original, catalogs, 7));
    assert.equal(applyAiSuggestion(original, suggestion('color', 'red'), 0), original);
});

test('batch preserves existing values and accepts only one compatible suggestion per field', () => {
    const original = form({ nom_pre: 'MI NOMBRE', cod_marcas_pre: 'brand' });
    const batch = compatibleSuggestionBatch(original, [
        suggestion('name', 'OTRO'), suggestion('brand', 'retired'),
        suggestion('color', 'red', 1), suggestion('color', 'blue', 2),
    ], catalogs, 0);
    assert.equal(batch.form.nom_pre, 'MI NOMBRE');
    assert.equal(batch.form.cod_marcas_pre, 'brand');
    assert.equal(batch.form.variant_rows[0].cod_colores_prv, 'red');
    assert.equal(batch.accepted.length, 1);
    assert.equal(batch.skipped.length, 3);
    assert.equal(wouldReplaceValue(suggestion('name', 'OTRO'), original, 0), true);
});

test('batch applies category before checking size, independent of suggestion order', () => {
    const batch = compatibleSuggestionBatch(form(), [suggestion('size', '38'), suggestion('category', 'top')], catalogs, 0);
    assert.equal(batch.form.cod_categorias_pre, 'top');
    assert.equal(batch.form.variant_rows[0].cod_tallas_prv, '');
    assert.equal(batch.skipped[0].field, 'size');
    assert.equal(suggestionIssue(suggestion('size', 'child'), form({ cod_categorias_pre: 'top' }), catalogs, 0)?.includes('perfil'), true);
});

test('category suggestions do not silently leave a preselected size with a conflicting category profile', () => {
    const original = form({ variant_rows: [variant({ cod_tallas_prv: '38' })] });
    assert.ok(suggestionIssue(suggestion('category', 'top'), original, catalogs, 0));
    assert.equal(suggestionIssue(suggestion('category', 'bottom'), original, catalogs, 0), null);
});

test('GTIN fields preserve leading zeroes and reject a code already used by another active variant', () => {
    const code = '00012345678905';
    const original = form({ variant_rows: [variant(), variant({ cob_prv: code })] });
    assert.equal(suggestionValue(suggestion('gtin', code)), code);
    assert.ok(suggestionIssue(suggestion('gtin', code), original, catalogs, 0));
    original.variant_rows[1].remove = true;
    assert.equal(suggestionIssue(suggestion('gtin', code), original, catalogs, 0), null);
    assert.ok(suggestionIssue(suggestion('gtin', 'https://example.test'), original, catalogs, 0));
});

test('model codes remain editable and descriptions retain their original casing and whitespace', () => {
    let draft = applyAiSuggestion(form(), suggestion('model_code', ' AB-123 '), 0);
    draft = applyAiSuggestion(draft, suggestion('description', '  Algodón con bolsillo.\n'), 0);
    assert.equal(currentSuggestionValue(draft, 'model_code', 0), 'AB-123');
    assert.equal(draft.des_pre, '  Algodón con bolsillo.\n');
    assert.ok(suggestionIssue(suggestion('model_code', 'x'.repeat(151)), draft, catalogs, 0));
});

test('existing image analysis sends only the active primary record id when no new upload is selected', () => {
    const draft = form({ existing_images: [{ id: '9', pri_igp: true, remove: true }, { id: '12', pri_igp: true, remove: false }] });
    assert.equal(existingAnalysisImageId(draft), '12');
    assert.equal(existingAnalysisImageId({ ...draft, imagen_principal: { name: 'new.webp' } }), null);
});

test('external images use only Laravel reference routes and prepared UUID routes', () => {
    const route = '/superadmin/ai/external-reference/' + uuid + '/0/1';
    assert.equal(externalReferencePreviewUrl(route), route);
    for (const value of ['https://external.test/image.webp', '//evil.test', route + '?url=https://evil.test', route + '/../2', '/superadmin/ai/external-reference/' + '-'.repeat(36) + '/0/0']) {
        assert.equal(externalReferencePreviewUrl(value), null, value);
    }
    assert.equal(preparedImageUrl(uuid), '/superadmin/ai/prepared-images/' + uuid);
    assert.equal(preparedImageUrl('../../image'), null);
});

test('publication requires an explicit allowed rights status and nonempty evidence', () => {
    for (const status of ['owned', 'licensed', 'provider_authorized', 'public_domain']) {
        assert.equal(imageRightsAllowPublication(status, 'Autorización registrada'), true);
        assert.equal(imageRightsAllowPublication(status), false);
        assert.equal(imageRightsAllowPublication(status, '  '), false);
    }
    assert.equal(imageRightsAllowPublication('unknown', 'Hay una foto'), false);
    assert.equal(imageRightsAllowPublication('prohibited', 'Texto'), false);
});

test('source links reject credentials and executable schemes', () => {
    assert.equal(safeExternalUrl('https://supplier.test/product/123'), 'https://supplier.test/product/123');
    for (const value of ['javascript:alert(1)', 'data:text/html,test', 'https://user:secret@external.test', '//external.test']) {
        assert.equal(safeExternalUrl(value), null);
    }
});

test('warnings expose useful Spanish text without raw internal source error codes', () => {
    assert.match(aiWarningText('IMAGE_RIGHTS_UNKNOWN'), /referencia/);
    assert.match(aiWarningText('SOURCE_TIMEOUT'), /tardó/);
    assert.equal(aiWarningText('SOURCE_UNKNOWN_FAILURE'), 'No se pudo obtener información de esta fuente. Puedes continuar con datos propios.');
    assert.equal(aiWarningText('SOME_INTERNAL_CODE'), 'Este resultado requiere revisión antes de aplicarlo.');
    assert.equal(aiWarningText({ message: 'Revisa esta etiqueta.' }), 'Revisa esta etiqueta.');
});

test('pending reviews retain only the newest decision per suggestion and target variant', () => {
    const accepted = { suggestion_id: 1, action: 'accepted', confirmed_value: 'red', variant_index: 0 };
    const otherVariant = { ...accepted, variant_index: 1 };
    const rejected = { ...accepted, action: 'rejected', confirmed_value: null };
    const queue = queueAiReview([accepted, otherVariant], rejected);
    assert.deepEqual(queue, [otherVariant, rejected]);
    assert.notEqual(aiReviewKey(accepted), aiReviewKey(otherVariant));
});

test('pending feedback recovery is bounded, expires after one day and ignores malformed decisions', () => {
    const now = 1_000_000_000;
    const valid = { suggestion_id: 1, action: 'corrected', confirmed_value: 'blue', variant_index: 0 };
    const pack = (reviews, saved_at = now) => JSON.stringify({ reviews, saved_at });
    assert.deepEqual(readAiReviewQueue(pack([valid]), now), [valid]);
    assert.deepEqual(readAiReviewQueue(pack([valid], now - 86_400_001), now), []);
    assert.deepEqual(readAiReviewQueue(pack([valid], now + 60_001), now), []);
    assert.deepEqual(readAiReviewQueue('{broken', now), []);
    assert.deepEqual(readAiReviewQueue(pack([{ ...valid, variant_index: -1 }, { ...valid, action: 'save_inventory' }, { ...valid, confirmed_value: {} }]), now), []);
});


test('abstained category keeps informational ranking but cannot be applied or counted as accepted', () => {
    const ranked = [{ category_id: 'top', category_name: 'POLERA', similarity_score: 0.072 }];
    const response = { status: 'partial', human_review_required: true,
        data: { classification: { abstained: true, top_k: ranked }, attributes: { pattern: null } },
        suggestions: [{ ...suggestion('category', 'top'), source: 'vision' }],
    };
    const applicable = aiApplicableSuggestions(response);
    const batch = compatibleSuggestionBatch(form(), applicable, catalogs, 0);
    assert.deepEqual(applicable, []);
    assert.equal(batch.form.cod_categorias_pre, '');
    assert.deepEqual(batch.accepted, []);
    assert.equal(response.data.classification.top_k, ranked);
    assert.equal(response.human_review_required, true);
});

test('partial results may apply a nonabstained category and keep an abstained pattern informational', () => {
    const category = { ...suggestion('category', 'top'), source: 'vision' };
    const pattern = { ...suggestion('pattern', 'plain'), source: 'vision' };
    const response = { status: 'partial', data: { classification: { abstained: false }, attributes: { pattern: null } },
        suggestions: [category, pattern],
    };
    assert.deepEqual(aiApplicableSuggestions(response), [category]);
    assert.equal(compatibleSuggestionBatch(form(), aiApplicableSuggestions(response), catalogs, 0).form.cod_categorias_pre, 'top');
    response.data.attributes.pattern = { name: 'LISO', abstained: true };
    assert.deepEqual(aiApplicableSuggestions(response), [category]);
    response.data.attributes.pattern = { name: 'LISO' };
    assert.deepEqual(aiApplicableSuggestions(response), [category, pattern]);
});

test('insufficient or degraded responses and missing category decision cannot expose visual proposals', () => {
    const category = { ...suggestion('category', 'top'), source: 'vision' };
    const pattern = { ...suggestion('pattern', 'plain'), source: 'vision' };
    const brand = { ...suggestion('brand', 'brand'), source: 'label_ocr' };
    for (const status of ['insufficient_evidence', 'degraded']) {
        assert.deepEqual(aiApplicableSuggestions({ status,
            data: { classification: { abstained: false }, attributes: { pattern: { name: 'LISO' } } },
            suggestions: [category, pattern, brand] }), [brand]);
    }
    assert.deepEqual(aiApplicableSuggestions({ status: 'completed', data: { classification: { top_k: [] } }, suggestions: [category] }), []);
});

test('completed, partial, insufficient evidence and service errors have distinct messages', () => {
    assert.equal(aiResultStatusLabel('completed'), 'Completado');
    assert.equal(aiResultNotice('completed'), null);
    assert.match(aiResultNotice('partial'), /parcial/);
    assert.match(aiResultNotice('insufficient_evidence'), /evidencia suficiente/);
    assert.match(aiResultNotice('degraded'), /servicio no está disponible/);
    assert.equal(new Set(['completed', 'partial', 'insufficient_evidence', 'degraded'].map(aiResultStatusLabel)).size, 4);
    assert.match(aiWarningText('TARGET_GARMENT_IDENTITY_UNVERIFIED'), /paleta es informativa/);
});
