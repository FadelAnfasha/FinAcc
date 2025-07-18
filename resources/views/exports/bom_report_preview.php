<!DOCTYPE html>
<html>

<head>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            border: 1px solid black;
            padding: 8px;
        }
    </style>
</head>

<body>
    <h2>BOM Report Preview: {{ $data->item_code }}</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Part Code</th>
                <th>Quantity</th>
                <th>Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data->bom as $i => $bom)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $bom->item_code }}</td>
                <td>{{ $bom->quantity }}</td>
                <td>{{ $bom->unit }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>