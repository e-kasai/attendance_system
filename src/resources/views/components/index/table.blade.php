<table class="index-table">
    <thead class="index-table__head">
        <tr>
            @foreach ($headers as $header)
                <th class="index-table__header">{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody class="index-table__body">
        {{ $slot }}
    </tbody>
</table>
