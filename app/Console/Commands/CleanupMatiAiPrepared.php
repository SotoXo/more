<?php

namespace App\Console\Commands;

use App\Models\AiRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CleanupMatiAiPrepared extends Command
{
    protected $signature = 'mati-ai:cleanup-prepared {--limit=500 : Máximo de preparaciones vencidas por ejecución}';
    protected $description = 'Elimina solamente archivos temporales IA vencidos y conserva su auditoría.';

    public function handle(): int
    {
        if (!Schema::hasTable('ai_runs')) {
            $this->warn('La migración IA aún no está aplicada.');
            return self::SUCCESS;
        }
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
        if ($limit === false) {
            $this->error('--limit debe estar entre 1 y 10000.');
            return self::INVALID;
        }
        $ids = AiRun::where('operation', 'prepare-publication')->whereNull('prenda_id')
            ->whereNotNull('expires_at')->where('expires_at', '<=', now())->orderBy('expires_at')->limit($limit)->pluck('id');
        $removed = 0;
        foreach ($ids as $id) {
            // The directory is derived from a UUID, never from paths supplied by a model.
            if (!Str::isUuid($id)) continue;
            $removed += DB::transaction(function () use ($id) {
                $run = AiRun::whereKey($id)->whereNull('prenda_id')->where('expires_at', '<=', now())->lockForUpdate()->first();
                if (!$run) return 0;
                $directory = 'mati-ai/prepared/'.$id;
                $disk = Storage::disk('local');
                if ($disk->directoryExists($directory) && !$disk->deleteDirectory($directory)) {
                    $this->warn('No se pudo retirar la preparación '.$id.'.');
                    return 0;
                }
                $result = $run->result;
                $result['data']['temporary_files_removed_at'] = now()->toIso8601String();
                $run->update(['status' => 'expired', 'expires_at' => null, 'result' => $result]);
                return 1;
            });
        }
        $this->info('Preparaciones temporales eliminadas: '.$removed.'. Se conservó la auditoría.');
        return self::SUCCESS;
    }
}
