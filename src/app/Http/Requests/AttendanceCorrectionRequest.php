<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
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
            'remarks' => 'required|string',
            // 出勤時間は退勤時間より前であること
            'revised_clock_in' => 'required|before:revised_clock_out',
            'revised_clock_out' => 'required',
            // 休憩開始・終了は退勤時間より前であること
            'rest_start.*' => 'nullable|before:revised_clock_out',
            'rest_end.*' => 'nullable|before:revised_clock_out',
        ];
    }

    public function messages(): array
    {
        return [
            'remarks.required' => '備考を記入してください',
            'revised_clock_in.before' => '出勤時間が不適切な値です',
            'rest_start.*.before' => '休憩時間が不適切な値です',
            'rest_end.*.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'revised_clock_out.required' => '退勤時間は必須です',
        ];
    }
}
