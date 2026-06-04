<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InventoryMovementService
{
    private const SENSITIVE_KEY_PARTS = [
        'archivo',
        'comprobante',
        'password',
        'remember',
        'secret',
        'token',
        'url',
        'ruta',
    ];

    public function __construct(
        private readonly SystemLogService $logs,
    ) {
    }

    public function record(
        Inventario $inventario,
        string $tipoMovimiento,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        $safeMetadata = $this->sanitizeMetadata($metadata);
        $locationBefore = $this->metadataString($safeMetadata, 'ubicacion_antes')
            ?? $this->metadataString($safeMetadata, 'location_before')
            ?? $this->stringOrNull($inventario->ubi_inv);
        $locationAfter = $this->metadataString($safeMetadata, 'ubicacion_despues')
            ?? $this->metadataString($safeMetadata, 'location_after')
            ?? $this->stringOrNull($inventario->ubi_inv);

        $movement = InventarioMovimiento::query()->create([
            'cod_inventario_inm' => $inventario->getKey(),
            'cod_prenda_variante_inm' => (int) $inventario->cod_prenda_variantes_inv,
            'tipo_movimiento_inm' => $this->normalizeSegment($tipoMovimiento, 50),
            'cantidad_inm' => $cantidad,
            'cdi_antes_inm' => $cdiAntes,
            'cdi_despues_inm' => $cdiDespues,
            'cre_antes_inm' => $creAntes,
            'cre_despues_inm' => $creDespues,
            'ubicacion_antes_inm' => $locationBefore,
            'ubicacion_despues_inm' => $locationAfter,
            'referencia_tipo_inm' => $referenciaTipo ? $this->normalizeSegment($referenciaTipo, 80) : null,
            'referencia_id_inm' => $referenciaId,
            'cod_usuario_inm' => Auth::id(),
            'descripcion_inm' => $descripcion ? Str::limit(trim($descripcion), 2000, '') : null,
            'metadata_inm' => $safeMetadata,
        ]);

        $this->logMovement($movement);

        return $movement;
    }

    public function entrada(
        Inventario $inventario,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'entrada', $cantidad, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    public function reserva(
        Inventario $inventario,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'reserva', $cantidad, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    public function liberacionReserva(
        Inventario $inventario,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'liberacion_reserva', $cantidad, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    public function venta(
        Inventario $inventario,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'venta', $cantidad, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    public function ventaEnRevision(
        Inventario $inventario,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'venta_en_revision', $cantidad, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    public function cancelacionVenta(
        Inventario $inventario,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'cancelacion_venta', $cantidad, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    public function ajusteManual(
        Inventario $inventario,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'ajuste_manual', $cantidad, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    public function cambioUbicacion(
        Inventario $inventario,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'cambio_ubicacion', 0, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    public function conversionReservaVenta(
        Inventario $inventario,
        int $cantidad,
        int $cdiAntes,
        int $cdiDespues,
        int $creAntes,
        int $creDespues,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $descripcion = null,
        array $metadata = [],
    ): ?InventarioMovimiento {
        return $this->record($inventario, 'conversion_reserva_venta', $cantidad, $cdiAntes, $cdiDespues, $creAntes, $creDespues, $referenciaTipo, $referenciaId, $descripcion, $metadata);
    }

    private function logMovement(InventarioMovimiento $movement): void
    {
        $action = $movement->tipo_movimiento_inm === 'ajuste_manual'
            ? 'ajuste_kardex'
            : 'movimiento_kardex';

        $this->logs->info(
            'inventario',
            $action,
            'Movimiento Kardex registrado: ' . $movement->tipo_movimiento_inm . ' para inventario #' . $movement->cod_inventario_inm . '.',
            $movement,
            [
                'movimiento_id' => $movement->id,
                'inventario_id' => $movement->cod_inventario_inm,
                'variante_id' => $movement->cod_prenda_variante_inm,
                'tipo_movimiento' => $movement->tipo_movimiento_inm,
                'cantidad' => $movement->cantidad_inm,
                'referencia_tipo' => $movement->referencia_tipo_inm,
                'referencia_id' => $movement->referencia_id_inm,
            ],
        );
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $clean = [];

        foreach ($metadata as $key => $value) {
            $stringKey = is_string($key) ? $key : (string) $key;

            if ($this->isSensitiveKey($stringKey)) {
                continue;
            }

            $clean[$stringKey] = $this->sanitizeValue($value);
        }

        return $clean;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeMetadata($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        if (is_string($value)) {
            return Str::limit($value, 500, '');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return Str::limit((string) $value, 500, '');
        }

        return '[' . get_debug_type($value) . ']';
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = Str::lower(Str::ascii($key));

        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($normalized, $part)) {
                return true;
            }
        }

        return false;
    }

    private function metadataString(array $metadata, string $key): ?string
    {
        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $this->stringOrNull($metadata[$key]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text !== '' ? Str::limit($text, 100, '') : null;
    }

    private function normalizeSegment(string $value, int $limit): string
    {
        $normalized = Str::snake(Str::ascii(trim($value)));

        return Str::limit($normalized !== '' ? $normalized : 'sin_definir', $limit, '');
    }
}
