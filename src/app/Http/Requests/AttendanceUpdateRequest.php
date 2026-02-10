<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],

            'rests.*.start' => ['nullable', 'date_format:H:i'],
            'rests.*.end' => ['nullable', 'date_format:H:i'],
            'remarks' => ['required', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->clock_in;
            $clockOut = $this->clock_out;

            if (!$clockIn || !$clockOut) {
                return;
            }

            $rests = $this->input('rests', []);
            $validRests = [];

            foreach ($rests as $index => $rest) {
                $start = isset($rest['start']) && $rest['start'] !== '' ? $rest['start'] : null;
                $end = isset($rest['end']) && $rest['end'] !== '' ? $rest['end'] : null;

                // --- 1. 片方だけ入力されている場合のバリデーション ---
                if (($start !== null && $end === null) || ($start === null && $end !== null)) {
                    if ($start === null) {
                        $validator->errors()->add("rests.{$index}.start", '休憩の開始時間を入力してください');
                    }
                    if ($end === null) {
                        $validator->errors()->add("rests.{$index}.end", '休憩の終了時間を入力してください');
                    }
                    continue;
                }

                // 両方入力されている場合のみ、計算用配列に追加
                if ($start !== null && $end !== null) {
                    $validRests[] = [
                        'index' => $index,
                        'start' => $start,
                        'end' => $end
                    ];
                }
            }

            // --- 2. 時間の整合性・重複チェック ---
            foreach ($validRests as $i => $rest) {
                // 開始が終了より後の時間になっていないか
                if ($rest['start'] >= $rest['end']) {
                    $validator->errors()->add("rests.{$rest['index']}.end", '休憩時間が不適切な値です');
                }

                // 出勤・退勤時間内に収まっているか
                if ($rest['start'] < $clockIn || $rest['end'] > $clockOut) {
                    $validator->errors()->add("rests.{$rest['index']}.end", '休憩時間もしくは退勤時間が不適切な値です');
                }

                // 他の休憩時間と被っていないか
                foreach ($validRests as $j => $compareRest) {
                    if ($i === $j) continue;

                    if ($rest['start'] < $compareRest['end'] && $rest['end'] > $compareRest['start']) {
                        $validator->errors()->add("rests.{$rest['index']}.end", '休憩時間が重複しています');
                        break;
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'clock_in.required' => '出勤時間は必須です',
            'clock_out.required' => '退勤時間は必須です',
            'clock_out.after' => '出勤時間が不適切な値です',
            'remarks.required' => '備考を記入してください',
        ];
    }
}