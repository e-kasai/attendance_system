<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            // 出勤・退勤（フォーム上は clock_in / clock_out）
            'clock_in'  => ['required', 'date_format:H:i', 'before:clock_out'], //date_format サーバ側でも入力形式を保証
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],

            // 休憩（break_in / break_out）
            'breaks.*.id' => ['nullable', 'integer', 'exists:break_times,id'],
            'breaks.*.break_in' => [
                'nullable',
                'date_format:H:i',
                'required_with:breaks.*.break_out',
                'after_or_equal:clock_in',
                'before_or_equal:clock_out',
            ],
            'breaks.*.break_out' => [
                'nullable',
                'date_format:H:i',
                'required_with:breaks.*.break_in',
                'after_or_equal:breaks.*.break_in',
                'before_or_equal:clock_out',
            ],

            // 備考
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    //複数休憩同士の時刻重複をはじく
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            //フォームデータの中から "breaks" 配列を取り出すなければ空配列
            $breaks = $this->input('breaks', []);

            // break_in / break_out 両方あるものだけに絞る
            $validBreaks = array_filter($breaks, function ($break) {
                return !empty($break['break_in']) && !empty($break['break_out']);
            });

            // 時刻でソート（早い順）
            usort($validBreaks, function ($firstBreak, $secondBreak) {
                return strcmp($firstBreak['break_in'], $secondBreak['break_in']);
            });

            for ($i = 0; $i < count($validBreaks) - 1; $i++) {
                $current = $validBreaks[$i];
                $next = $validBreaks[$i + 1];

                //次の休憩開始は前の休憩の終了時刻より後
                if ($current['break_out'] > $next['break_in']) {
                    $validator->errors()->add(
                        "breaks.{$i}.break_in",
                        "休憩時間が重複しています"
                    );
                    //最初の重複を見つけた時点で止める
                    break;
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            // 出退勤
            'clock_in.required' => '出勤時間を入力してください',
            'clock_in.date_format' => '出勤時間の形式が正しくありません（例：09:00）',
            'clock_in.before' => '出勤時間もしくは退勤時間が不適切な値です',

            'clock_out.required' => '退勤時間を入力してください',
            'clock_out.date_format' => '退勤時間の形式が正しくありません（例：18:00）',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            // 休憩
            'breaks.*.id.integer' => '休憩IDの形式が正しくありません',
            'breaks.*.id.exists' => '指定された休憩データが存在しません',
            'breaks.*.break_in.date_format' => '休憩開始時間の形式が正しくありません（例：12:00）',
            'breaks.*.break_in.required_with' => '休憩終了時間を入力する場合は、休憩開始時間も入力してください',
            'breaks.*.break_in.after_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.break_in.before_or_equal' => '休憩時間が不適切な値です',

            'breaks.*.break_out.date_format' => '休憩終了時間の形式が正しくありません（例：13:00）',
            'breaks.*.break_out.required_with' => '休憩開始時間を入力する場合は、休憩終了時間も入力してください',
            'breaks.*.break_out.after_or_equal' => '休憩終了時間は休憩開始時間以降にしてください',
            'breaks.*.break_out.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',

            // 備考
            'comment.required' => '備考を記入してください',
            'comment.string' => '備考欄は文字列で入力してください',
            'comment.max' => '備考欄は255文字以下で入力してください',
        ];
    }
}
