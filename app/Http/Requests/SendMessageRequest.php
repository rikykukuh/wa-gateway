<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('recipient')) {
            $this->merge([
                'recipient' => preg_replace('/\D+/', '', (string) $this->input('recipient')),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['required', 'regex:/^[1-9][0-9]{7,14}$/'],
            'message' => ['required', 'string', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient.regex' => 'Nomor harus memakai kode negara, contoh 628123456789.',
        ];
    }
}
