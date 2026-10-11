<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\AI\RagGateV2Retriever;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RagController extends Controller
{
    public function search(Request $request, RagGateV2Retriever $retriever): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:1', 'max:1000'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'mode' => ['sometimes', Rule::in(['vector', 'hybrid'])],
            'document_type' => ['sometimes', Rule::in(['taxonomy_category', 'comparison', 'color_knowledge', 'style_rule', 'store_policy', 'product_reference', 'technical_explanation'])],
            'source_version' => ['sometimes', 'string', 'min:1', 'max:100'],
            'corpus_version' => ['sometimes', 'string', 'min:1', 'max:100'],
        ]);
        return response()->json($retriever->search($data['query'], (int) ($data['limit'] ?? 3), $data['mode'] ?? 'hybrid',
            array_intersect_key($data, array_flip(['document_type', 'source_version', 'corpus_version']))));
    }
}
