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
        .filters { margin-bottom: 10px; padding: 7px 9px; background: #f3f4f6; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 7px 6px; color: #fff; background: #2563eb; font-size: 8px; text-align: left; }
        td { padding: 6px; border: 1px solid #d1d5db; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .right { text-align: right; }
        .empty { padding: 20px; color: #6b7280; text-align: center; }
        .footer { margin-top: 10px; color: #6b7280; font-size: 8px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Dibuat pada {{ $generatedAt->format('d-m-Y H:i:s') }}</div>

    @if ($search !== '' || $filters->isNotEmpty())
        <div class="filters">
            @if ($search !== '')
                <strong>Pencarian:</strong> {{ $search }}
            @endif
            @foreach ($filters as $key => $value)
                @if ($search !== '' || !$loop->first) &nbsp;|&nbsp; @endif
                <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 24px;">No</th>
                @foreach ($columns as $column)
                    <th class="{{ $column['align'] === 'right' ? 'right' : '' }}">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        <td class="{{ $column['align'] === 'right' ? 'right' : '' }}">{{ $row[$column['key']] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="empty">Tidak ada data yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Total data: {{ $rows->count() }}</div>
</body>
</html>
