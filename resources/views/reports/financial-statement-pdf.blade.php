<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 24px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0 0 4px; color: #111827; font-size: 16px; }
        .meta { margin-bottom: 14px; color: #6b7280; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { padding: 7px 6px; color: #fff; background: #2563eb; font-size: 8px; text-align: left; }
        td { padding: 6px; border: 1px solid #d1d5db; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .right { text-align: right; }
        .empty { padding: 12px; color: #6b7280; text-align: center; }
        .footer { margin-top: 10px; color: #6b7280; font-size: 8px; }
        .section-label { padding: 6px; background: #e5e7eb; font-weight: bold; }
        .subtotal-row td { font-weight: bold; background: #eff6ff; }
        .grand-footer { margin-top: 6px; padding: 8px 10px; background: #111827; color: #fff; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Dibuat pada {{ $generatedAt->format('d-m-Y H:i:s') }}</div>

    @foreach ($sections as $section)
        <table>
            @if ($section['label'])
                <tr><td colspan="{{ count($columns) }}" class="section-label">{{ $section['label'] }}</td></tr>
            @endif
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th class="{{ ($column['align'] ?? 'left') === 'right' ? 'right' : '' }}">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($section['rows'] as $row)
                    <tr>
                        @foreach ($columns as $column)
                            <td class="{{ ($column['align'] ?? 'left') === 'right' ? 'right' : '' }}">{{ $row[$column['key']] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="empty">Tidak ada data.</td>
                    </tr>
                @endforelse
                @if (!empty($section['subtotal']))
                    <tr class="subtotal-row">
                        @foreach ($columns as $column)
                            <td class="{{ ($column['align'] ?? 'left') === 'right' ? 'right' : '' }}">{{ $section['subtotal'][$column['key']] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach

    @if (!empty($footer))
        <div class="grand-footer">{{ $footer }}</div>
    @endif

    <div class="footer">Dokumen ini dihasilkan otomatis dari sistem WMS.</div>
</body>
</html>
