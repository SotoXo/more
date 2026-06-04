<?php

namespace App\Services;

use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    private const BACKUP_DIRECTORY = 'backups/database';

    public function __construct(private readonly SystemLogService $logs)
    {
    }

    public function createManualBackup(?User $user): DatabaseBackup
    {
        $timestamp = Carbon::now();
        $fileName = 'mati_boutique_backup_' . $timestamp->format('Ymd_His') . '.sql';
        $relativePath = self::BACKUP_DIRECTORY . '/' . $fileName;

        $backup = DatabaseBackup::query()->create([
            'nombre_bak' => 'Backup manual ' . $timestamp->format('d/m/Y H:i:s'),
            'archivo_bak' => $fileName,
            'ruta_bak' => $relativePath,
            'disco_bak' => 'local',
            'estado_bak' => 'pendiente',
            'cod_usuario_bak' => $user?->id,
            'fec_bak' => $timestamp,
        ]);

        $absoluteDirectory = storage_path('app/' . self::BACKUP_DIRECTORY);
        $absolutePath = storage_path('app/' . $relativePath);

        try {
            File::ensureDirectoryExists($absoluteDirectory, 0750, true);

            if (!$this->pgDumpExists()) {
                throw new \RuntimeException('pg_dump no está disponible dentro del contenedor Laravel.');
            }

            $connection = config('database.default');
            $database = config("database.connections.$connection");

            if (($database['driver'] ?? null) !== 'pgsql') {
                throw new \RuntimeException('La conexión activa no usa PostgreSQL.');
            }

            $password = (string) ($database['password'] ?? '');
            $process = new Process([
                'pg_dump',
                '-h',
                (string) ($database['host'] ?? 'pgsql'),
                '-p',
                (string) ($database['port'] ?? 5432),
                '-U',
                (string) ($database['username'] ?? 'sail'),
                '-d',
                (string) ($database['database'] ?? ''),
                '--clean',
                '--if-exists',
                '--no-owner',
                '--no-privileges',
                '-f',
                $absolutePath,
            ], null, [
                'PGPASSWORD' => $password,
            ]);
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($this->safeMessage($process->getErrorOutput() ?: $process->getOutput(), $password));
            }

            clearstatcache(true, $absolutePath);

            if (!File::exists($absolutePath)) {
                throw new \RuntimeException('pg_dump finalizó, pero no se encontró el archivo generado.');
            }

            $backup->update([
                'estado_bak' => 'exitoso',
                'tamano_bak' => File::size($absolutePath),
                'mensaje_bak' => 'Backup generado correctamente.',
                'fec_bak' => Carbon::now(),
            ]);

            $this->logs->info(
                'backups',
                'backup_generado',
                'Backup de base de datos generado correctamente.',
                $backup->fresh(),
                [
                    'backup_id' => $backup->id,
                    'filename' => $backup->archivo_bak,
                    'tamano_bytes' => $backup->tamano_bak,
                    'estado' => 'exitoso',
                ],
            );

            return $backup->fresh();
        } catch (\Throwable $exception) {
            if (File::exists($absolutePath) && (File::size($absolutePath) ?? 0) === 0) {
                File::delete($absolutePath);
            }

            $message = $this->safeMessage($exception->getMessage(), config("database.connections." . config('database.default') . ".password"));

            $backup->update([
                'estado_bak' => 'fallido',
                'tamano_bak' => File::exists($absolutePath) ? File::size($absolutePath) : null,
                'mensaje_bak' => $message,
                'fec_bak' => Carbon::now(),
            ]);

            $this->logs->critical(
                'backups',
                'backup_fallido',
                'Fallo al generar backup de base de datos.',
                $backup->fresh(),
                [
                    'backup_id' => $backup->id,
                    'filename' => $backup->archivo_bak,
                    'estado' => 'fallido',
                    'mensaje' => Str::limit($message, 300, ''),
                ],
            );

            return $backup->fresh();
        }
    }

    public function absolutePath(DatabaseBackup $backup): string
    {
        return storage_path('app/' . ltrim($backup->ruta_bak, '/'));
    }

    public function isDownloadable(DatabaseBackup $backup): bool
    {
        if ($backup->estado_bak !== 'exitoso') {
            return false;
        }

        $path = $this->absolutePath($backup);
        $base = realpath(storage_path('app/' . self::BACKUP_DIRECTORY));
        $real = realpath($path);

        return $base !== false
            && $real !== false
            && str_starts_with($real, $base . DIRECTORY_SEPARATOR)
            && File::exists($real);
    }

    private function pgDumpExists(): bool
    {
        $process = new Process(['which', 'pg_dump']);
        $process->run();

        return $process->isSuccessful();
    }

    private function safeMessage(string $message, ?string $password = null): string
    {
        $clean = trim($message) ?: 'Error desconocido al generar backup.';

        if ($password) {
            $clean = str_replace($password, '[password oculto]', $clean);
        }

        return Str::limit($clean, 1000, '');
    }
}
