<?php

namespace App\Console\Commands;

use App\Services\AI\RagCorpusStore;
use Illuminate\Console\Command;

class IndexRagCorpus extends Command
{
    protected $signature = 'mati-ai:rag-index';
    protected $description = 'Indexa sólo corpus curado versionado de Mati; sin productos ni vectores visuales.';

    public function handle(RagCorpusStore $store): int
    {
        try {
            $result = $store->ingest($store->corpus());
            $this->line(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('RAG_INDEX_FAILED: revisa perfil aprobado, corpus inmutable y servicio textual. No se activó una versión parcial.');
            return self::FAILURE;
        }
    }
}
