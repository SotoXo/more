<?php

namespace Tests\Unit;

use App\Services\AI\ColorimetryCatalogGuard;
use Tests\TestCase;

class ColorimetryCatalogGuardTest extends TestCase
{
    private function swatch(): array
    {
        return [
            'hex' => '#d9c5a0', 'rgb' => [217, 197, 160], 'lab' => [80.0, 3.0, 21.0],
            'percentage' => 1.0, 'family' => 'beige_cream', 'descriptive_name' => 'beige',
            'catalog_color_id' => 7, 'catalog_color_name' => 'BEIGE',
            'catalog_id' => 7, 'catalog_name' => 'BEIGE', 'delta_e' => 0.0,
            'confidence_score' => 0.9,
            'evidence' => ['catalog_match_accepted' => true, 'catalog_reference_source' => 'catalog_hex',
                'illumination_reliable' => true, 'catalog_match_margin_delta_e' => 9.0],
        ];
    }

    private function response(): array
    {
        $swatch = $this->swatch();
        return ['status' => 'completed', 'warnings' => [], 'human_review_required' => true,
            'data' => ['colors' => ['dominant_color' => $swatch, 'palette' => [$swatch], 'secondary_colors' => []],
                'colorimetry' => [
                    'version' => 'garment-colorimetry-1.0.0', 'status' => 'completed',
                    'foreground_confidence' => 0.94, 'coverage' => 1.0,
                    'palette' => [$swatch], 'primary_color' => $swatch, 'secondary_colors' => [], 'accent_colors' => [],
                    'temperature' => 'warm_neutral', 'lightness' => 'high', 'chroma' => 'low',
                    'saturation' => 'low', 'neutrality' => 'high', 'internal_contrast' => 'low',
                    'color_variety' => 'monochrome', 'dominant_family' => 'beige_cream', 'warnings' => [],
                    'metrics' => ['mean_lightness_l' => 80.0], 'evidence' => ['illumination' => ['reliable' => true]],
                ],
            ],
        ];
    }

    private function normalize(array $response): array
    {
        return app(ColorimetryCatalogGuard::class)->normalize($response, [
            ['id' => 7, 'name' => 'BEIGE', 'hex' => '#d9c5a0', 'family' => 'BEIGE'],
        ]);
    }

    public function test_geometric_confidence_keeps_palette_but_cannot_authorize_catalog_id(): void
    {
        $response = $this->response();
        $normalized = $this->normalize($response);
        $profile = $normalized['data']['colorimetry'];
        $this->assertSame('completed', $profile['status']);
        $this->assertSame('insufficient_evidence', $profile['commercial_color_status']);
        $this->assertFalse($profile['evidence']['target_garment_identity_verified']);
        $this->assertSame(0.94, $profile['foreground_confidence']);
        $this->assertSame(1.0, $profile['coverage']);
        $this->assertSame($response['data']['colorimetry']['metrics'], $profile['metrics']);
        $this->assertSame('#d9c5a0', $profile['primary_color']['hex']);
        $this->assertSame('partial', $normalized['status']);
        $this->assertTrue($normalized['human_review_required']);
        $this->assertContains('COLORIMETRY_INSUFFICIENT_EVIDENCE', $normalized['abstentions']);
        $this->assertContains('TARGET_GARMENT_IDENTITY_UNVERIFIED', $normalized['warnings']);
        foreach ([$profile['palette'][0], $profile['primary_color'],
            $normalized['data']['colors']['dominant_color'], $normalized['data']['colors']['palette'][0]] as $swatch) {
            $this->assertNull($swatch['catalog_color_id']);
            $this->assertNull($swatch['catalog_id']);
            $this->assertNull($swatch['catalog_color_name']);
            $this->assertNull($swatch['catalog_name']);
            $this->assertFalse($swatch['evidence']['catalog_match_accepted']);
        }
    }

    public function test_unsafe_evidence_retains_diagnostics_without_catalog_ids(): void
    {
        foreach (['insufficient', 'foreground', 'lighting', 'background', 'claimed_identity'] as $case) {
            $response = $this->response();
            $profile = &$response['data']['colorimetry'];
            if ($case === 'insufficient') $profile['status'] = 'insufficient_evidence';
            if ($case === 'foreground') $profile['foreground_confidence'] = 0.2;
            if ($case === 'lighting') $profile['evidence']['illumination']['reliable'] = false;
            if ($case === 'background') $profile['warnings'][] = 'BACKGROUND_INTERFERENCE';
            if ($case === 'claimed_identity') {
                $profile['commercial_color_status'] = 'completed';
                $profile['evidence']['target_garment_identity_verified'] = true;
            }
            unset($profile);
            $normalized = $this->normalize($response);
            $this->assertCount(1, $normalized['data']['colorimetry']['palette'], $case);
            $this->assertNull($normalized['data']['colorimetry']['palette'][0]['catalog_color_id'], $case);
            $this->assertNull($normalized['data']['colors']['palette'][0]['catalog_id'], $case);
            $this->assertFalse($normalized['data']['colorimetry']['evidence']['target_garment_identity_verified'], $case);
            $this->assertSame('insufficient_evidence', $normalized['data']['colorimetry']['commercial_color_status'], $case);
        }
    }

    public function test_legacy_service_cannot_bypass_commercial_abstention(): void
    {
        $response = $this->response();
        unset($response['data']['colorimetry']);
        $response['data']['colors']['secondary_colors'] = [$this->swatch()];
        $normalized = $this->normalize($response);
        $this->assertSame('#d9c5a0', $normalized['data']['colors']['dominant_color']['hex']);
        $this->assertSame('insufficient_evidence', $normalized['data']['colors']['commercial_color_status']);
        foreach (['palette', 'secondary_colors'] as $role) {
            $this->assertCount(1, $normalized['data']['colors'][$role]);
            $this->assertNull($normalized['data']['colors'][$role][0]['catalog_id']);
            $this->assertNull($normalized['data']['colors'][$role][0]['catalog_color_id']);
            $this->assertFalse($normalized['data']['colors'][$role][0]['evidence']['catalog_match_accepted']);
        }
        $this->assertNull($normalized['data']['colors']['dominant_color']['catalog_id']);
        $this->assertContains('TARGET_GARMENT_IDENTITY_UNVERIFIED', $normalized['warnings']);
    }

    public function test_malformed_palette_is_rejected_without_leaking_legacy_id(): void
    {
        $response = $this->response();
        $response['data']['colorimetry']['palette'][0]['rgb'] = [999, 0, 0];
        $normalized = $this->normalize($response);
        $this->assertArrayNotHasKey('colorimetry', $normalized['data']);
        $this->assertNull($normalized['data']['colors']['dominant_color']);
        $this->assertSame([], $normalized['data']['colors']['palette']);
        $this->assertContains('COLORIMETRY_INVALID_RESPONSE', $normalized['warnings']);
        $this->assertSame('insufficient_evidence', $normalized['data']['colors']['commercial_color_status']);
        $this->assertContains('COLORIMETRY_INSUFFICIENT_EVIDENCE', $normalized['abstentions']);
    }
}
