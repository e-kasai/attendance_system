@props(["rows" => [], "button" => null, "isEditable" => false])

{{-- <div class="detail-table"> --}}
<table class="detail-table">
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <th class="detail-table__label">{{ $row["label"] }}</th>
                <td class="detail-table__value">
                    @if ($isEditable && isset($row["name"]))
                        {{-- 時間入力フィールド --}}
                        {{-- ブラケット形式をドット記法に変換(breaks[0][break_in]→breaks.0.break_in) --}}
                        @php
                            $errorKeys = is_array($row["name"])
                                ? array_map(fn ($name) => str_replace(["[", "]"], [".", ""], $name), $row["name"])
                                : [str_replace(["[", "]"], [".", ""], $row["name"])];
                        @endphp

                        {{-- 出退勤・休憩 --}}
                        @if ($row["type"] === "time-range")
                            {{-- 休憩（既存データ）の場合は hidden id --}}
                            <div class="detail-table__time-range">
                                @if (isset($row["id"]))
                                    <input
                                        type="hidden"
                                        name="{{ str_replace(["[break_in]"], "[id]", $row["name"][0]) }}"
                                        value="{{ $row["id"] }}"
                                    />
                                @endif

                                {{-- 時間入力（開始〜終了） --}}
                                <input
                                    type="time"
                                    name="{{ $row["name"][0] }}"
                                    value="{{ old(str_replace(["[", "]"], [".", ""], $row["name"][0]), $row["value"][0]) }}"
                                />

                                <span class="detail-table__tilde">〜</span>
                                <input
                                    type="time"
                                    name="{{ $row["name"][1] }}"
                                    value="{{ old(str_replace(["[", "]"], [".", ""], $row["name"][1]), $row["value"][1]) }}"
                                />
                            </div>

                            {{-- 出退勤（最初の1件）と休憩（複数）でエラー表示を分岐 --}}
                            @if ($row["label"] === "出勤・退勤")
                                @php
                                    $firstError = $errors->first($errorKeys[0]) ?: $errors->first($errorKeys[1]);
                                @endphp

                                @if ($firstError)
                                    <p class="form-error">{{ $firstError }}</p>
                                @endif
                            @else
                                {{-- 休憩などは複数表示 --}}
                                @foreach ($errorKeys as $key)
                                    @error($key)
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                @endforeach
                            @endif
                            {{-- 予備の休憩入力欄 --}}
                        @elseif ($row["type"] === "time-range-empty")
                            <div class="detail-table__time-range">
                                <input
                                    type="time"
                                    name="{{ $row["name"][0] }}"
                                    value="{{ old(str_replace(["[", "]"], [".", ""], $row["name"][0]), $row["value"][0]) }}"
                                />
                                <span>〜</span>
                                <input
                                    type="time"
                                    name="{{ $row["name"][1] }}"
                                    value="{{ old(str_replace(["[", "]"], [".", ""], $row["name"][1]), $row["value"][1]) }}"
                                />
                            </div>
                            {{-- 予備の休憩エラーメッセージ --}}
                            @foreach ($errorKeys as $key)
                                @error($key)
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            @endforeach

                            {{-- 備考欄 --}}
                        @elseif ($row["type"] === "textarea")
                            <textarea name="{{ $row["name"] }}">{{ old($row["name"], $row["value"]) }}</textarea>
                            @error($row["name"])
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        @endif
                    @else
                        {{-- 編集不可モード（isEditable=false）のときの出力 --}}
                        @if (is_array($row["value"]))
                            {{ ($row["value"][0] ?? "") . " 〜 " . ($row["value"][1] ?? "") }}
                        @else
                            {{ $row["value"] }}
                        @endif
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
{{-- </div> --}}
