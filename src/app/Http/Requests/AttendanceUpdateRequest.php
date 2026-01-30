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

            'rests.*.start' => [
                'nullable',
                'date_format:H:i',
            ],
            'rests.*.end' => [
                'nullable',
                'date_format:H:i',
                'after:rests.*.start',
                'before:clock_out',
            ],
            'remarks' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_out.after' => '出勤時間が不適切な値です',
            'rests.*.end.after' => '休憩時間が不適切な値です',
            'rests.*.end.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'remarks.required' => '備考を記入してください',
        ];
    }
}
