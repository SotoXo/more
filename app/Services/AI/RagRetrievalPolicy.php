<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

class RagRetrievalPolicy
{
    public function normalized(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower(Str::ascii($text)))) ?? '');
    }

    public function toolRequired(string $query): bool
    {
        return (bool) preg_match('/\b(stock|stocks|precio|precios|cuesta|cuestan|cost|price|prices|inventario|inventory|disponibilidad|disponible|disponibles|availability|available|reserva|reservas|reservation|pago|pagos|payment|quedan|queda|existencias)\b/u', $this->normalized($query));
    }

    public function rank(array $rows, string $query, float $minimumCosine, string $mode, int $limit): array
    {
        $normalized = $this->normalized($query);
        $accepted = array_values(array_filter($rows, function ($row) use ($normalized, $minimumCosine, $mode) {
            $aliases = $row['document_type'] === 'taxonomy_category' ? [$row['title'], ...($row['metadata']['aliases'] ?? [])] : [];
            $exact = $normalized !== '' && in_array($normalized, array_map(fn ($alias) => $this->normalized($alias), $aliases), true);
            return $row['similarity'] >= $minimumCosine || ($mode === 'hybrid' && $exact);
        }));
        $vector = $accepted;
        usort($vector, fn ($a, $b) => ($b['similarity'] <=> $a['similarity']) ?: ($a['chunk_id'] <=> $b['chunk_id']));
        $lexical = array_values(array_filter($accepted, fn ($row) => $row['lexical_score'] > 0));
        usort($lexical, fn ($a, $b) => ($b['lexical_score'] <=> $a['lexical_score']) ?: ($a['chunk_id'] <=> $b['chunk_id']));
        $scores = [];
        foreach ($vector as $i => $row) $scores[$row['chunk_id']] = 1 / (60 + $i + 1);
        if ($mode === 'hybrid') {
            foreach ($lexical as $i => $row) $scores[$row['chunk_id']] += 1 / (60 + $i + 1);
            usort($accepted, fn ($a, $b) => ($scores[$b['chunk_id']] <=> $scores[$a['chunk_id']]) ?: ($a['chunk_id'] <=> $b['chunk_id']));
        } else $accepted = $vector;
        foreach ($accepted as &$row) $row['ranking_score'] = $mode === 'hybrid' ? $scores[$row['chunk_id']] : $row['similarity'];
        return array_slice($accepted, 0, $limit);
    }
}
