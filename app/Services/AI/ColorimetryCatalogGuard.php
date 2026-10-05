<?php

namespace App\Services\AI;

/**
 * Reconciles observations with the catalog Laravel supplied for this request.
 * It never creates a commercial color or turns a swatch into a confirmed value.
 */
class ColorimetryCatalogGuard
{
    private const FAMILIES = [
        'black', 'white', 'gray', 'beige_cream', 'brown', 'red', 'orange',
        'yellow', 'green', 'blue', 'violet', 'pink', 'metallic', 'multicolor',
    ];
    private const LIGHTING_WARNINGS = [
        'COLOR_OVEREXPOSURE_SUSPECTED', 'COLOR_UNDEREXPOSURE_SUSPECTED',
        'COLOR_CHANNEL_CLIPPING', 'WARM_LIGHTING_SUSPECTED', 'COOL_LIGHTING_SUSPECTED',
        'WHITE_BALANCE_SUSPECTED',
    ];

    public function normalize(array $result, array $catalog): array
    {
        $available = [];
        foreach ($catalog as $entry) {
            if (is_array($entry) && isset($entry['id'], $entry['name'])) {
                $available[(string) $entry['id']] = $entry;
            }
        }

        if (!array_key_exists('colorimetry', $result['data'])) {
            // Legacy geometry offers no evidence of the target garment's identity.
            $result['data']['colors'] = $this->legacyColors($result['data']['colors'], []);
            $result['data']['colors']['commercial_color_status'] = 'insufficient_evidence';
            $result['data']['colors']['warnings'] = array_values(array_unique([
                ...($result['data']['colors']['warnings'] ?? []), 'TARGET_GARMENT_IDENTITY_UNVERIFIED',
            ]));
            $result['warnings'] = array_values(array_unique([...$result['warnings'], 'TARGET_GARMENT_IDENTITY_UNVERIFIED']));
            $result['abstentions'] = array_values(array_unique([...($result['abstentions'] ?? []), 'COLORIMETRY_INSUFFICIENT_EVIDENCE']));
            if ($result['status'] === 'completed') $result['status'] = 'partial';
            $result['human_review_required'] = true;
            return $result;
        }

        $colorimetry = $result['data']['colorimetry'];
        if (!$this->validColorimetry($colorimetry)) {
            unset($result['data']['colorimetry']);
            $result['data']['colors'] = [
                ...$this->legacyColors($result['data']['colors'], []),
                'dominant_color' => null, 'secondary_colors' => [], 'palette' => [],
                'foreground_confidence' => 0, 'coverage' => 0,
                'commercial_color_status' => 'insufficient_evidence',
                'warnings' => ['COLORIMETRY_INVALID_RESPONSE', 'TARGET_GARMENT_IDENTITY_UNVERIFIED'],
            ];
            $result['warnings'] = array_values(array_unique([...$result['warnings'], 'COLORIMETRY_INVALID_RESPONSE', 'TARGET_GARMENT_IDENTITY_UNVERIFIED']));
            $result['abstentions'] = array_values(array_unique([...($result['abstentions'] ?? []), 'COLORIMETRY_INSUFFICIENT_EVIDENCE']));
            if ($result['status'] === 'completed') $result['status'] = 'partial';
            $result['human_review_required'] = true;
            return $result;
        }

        if ($colorimetry['foreground_confidence'] < 0.70 || $colorimetry['status'] === 'insufficient_evidence') {
            $colorimetry['status'] = 'insufficient_evidence';
            // Keep valid observed swatches for diagnosis; never authorize their IDs.
            foreach (['temperature', 'lightness', 'chroma', 'saturation', 'neutrality', 'internal_contrast', 'color_variety'] as $property) {
                $colorimetry[$property] = 'unknown';
            }
            $colorimetry['warnings'] = array_values(array_unique([...$colorimetry['warnings'], 'BACKGROUND_INTERFERENCE']));
        }
        if ($result['status'] === 'completed' && $colorimetry['status'] !== 'completed') $result['status'] = 'partial';

        // No target-garment verifier exists in this pipeline. A declaration from
        // a client or a high geometric confidence cannot establish that identity.
        $colorimetry['commercial_color_status'] = 'insufficient_evidence';
        $colorimetry['evidence']['observation_status'] = $colorimetry['status'];
        $colorimetry['evidence']['target_garment_identity_verified'] = false;
        $colorimetry['evidence']['target_garment_identity_method'] = 'not_implemented';
        $colorimetry['evidence']['properties_scope'] = 'observed_geometric_foreground_only';
        $colorimetry['warnings'] = array_values(array_unique([...$colorimetry['warnings'], 'TARGET_GARMENT_IDENTITY_UNVERIFIED']));
        $result['abstentions'] = array_values(array_unique([...($result['abstentions'] ?? []), 'COLORIMETRY_INSUFFICIENT_EVIDENCE']));
        if ($result['status'] === 'completed') $result['status'] = 'partial';

        $reliable = $colorimetry['evidence']['target_garment_identity_verified'] === true
            && $colorimetry['foreground_confidence'] >= 0.70
            && $colorimetry['status'] !== 'insufficient_evidence'
            && data_get($colorimetry, 'evidence.illumination.reliable') === true
            && !array_intersect(self::LIGHTING_WARNINGS, $colorimetry['warnings']);
        $palette = array_map(fn ($swatch) => $this->catalogSwatch($swatch, $available, $reliable), $colorimetry['palette']);

        // All roles point to the same reconciled observations, including their evidence.
        $primary = $this->paletteSwatch($colorimetry['primary_color'] ?? null, $palette);
        if (in_array('NO_SINGLE_DOMINANT_COLOR', $colorimetry['warnings'], true)) $primary = null;
        $colorimetry['palette'] = $palette;
        $colorimetry['primary_color'] = $primary;
        foreach (['secondary_colors', 'accent_colors'] as $role) {
            $colorimetry[$role] = array_values(array_filter(array_map(
                fn ($swatch) => $this->paletteSwatch($swatch, $palette), $colorimetry[$role],
            )));
        }
        $result['data']['colorimetry'] = $colorimetry;
        $result['warnings'] = array_values(array_unique([...$result['warnings'], ...$colorimetry['warnings']], SORT_REGULAR));
        // Rebuild legacy aliases so a refused advanced match cannot bypass the guard.
        $result['data']['colors'] = [
            ...$result['data']['colors'],
            'dominant_color' => $primary ? $this->legacySwatch($primary) : null,
            'secondary_colors' => array_values(array_map(fn ($swatch) => $this->legacySwatch($swatch), array_filter(
                $palette, fn ($swatch) => $primary === null || $swatch['hex'] !== $primary['hex'] || $swatch['lab'] !== $primary['lab'],
            ))),
            'palette' => array_map(fn ($swatch) => $this->legacySwatch($swatch), $palette),
            'foreground_confidence' => $colorimetry['foreground_confidence'],
            'commercial_color_status' => $colorimetry['commercial_color_status'],
            'warnings' => $colorimetry['warnings'],
            'coverage' => $colorimetry['coverage'],
        ];
        $result['human_review_required'] = true;
        return $result;
    }

    private function catalogSwatch(array $swatch, array $available, bool $reliable): array
    {
        $id = $swatch['catalog_color_id'] ?? null;
        $entry = $id !== null ? ($available[(string) $id] ?? null) : null;
        $distance = $swatch['delta_e'] ?? null;
        $margin = data_get($swatch, 'evidence.catalog_match_margin_delta_e');
        $hex = $entry['hex'] ?? null;
        $catalogFamily = mb_strtoupper((string) ($entry['family'] ?? ''));
        $catalogName = mb_strtoupper((string) ($entry['name'] ?? ''));
        $metallic = in_array($catalogFamily, ['DORADO', 'PLATEADO', 'METALIZADO', 'METALIZADOS'], true)
            || preg_match('/DORAD|PLATEAD|METALIZ|BRONCE|COBRE/', $catalogName);
        $accepted = $reliable && $entry && !$metallic
            && is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex)
            && $swatch['family'] !== 'metallic'
            && data_get($swatch, 'evidence.catalog_match_accepted') === true
            && data_get($swatch, 'evidence.catalog_reference_source') === 'catalog_hex'
            && data_get($swatch, 'evidence.illumination_reliable') === true
            && $this->numberInRange($distance, 0, 10)
            && ($distance <= 2 || $this->numberInRange($margin, 2, PHP_FLOAT_MAX))
            && $swatch['confidence_score'] >= 0.55;

        $swatch['catalog_color_id'] = $accepted ? $entry['id'] : null;
        $swatch['catalog_color_name'] = $accepted ? $entry['name'] : null;
        // Extra legacy fields may be retained by the additive Python schema.
        if (array_key_exists('catalog_id', $swatch)) $swatch['catalog_id'] = $swatch['catalog_color_id'];
        if (array_key_exists('catalog_name', $swatch)) $swatch['catalog_name'] = $swatch['catalog_color_name'];
        $swatch['evidence']['catalog_validated_by'] = 'laravel';
        $swatch['evidence']['catalog_match_accepted'] = (bool) $accepted;
        if (!$accepted) $swatch['evidence']['catalog_match_confidence_score'] = 0.0;
        if ($accepted) {
            $swatch['evidence']['catalog_reference_hex'] = $hex;
            $swatch['evidence']['catalog_family'] = $entry['family'] ?? null;
        } elseif ($id !== null) {
            $swatch['evidence']['catalog_match_reason'] = $entry
                ? 'target_garment_identity_unverified' : 'catalog_color_unavailable';
        }
        return $swatch;
    }

    private function paletteSwatch(?array $swatch, array $palette): ?array
    {
        if ($swatch === null) return null;
        foreach ($palette as $entry) {
            if ($entry['hex'] === $swatch['hex'] && $entry['lab'] === $swatch['lab']) return $entry;
        }
        return null;
    }

    private function legacySwatch(array $swatch): array
    {
        return [
            ...$swatch, 'catalog_id' => $swatch['catalog_color_id'],
            'catalog_name' => $swatch['catalog_color_name'],
            'distance_delta_e' => $swatch['delta_e'], 'source' => 'garment_colorimetry_delta_e_2000',
        ];
    }

    private function legacyColors(array $colors, array $available): array
    {
        $normalize = function ($swatch) use ($available) {
            if (!is_array($swatch)) return null;
            $id = $swatch['catalog_id'] ?? null;
            $entry = is_int($id) || is_string($id) ? ($available[(string) $id] ?? null) : null;
            $swatch['catalog_id'] = $entry['id'] ?? null;
            $swatch['catalog_name'] = $entry['name'] ?? null;
            $swatch['evidence'] = is_array($swatch['evidence'] ?? null) ? $swatch['evidence'] : [];
            $swatch['evidence']['catalog_match_accepted'] = false;
            $swatch['evidence']['catalog_match_reason'] = 'target_garment_identity_unverified';
            $swatch['evidence']['catalog_match_confidence_score'] = 0.0;
            if (array_key_exists('catalog_color_id', $swatch)) $swatch['catalog_color_id'] = $entry['id'] ?? null;
            if (array_key_exists('catalog_color_name', $swatch)) $swatch['catalog_color_name'] = $entry['name'] ?? null;
            return $swatch;
        };
        $colors['dominant_color'] = $normalize($colors['dominant_color'] ?? null);
        foreach (['palette', 'secondary_colors'] as $field) {
            $colors[$field] = array_values(array_filter(array_map($normalize, is_array($colors[$field] ?? null) ? $colors[$field] : [])));
        }
        return $colors;
    }

    private function validColorimetry(mixed $value): bool
    {
        if (!is_array($value)
            || !is_string($value['version'] ?? null) || mb_strlen($value['version']) > 100
            || !in_array($value['status'] ?? null, ['completed', 'partial', 'insufficient_evidence'], true)
            || !$this->numberInRange($value['foreground_confidence'] ?? null, 0, 1)
            || !$this->numberInRange($value['coverage'] ?? null, 0, 1)
            || !is_array($value['evidence'] ?? null) || !is_array($value['metrics'] ?? null)
            || !is_array($value['warnings'] ?? null) || !array_is_list($value['warnings']) || count($value['warnings']) > 40) return false;

        foreach ($value['warnings'] as $warning) {
            if (!is_string($warning) || mb_strlen($warning) > 160) return false;
        }
        foreach (['lightness', 'chroma', 'saturation', 'neutrality', 'internal_contrast'] as $property) {
            if (!in_array($value[$property] ?? null, ['low', 'medium', 'high', 'unknown'], true)) return false;
        }
        if (!in_array($value['temperature'] ?? null, ['warm', 'cool', 'neutral', 'warm_neutral', 'cool_neutral', 'unknown'], true)
            || !in_array($value['color_variety'] ?? null, ['monochrome', 'bicolor', 'multicolor', 'unknown'], true)
            || (($value['dominant_family'] ?? null) !== null && !in_array($value['dominant_family'], self::FAMILIES, true))) return false;
        foreach (['palette', 'secondary_colors', 'accent_colors'] as $role) {
            if (!is_array($value[$role] ?? null) || !array_is_list($value[$role]) || count($value[$role]) > 12) return false;
            foreach ($value[$role] as $swatch) {
                if (!$this->validSwatch($swatch)) return false;
            }
        }
        return ($value['primary_color'] ?? null) === null || $this->validSwatch($value['primary_color']);
    }

    private function validSwatch(mixed $swatch): bool
    {
        if (!is_array($swatch) || !is_string($swatch['hex'] ?? null) || !preg_match('/^#[0-9a-fA-F]{6}$/', $swatch['hex'])
            || !$this->numberInRange($swatch['percentage'] ?? null, 0, 1)
            || !$this->numberInRange($swatch['confidence_score'] ?? null, 0, 1)
            || !is_array($swatch['evidence'] ?? null)
            || !in_array($swatch['family'] ?? null, self::FAMILIES, true)
            || !is_string($swatch['descriptive_name'] ?? null) || mb_strlen($swatch['descriptive_name']) > 160
            || (($swatch['delta_e'] ?? null) !== null && !$this->numberInRange($swatch['delta_e'], 0, PHP_FLOAT_MAX))
            || (($swatch['catalog_color_id'] ?? null) !== null && !is_int($swatch['catalog_color_id']) && !is_string($swatch['catalog_color_id']))) return false;
        foreach (['rgb', 'lab'] as $space) {
            if (!is_array($swatch[$space] ?? null) || !array_is_list($swatch[$space]) || count($swatch[$space]) !== 3) return false;
        }
        foreach ($swatch['rgb'] as $channel) {
            if (!is_int($channel) || $channel < 0 || $channel > 255) return false;
        }
        foreach ($swatch['lab'] as $channel) {
            if ((!is_int($channel) && !is_float($channel)) || !is_finite((float) $channel)) return false;
        }
        return $this->numberInRange($swatch['lab'][0], 0, 100);
    }

    private function numberInRange(mixed $number, float $min, float $max): bool
    {
        return (is_int($number) || is_float($number)) && is_finite((float) $number) && $number >= $min && $number <= $max;
    }
}
