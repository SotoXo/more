<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait InteractsWithCatalogMasters
{
    protected function upsertCanonicalMaster(
        string $table,
        string $idColumn,
        string $nameColumn,
        array $payload,
        array $references = [],
    ): int {
        $payload = $this->filterExistingColumns($table, $payload);
        $canonicalName = (string) ($payload[$nameColumn] ?? '');

        $matches = DB::table($table)
            ->select($idColumn, $nameColumn)
            ->get()
            ->filter(fn ($row) => $this->normalizeCatalogSeedKey((string) $row->{$nameColumn}) === $this->normalizeCatalogSeedKey($canonicalName))
            ->sortBy($idColumn)
            ->values();

        $insertPayload = $payload;
        $updatePayload = array_diff_key($payload, ['created_at' => true]);

        if ($matches->isEmpty()) {
            return (int) DB::table($table)->insertGetId($insertPayload, $idColumn);
        }

        $keeperId = (int) $matches->first()->{$idColumn};
        $duplicateIds = $matches
            ->slice(1)
            ->pluck($idColumn)
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($references as $reference) {
            if ($duplicateIds === []) {
                break;
            }

            DB::table($reference['table'])
                ->whereIn($reference['column'], $duplicateIds)
                ->update([$reference['column'] => $keeperId]);
        }

        if ($duplicateIds !== []) {
            DB::table($table)->whereIn($idColumn, $duplicateIds)->delete();
        }

        DB::table($table)
            ->where($idColumn, $keeperId)
            ->update($updatePayload);

        return $keeperId;
    }

    protected function filterExistingColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    protected function normalizeCatalogSeedKey(string $value): string
    {
        return (string) Str::of(Str::ascii($value))
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->squish();
    }
}
