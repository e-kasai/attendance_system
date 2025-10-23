@props(["rows" => [], "button" => null, "isEditable" => false])

<div class="detail-table">
    <table class="detail-table__inner">
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <th class="detail-table__label">{{ $row["label"] }}</th>
                    <td class="detail-table__value">
                        @if ($isEditable && isset($row["name"]))
                            {{-- 時間入力フィールド --}}

                            @if ($row["type"] === "time-range")
                                <div class="detail-table__time-range">
                                    <input type="time" name="{{ $row["name"][0] }}" value="{{ $row["value"][0] }}" />
                                    <span class="detail-table__tilde">〜</span>
                                    <input type="time" name="{{ $row["name"][1] }}" value="{{ $row["value"][1] }}" />
                                </div>
                            @elseif ($row["type"] === "time")
                                <input type="time" name="{{ $row["name"] }}" value="{{ $row["value"] }}" />
                            @elseif ($row["type"] === "textarea")
                                <textarea name="{{ $row["name"] }}">{{ $row["value"] }}</textarea>
                            @else
                                {{ $row["value"] }}
                            @endif
                        @else
                            {{ $row["value"] }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($button)
        <form method="POST" action="{{ $button["route"] ?? "#" }}">
            @csrf
            <button type="{{ $button["type"] ?? "button" }}" class="detail-table__btn">
                {{ $button["text"] }}
            </button>
        </form>
    @endif
</div>
