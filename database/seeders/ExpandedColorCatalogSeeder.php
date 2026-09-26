<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ExpandedColorCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Each exact swatch is distinct; the family swatch is intentionally shared.
        $families = [
            'BLANCO' => ['#F7F6F2', [
                ['BLANCO HUESO', '#F4F0E8'], ['MARFIL', '#FFF8E7'],
            ]],
            'BEIGE' => ['#D8C3A5', [
                ['ARENA', '#CDBA96'], ['TAUPE', '#8B7D74'], ['CAQUI', '#A69F72'],
            ]],
            'GRIS' => ['#8A8F98', [
                ['GRIS CLARO', '#C8CDD2'], ['GRIS OSCURO', '#4B5059'],
            ]],
            'ROJO' => ['#E53935', [
                ['ROJO', '#E53935'], ['ROJO CARMÍN', '#B31B34'],
                ['TERRACOTA', '#C96B4B'], ['CORAL', '#FF7F6A'],
            ]],
            'NARANJA' => ['#F57C00', [
                ['NARANJA', '#F57C00'], ['NARANJA QUEMADO', '#C65D16'], ['DURAZNO', '#F7B38A'],
            ]],
            'AMARILLO' => ['#F5DC1E', [
                ['AMARILLO PASTEL', '#F7E89B'], ['MOSTAZA', '#C49A20'],
            ]],
            'VERDE' => ['#009B77', [
                ['VERDE MENTA', '#98D8AA'], ['VERDE SALVIA', '#9CAF88'],
                ['VERDE ESMERALDA', '#009B77'], ['VERDE BOTELLA', '#205B45'],
                ['VERDE BOSQUE', '#244D35'], ['VERDE LIMA', '#A4C639'],
            ]],
            'AZUL' => ['#2447D8', [
                ['AZUL CIELO', '#64B5F6'], ['AZUL TURQUESA', '#20B2AA'],
                ['AZUL REY', '#2447D8'], ['AZUL ELÉCTRICO', '#0066FF'],
            ]],
            'MORADO' => ['#6A1B9A', [
                ['LAVANDA', '#B39DDB'], ['LILA', '#C8A2C8'],
                ['VIOLETA', '#8F3FB0'], ['MORADO', '#6A1B9A'], ['CIRUELA', '#673957'],
            ]],
            'ROSA' => ['#F48FB1', [
                ['ROSA BEBÉ', '#F6C9D8'], ['ROSA', '#F48FB1'],
                ['ROSA FUERTE', '#E75480'], ['FUCSIA', '#E91E63'], ['MAGENTA', '#C2185B'],
            ]],
            'MARRON' => ['#6F4E37', [
                ['MARRÓN CLARO', '#A87652'], ['CHOCOLATE', '#4E2E23'],
            ]],
            'DORADO' => ['#C9A227', [['DORADO', '#C9A227']]],
            'PLATEADO' => ['#B5BAC3', [['PLATEADO', '#B5BAC3']]],
        ];

        DB::transaction(function () use ($families): void {
            $existing = Color::query()->get();
            $names = [];
            $hexes = [];
            foreach ($existing as $color) {
                $names[$this->normalizedName($color->nom_col)] = true;
                if ($color->hex_col) {
                    $hexes[strtoupper($color->hex_col)] = $color->nom_col;
                }
            }

            $order = ((int) $existing->max('ord_col')) + 10;
            foreach ($families as $family => [$dominantHex, $shades]) {
                foreach ($shades as [$name, $hex]) {
                    $key = $this->normalizedName($name);
                    if (isset($names[$key])) {
                        continue;
                    }
                    if (isset($hexes[$hex])) {
                        throw new RuntimeException("El HEX {$hex} ya pertenece a {$hexes[$hex]}.");
                    }

                    Color::query()->firstOrCreate(['nom_col' => $name], [
                        'fam_col' => $family,
                        'hex_col' => $hex,
                        'hex_dom_col' => $dominantHex,
                        'ord_col' => $order,
                        'est_col' => true,
                        'fec_col' => now(),
                    ]);
                    $names[$key] = true;
                    $hexes[$hex] = $name;
                    $order += 10;
                }
            }
        });
    }

    private function normalizedName(string $name): string
    {
        return preg_replace('/\s+/u', ' ', Str::upper(Str::ascii(trim($name))));
    }
}
