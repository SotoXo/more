<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryComplementController extends Controller
{
    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $validated = $request->validate([
            'rows' => ['present', 'array', 'max:30'],
            'rows.*.destino_id' => ['required', 'integer', 'distinct',
                Rule::exists('categorias', 'id_categoria'),
                Rule::notIn([$categoria->id_categoria])],
            'rows.*.prioridad' => ['required', 'integer', 'min:1', 'max:100'],
            'rows.*.activo' => ['required', 'boolean'],
        ]);

        $rows = collect($validated['rows']);
        DB::transaction(function () use ($categoria, $rows): void {
            $destinationIds = $rows->pluck('destino_id')->map(fn ($id) => (int) $id)->all();
            $existing = DB::table('categoria_complementarias')
                ->where('cod_categoria_origen_cc', $categoria->id_categoria);
            if ($destinationIds) {
                $existing->whereNotIn('cod_categoria_destino_cc', $destinationIds);
            }
            $existing->delete();

            if ($rows->isNotEmpty()) {
                $now = now();
                DB::table('categoria_complementarias')->upsert(
                    $rows->map(fn (array $row) => [
                        'cod_categoria_origen_cc' => $categoria->id_categoria,
                        'cod_categoria_destino_cc' => (int) $row['destino_id'],
                        'pri_cc' => (int) $row['prioridad'],
                        'est_cc' => (bool) $row['activo'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(),
                    ['cod_categoria_origen_cc', 'cod_categoria_destino_cc'],
                    ['pri_cc', 'est_cc', 'updated_at'],
                );
            }
        });

        return back()->with('success', 'Categorías complementarias actualizadas.');
    }
}
