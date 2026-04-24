<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Giocate da verificare — {{ $date }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222;">
    <h2>Giocate da verificare a mano — {{ $date }}</h2>

    <p><strong>Totale:</strong> {{ $count }}</p>

    @if (! empty($breakdown))
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #ccc;">
            <thead style="background: #f5f5f5;">
                <tr>
                    <th align="left">Categoria</th>
                    <th align="right">Giocate</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($breakdown as $label => $n)
                    <tr>
                        <td>{{ $label }}</td>
                        <td align="right">{{ $n }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p style="margin-top: 20px;">
        <a href="{{ url('/admin/plays?tableFilters[to_verify_manually][isActive]=true') }}">
            Apri lista in backoffice
        </a>
    </p>
</body>
</html>
