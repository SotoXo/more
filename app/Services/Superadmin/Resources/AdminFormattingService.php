<?php

namespace App\Services\Superadmin\Resources;

use Illuminate\Support\Carbon;

class AdminFormattingService
{
    public function normalizeUpper(?string $value): ?string
    {
        $normalized = $this->normalizeNullableText($value);

        return $normalized ? mb_strtoupper($normalized, 'UTF-8') : null;
    }

    public function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return $normalized === '' ? null : $normalized;
    }

    public function formatDate($value): string
    {
        if (!$value) {
            return 'Sin fecha';
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value)->timezone(config('app.timezone'))->format('d M Y');
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value->timezone(config('app.timezone'))->format('d M Y');
    }

    public function money(float $value): string
    {
        return 'Bs ' . number_format($value, 2, '.', ',');
    }

    public function booleanStatus(?bool $value, string $positive = 'Activo', string $negative = 'Inactivo'): array
    {
        return $value
            ? ['label' => $positive, 'tone' => 'success']
            : ['label' => $negative, 'tone' => 'danger'];
    }

    public function textStatus(?string $value, string $fallback): array
    {
        $label = $value ?: $fallback;
        $tone = match (strtolower($label)) {
            'cancelada', 'inactivo' => 'danger',
            'pendiente', 'seguimiento' => 'warning',
            'activa', 'entregada', 'completada' => 'success',
            default => 'accent',
        };

        return ['label' => $label, 'tone' => $tone];
    }

    public function userName($user): string
    {
        if (!$user) {
            return '';
        }

        return trim(implode(' ', array_filter([$user->nom_usu, $user->app_usu, $user->apm_usu]))) ?: ($user->email ?? '');
    }

    public function available($inventory): int
    {
        if (!$inventory) {
            return 0;
        }

        if (method_exists($inventory, 'stockDisponible')) {
            return $inventory->stockDisponible();
        }

        return max(0, (int) ($inventory->cdi_inv ?? 0) - (int) ($inventory->cre_inv ?? 0));
    }
}
