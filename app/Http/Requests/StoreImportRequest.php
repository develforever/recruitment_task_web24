<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Import::class);
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                function ($attr, $file, $fail) {
                    $ext = strtolower($file->getClientOriginalExtension());

                    if (! in_array($ext, ['csv', 'json', 'xml'])) {
                        $fail('Nieobsługiwany format pliku.');

                        return;
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Plik jest wymagany.',
            'file.file' => 'Przesłany plik jest nieprawidłowy.',
            'file.max' => 'Plik nie może być większy niż 10MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file')) {
                $file = $this->file('file');
                $ext = strtolower($file->getClientOriginalExtension());

                if ($ext === 'xml') {
                    libxml_use_internal_errors(true);
                    $content = @file_get_contents($file->getRealPath());

                    if ($content === false || ! @simplexml_load_string($content)) {
                        $validator->errors()->add('file', 'Plik XML jest uszkodzony lub nieprawidłowy.');
                    }

                    libxml_clear_errors();
                }

                // Walidacja JSON
                if ($ext === 'json') {
                    $content = @file_get_contents($file->getRealPath());
                    json_decode($content);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $validator->errors()->add('file', 'Plik JSON jest nieprawidłowy: '.json_last_error_msg());
                    }
                }
            }
        });
    }
}
