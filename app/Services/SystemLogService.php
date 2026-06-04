<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SystemLogService
{
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

    public function log(
        string $modulo,
        string $accion,
        string $descripcion,
        ?Model $entidad = null,
        string $nivel = 'info',
        array $metadata = [],
    ): ?SystemLog {
        try {
            $request = request();
            $user = Auth::user();

            if ($user && method_exists($user, 'loadMissing')) {
                $user->loadMissing('rol');
            }

            return SystemLog::query()->create([
                'cod_usuario_slg' => $user?->getAuthIdentifier(),
                'rol_slg' => $this->userRoleName($user),
                'modulo_slg' => $this->normalizeSegment($modulo, 80),
                'accion_slg' => $this->normalizeSegment($accion, 80),
                'entidad_tipo_slg' => $entidad ? $this->normalizeSegment(class_basename($entidad), 100) : null,
                'entidad_id_slg' => $entidad?->getKey(),
                'descripcion_slg' => Str::limit(trim($descripcion), 2000, ''),
                'nivel_slg' => in_array($nivel, self::ALLOWED_LEVELS, true) ? $nivel : 'info',
                'ip_slg' => $request instanceof Request ? $request->ip() : null,
                'user_agent_slg' => $request instanceof Request ? Str::limit((string) $request->userAgent(), 1000, '') : null,
                'metodo_http_slg' => $request instanceof Request ? Str::limit($request->method(), 10, '') : null,
                'ruta_slg' => $request instanceof Request ? Str::limit('/' . ltrim($request->path(), '/'), 255, '') : null,
                'metadata_slg' => $this->sanitizeMetadata($metadata),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function info(
        string $modulo,
        string $accion,
        string $descripcion,
        ?Model $entidad = null,
        array $metadata = [],
    ): ?SystemLog {
        return $this->log($modulo, $accion, $descripcion, $entidad, 'info', $metadata);
    }

    public function warning(
        string $modulo,
        string $accion,
        string $descripcion,
        ?Model $entidad = null,
        array $metadata = [],
    ): ?SystemLog {
        return $this->log($modulo, $accion, $descripcion, $entidad, 'warning', $metadata);
    }

    public function critical(
        string $modulo,
        string $accion,
        string $descripcion,
        ?Model $entidad = null,
        array $metadata = [],
    ): ?SystemLog {
        return $this->log($modulo, $accion, $descripcion, $entidad, 'critical', $metadata);
    }

    private function userRoleName(mixed $user): ?string
    {
        $roleName = $user?->rol?->nom_rol;

        return $roleName ? $this->normalizeSegment((string) $roleName, 50) : null;
    }

    private function normalizeSegment(string $value, int $limit): string
    {
        $normalized = Str::snake(Str::ascii(trim($value)));

        return Str::limit($normalized !== '' ? $normalized : 'sin_definir', $limit, '');
    }

    private function sanitizeMetadata(array $metadata): array
    {
        return $this->sanitizeArray($metadata);
    }

    private function sanitizeArray(array $values): array
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
            return $this->sanitizeArray($value);
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
