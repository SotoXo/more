<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogService
{
    private const ALLOWED_ACTIONS = ['created', 'updated', 'deleted', 'restored', 'force_deleted'];

    private const ALLOWED_LEVELS = ['info', 'warning', 'critical'];

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

    public function record(
        string $tabla,
        ?int $registroId,
        string $accion,
        string $modulo,
        array $valoresAnteriores = [],
        array $valoresNuevos = [],
        array $camposModificados = [],
        string $nivel = 'info',
        ?string $descripcion = null,
    ): ?AuditLog {
        try {
            $request = request();
            $action = in_array($accion, self::ALLOWED_ACTIONS, true) ? $accion : 'updated';
            $level = in_array($nivel, self::ALLOWED_LEVELS, true) ? $nivel : 'info';

            return AuditLog::query()->create([
                'cod_usuario_aul' => Auth::id(),
                'tabla_aul' => Str::limit(Str::snake(Str::ascii(trim($tabla))), 100, ''),
                'registro_id_aul' => $registroId,
                'accion_aul' => $action,
                'modulo_aul' => Str::limit(Str::snake(Str::ascii(trim($modulo))), 80, ''),
                'valores_anteriores_aul' => $this->sanitizeValues($valoresAnteriores),
                'valores_nuevos_aul' => $this->sanitizeValues($valoresNuevos),
                'campos_modificados_aul' => array_values(array_unique(array_map('strval', $camposModificados))),
                'descripcion_aul' => $descripcion ? Str::limit(trim($descripcion), 2000, '') : null,
                'nivel_aul' => $level,
                'ip_aul' => method_exists($request, 'ip') ? $request->ip() : null,
                'user_agent_aul' => method_exists($request, 'userAgent') ? Str::limit((string) $request->userAgent(), 1000, '') : null,
                'ruta_aul' => method_exists($request, 'path') ? Str::limit('/' . ltrim($request->path(), '/'), 255, '') : null,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function sanitizeValues(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
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
            return $this->sanitizeValues($value);
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
}
