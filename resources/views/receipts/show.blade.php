<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $receipt['brand'] }} | {{ $receipt['title'] }} {{ $receipt['number'] }}</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #1d1a17;
            --muted: #6c625b;
            --line: #ded8d1;
            --soft: #f7f3ef;
            --accent: #8a6a4f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eee8e2;
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .page {
            width: min(960px, calc(100% - 32px));
            margin: 32px auto;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 20px 60px rgba(29, 26, 23, 0.12);
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 18px 24px;
            border-bottom: 1px solid var(--line);
            background: var(--soft);
        }

        .button {
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            color: var(--ink);
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            padding: 11px 16px;
            text-decoration: none;
            text-transform: uppercase;
        }

        .button.primary {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
        }

        .receipt {
            padding: 42px;
        }

        .header {
            align-items: flex-start;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            padding-bottom: 28px;
            border-bottom: 2px solid var(--ink);
        }

        .brand {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 34px;
            letter-spacing: 0.02em;
            line-height: 1;
        }

        .title {
            color: var(--accent);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.22em;
            margin-top: 12px;
            text-transform: uppercase;
        }

        .number {
            text-align: right;
        }

        .number strong {
            display: block;
            font-size: 18px;
            margin-top: 4px;
        }

        .grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 28px;
        }

        .box {
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px;
        }

        .label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .value {
            font-size: 15px;
            margin-top: 8px;
        }

        table {
            border-collapse: collapse;
            margin-top: 28px;
            width: 100%;
        }

        th {
            background: var(--soft);
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 0.12em;
            padding: 12px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            padding: 14px 12px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .totals {
            display: grid;
            gap: 10px;
            margin: 26px 0 0 auto;
            max-width: 360px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 1px solid var(--line);
            padding: 10px 0;
        }

        .total-row:last-child {
            border-bottom: 2px solid var(--ink);
            font-size: 18px;
            font-weight: 800;
        }

        .details {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 28px;
        }

        .note {
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
            margin-top: 34px;
            padding-top: 22px;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                border: 0;
                box-shadow: none;
                margin: 0;
                width: 100%;
            }

            .toolbar {
                display: none;
            }

            .receipt {
                padding: 24px;
            }
        }

        @media (max-width: 720px) {
            .header,
            .grid,
            .details {
                grid-template-columns: 1fr;
            }

            .header {
                display: block;
            }

            .number {
                margin-top: 24px;
                text-align: left;
            }

            .receipt {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="toolbar">
            <a class="button" href="{{ $returnUrl }}">Volver</a>
            <button class="button primary" type="button" onclick="window.print()">Imprimir</button>
        </div>

        <section class="receipt">
            <header class="header">
                <div>
                    <div class="brand">{{ $receipt['brand'] }}</div>
                    <div class="title">{{ $receipt['title'] }}</div>
                </div>
                <div class="number">
                    <span class="label">{{ $receipt['numberLabel'] }}</span>
                    <strong>{{ $receipt['number'] }}</strong>
                    <div style="margin-top: 12px;">
                        <span class="label">Fecha de emision</span>
                        <div class="value">{{ \Illuminate\Support\Carbon::parse($receipt['issuedAt'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </header>

            <div class="grid">
                <div class="box">
                    <div class="label">Cliente</div>
                    <div class="value">{{ $receipt['customer'] }}</div>
                </div>
                <div class="box">
                    <div class="label">{{ $receipt['responsibleLabel'] }}</div>
                    <div class="value">{{ $receipt['responsible'] }}</div>
                </div>
                @if($receipt['referenceLabel'] && $receipt['reference'])
                    <div class="box">
                        <div class="label">{{ $receipt['referenceLabel'] }}</div>
                        <div class="value">{{ $receipt['reference'] }}</div>
                    </div>
                @endif
                @if($receipt['typeLabel'] && $receipt['type'])
                    <div class="box">
                        <div class="label">{{ $receipt['typeLabel'] }}</div>
                        <div class="value">{{ $receipt['type'] }}</div>
                    </div>
                @endif
            </div>

            <table>
                <thead>
                    <tr>
                        <th>{{ $receipt['productsTitle'] }}</th>
                        <th>SKU</th>
                        <th>Color</th>
                        <th>Talla</th>
                        <th class="right">Cant.</th>
                        <th class="right">Unitario</th>
                        <th class="right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt['items'] as $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td>{{ $item['sku'] }}</td>
                            <td>{{ $item['color'] }}</td>
                            <td>{{ $item['size'] }}</td>
                            <td class="right">{{ $item['quantity'] }}</td>
                            <td class="right">Bs {{ number_format($item['unitPrice'], 2, '.', ',') }}</td>
                            <td class="right">Bs {{ number_format($item['subtotal'], 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                @foreach($receipt['totals'] as $row)
                    <div class="total-row">
                        <span>{{ $row['label'] }}</span>
                        <strong>Bs {{ number_format($row['value'], 2, '.', ',') }}</strong>
                    </div>
                @endforeach
            </div>

            <div class="details">
                @foreach($receipt['details'] as $detail)
                    <div class="box">
                        <div class="label">{{ $detail['label'] }}</div>
                        <div class="value">{{ $detail['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <p class="note">{{ $receipt['legalText'] }}</p>
        </section>
    </main>
</body>
</html>
