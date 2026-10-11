<?php

namespace App\Services\AI;

use App\Models\RagChunk;
use App\Models\RagDocument;
use App\Models\RagEmbedding;
use App\Models\RagModelVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class RagCorpusStore
{
    public function __construct(private RagTextClient $client) {}

    public function corpus(): array
    {
        // Only this curated repository resource is ingested; CLI/API accept no filesystem path.
        $path = config('rag.corpus_path');
        if ($path !== base_path('services/mati-ai/resources/rag/corpus-v1.json') || !is_file($path)) {
            throw new RuntimeException('CURATED_CORPUS_UNAVAILABLE');
        }
        return json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
    }

    public function canonicalHash(array $value): string
    {
        $sort = function (array $entry) use (&$sort): array {
            if (!array_is_list($entry)) ksort($entry);
            foreach ($entry as &$child) if (is_array($child)) $child = $sort($child);
            return $entry;
        };
        return hash('sha256', json_encode($sort($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function prepare(array $corpus): array
    {
        if (($corpus['schema_version'] ?? null) !== 'mati-rag-corpus-1'
            || !is_string($corpus['corpus_version'] ?? null)
            || !preg_match('/^[a-zA-Z0-9._-]{1,100}$/', $corpus['corpus_version'])
            || !is_array($corpus['documents'] ?? null) || !array_is_list($corpus['documents'])
            || count($corpus['documents']) < 1 || count($corpus['documents']) > 500) throw new InvalidArgumentException('CORPUS_INVALID');
        $seen = $chunkIds = [];
        $flat = [];
        foreach ($corpus['documents'] as $document) {
            foreach (['id', 'document_type', 'title', 'source', 'source_version'] as $key) {
                if (!is_string($document[$key] ?? null) || trim($document[$key]) === '') throw new InvalidArgumentException('DOCUMENT_INVALID');
            }
            if (!preg_match('/^[a-zA-Z0-9:_-]{1,160}$/', $document['id']) || isset($seen[$document['id']])
                || ($document['status'] ?? 'active') !== 'active'
                || !in_array($document['document_type'], ['taxonomy_category', 'comparison', 'color_knowledge', 'style_rule', 'store_policy', 'product_reference', 'technical_explanation'], true)
                || mb_strlen($document['title']) > 255 || strlen($document['source_version']) > 100
                || !is_array($document['metadata'] ?? null)
                || !is_array($document['chunks'] ?? null) || !array_is_list($document['chunks'])
                || count($document['chunks']) < 1 || count($document['chunks']) > 20) throw new InvalidArgumentException('DOCUMENT_INVALID');
            foreach (($document['metadata']['aliases'] ?? []) as $alias) {
                if (!is_string($alias) || trim($alias) === '' || mb_strlen($alias) > 160) throw new InvalidArgumentException('ALIAS_INVALID');
            }
            if (!is_array($document['metadata']['aliases'] ?? []) || !array_is_list($document['metadata']['aliases'] ?? [])) throw new InvalidArgumentException('ALIAS_INVALID');
            $seen[$document['id']] = true;
            $inputs = [];
            foreach ($document['chunks'] as $index => $chunk) {
                if (!is_string($chunk['id'] ?? null) || !preg_match('/^[a-zA-Z0-9:#_.-]{1,220}$/', $chunk['id'])
                    || isset($chunkIds[$chunk['id']]) || !is_string($chunk['section'] ?? null)
                    || $chunk['section'] === '' || mb_strlen($chunk['section']) > 120
                    || !is_string($chunk['content'] ?? null) || trim($chunk['content']) === ''
                    || !mb_check_encoding($chunk['content'], 'UTF-8')
                    || ($chunk['content_hash'] ?? null) !== hash('sha256', $chunk['content'])) throw new InvalidArgumentException('CHUNK_INVALID');
                $chunkIds[$chunk['id']] = true;
                $input = $document['title']."\n".$chunk['section']."\n".$chunk['content'];
                if (mb_strlen($input) > 8192) throw new InvalidArgumentException('CHUNK_TOO_LONG');
                $inputs[] = $input;
                $flat[] = ['document' => $document, 'chunk' => $chunk, 'index' => $index, 'input' => $input, 'input_hash' => hash('sha256', $input)];
            }
            if (($document['content_hash'] ?? null) !== hash('sha256', implode("\n\n", $inputs))) throw new InvalidArgumentException('DOCUMENT_HASH_INVALID');
        }
        if (count($flat) > 1000) throw new InvalidArgumentException('CORPUS_TOO_LARGE');
        return $flat;
    }

    public function ingest(array $corpus): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || !Schema::hasTable('rag_embeddings')) throw new RuntimeException('RAG_STORAGE_UNAVAILABLE');
        $flat = $this->prepare($corpus);
        $profile = $this->client->profile();
        $spaceKey = $this->client->spaceKey($profile);
        // IDs remain commercial authority: semantic references cannot create or rename a category.
        $categoryIds = [];
        foreach ($corpus['documents'] as $document) {
            foreach (($document['metadata']['category_ids'] ?? []) as $id) {
                if (!is_int($id) || $id < 1) throw new InvalidArgumentException('CATEGORY_REFERENCE_INVALID');
                $categoryIds[$id] = $id;
            }
        }
        if ($categoryIds && DB::table('categorias')->where('est_cat', true)->whereIn('id_categoria', array_values($categoryIds))->count() !== count($categoryIds)) {
            throw new InvalidArgumentException('CATEGORY_REFERENCE_NOT_ACTIVE');
        }
        $this->assertImmutableVersion($corpus);
        $existingModel = RagModelVersion::where('space_key', $spaceKey)->first();
        $activeDocs = RagDocument::where('status', 'active')->get();
        $expectedDocuments = collect($corpus['documents'])->keyBy('id');
        $sameSnapshot = $activeDocs->count() === count($corpus['documents'])
            && $activeDocs->every(fn ($doc) => $doc->corpus_version === $corpus['corpus_version']
                && $expectedDocuments->has($doc->document_key)
                && $doc->snapshot_hash === $this->canonicalHash($expectedDocuments[$doc->document_key]));
        if ($sameSnapshot && $existingModel) {
            $active = RagEmbedding::where('model_version_id', $existingModel->id)->where('status', 'active')
                ->whereHas('chunk.document', fn ($query) => $query->where('status', 'active')->where('corpus_version', $corpus['corpus_version']))->count();
            if ($active === count($flat)) return ['status' => 'completed', 'documents' => count($corpus['documents']), 'chunks' => count($flat), 'indexed' => 0, 'skipped' => count($flat), 'corpus_version' => $corpus['corpus_version'], 'space_key' => $spaceKey];
        }
        // Prepare every vector before entering the activation transaction.
        $entries = [];
        foreach (array_chunk($flat, 16) as $batch) {
            $texts = array_column($batch, 'input');
            // Also bound encoded UTF8 batch bytes; split rather than silently truncate.
            $pending = [];
            $bytes = 0;
            foreach ($texts as $text) {
                if ($pending && $bytes + strlen($text) > 65536) {
                    array_push($entries, ...$this->client->embed($pending, 'document', $profile));
                    $pending = []; $bytes = 0;
                }
                $pending[] = $text; $bytes += strlen($text);
            }
            if ($pending) array_push($entries, ...$this->client->embed($pending, 'document', $profile));
        }
        if (count($entries) !== count($flat)) throw new RuntimeException('INCOMPLETE_TEXT_EMBEDDINGS');
        return DB::transaction(function () use ($corpus, $flat, $entries, $profile, $spaceKey) {
            DB::select('SELECT pg_advisory_xact_lock(?)', [187041031]);
            $this->assertImmutableVersion($corpus);
            $model = RagModelVersion::firstOrCreate(['space_key' => $spaceKey], [
                'vector_space' => 'text', 'model_id' => $profile['model_id'], 'model_revision' => $profile['revision'],
                'pipeline_version' => $profile['pipeline_version'], 'dimensions' => $profile['dimensions'],
                'normalization' => 'l2', 'preprocess_version' => $profile['preprocess_version'],
            ]);
            RagDocument::where('status', 'active')->update(['status' => 'inactive']);
            RagEmbedding::where('status', 'active')->update(['status' => 'inactive']);
            $documents = [];
            $indexed = 0;
            foreach ($flat as $position => $row) {
                $definition = $row['document'];
                $hash = $this->canonicalHash($definition);
                if (!isset($documents[$definition['id']])) {
                    $doc = RagDocument::firstOrCreate(['document_key' => $definition['id'], 'corpus_version' => $corpus['corpus_version']], [
                        'document_type' => $definition['document_type'], 'title' => $definition['title'], 'source' => $definition['source'],
                        'source_version' => $definition['source_version'], 'content_hash' => $definition['content_hash'],
                        'snapshot_hash' => $hash, 'metadata' => $definition['metadata'], 'status' => 'inactive',
                    ]);
                    if ($doc->snapshot_hash !== $hash) throw new InvalidArgumentException('IMMUTABLE_CORPUS_VERSION_CHANGED');
                    $doc->update(['status' => 'active']);
                    $documents[$definition['id']] = $doc;
                }
                $doc = $documents[$definition['id']];
                $chunk = RagChunk::firstOrCreate(['document_id' => $doc->id, 'chunk_key' => $row['chunk']['id']], [
                    'chunk_index' => $row['index'], 'section' => $row['chunk']['section'], 'content' => $row['chunk']['content'],
                    'content_hash' => $row['chunk']['content_hash'],
                    'search_text' => $definition['title'].' '.implode(' ', $definition['metadata']['aliases'] ?? []).' '.$row['chunk']['section'].' '.$row['chunk']['content'],
                    'metadata' => $row['chunk']['metadata'] ?? [],
                ]);
                if ($chunk->content_hash !== $row['chunk']['content_hash']) throw new RuntimeException('STALE_CHUNK');
                $embedding = RagEmbedding::where('chunk_id', $chunk->id)->where('model_version_id', $model->id)->first();
                if (!$embedding) {
                    $vector = json_encode($entries[$position]['vector'], JSON_THROW_ON_ERROR);
                    DB::insert('INSERT INTO rag_embeddings (chunk_id, model_version_id, dimensions, input_hash, norm, status, embedding, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, CAST(? AS vector), ?, ?)', [
                        $chunk->id, $model->id, $profile['dimensions'], $row['input_hash'], $entries[$position]['norm'], 'active', $vector, now(), now(),
                    ]);
                    $indexed++;
                } else {
                    if ($embedding->input_hash !== $row['input_hash']) throw new RuntimeException('STALE_TEXT_EMBEDDING');
                    $embedding->update(['status' => 'active']);
                }
            }
            return ['status' => 'completed', 'documents' => count($documents), 'chunks' => count($flat), 'indexed' => $indexed,
                'skipped' => count($flat) - $indexed, 'corpus_version' => $corpus['corpus_version'], 'space_key' => $spaceKey];
        });
    }

    private function assertImmutableVersion(array $corpus): void
    {
        $stored = RagDocument::where('corpus_version', $corpus['corpus_version'])
            ->pluck('snapshot_hash', 'document_key')->all();
        if (!$stored) return;
        $incoming = [];
        foreach ($corpus['documents'] as $document) $incoming[$document['id']] = $this->canonicalHash($document);
        ksort($stored); ksort($incoming);
        if ($stored !== $incoming) throw new InvalidArgumentException('IMMUTABLE_CORPUS_VERSION_CHANGED');
    }

}
