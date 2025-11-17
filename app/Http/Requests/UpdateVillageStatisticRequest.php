<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVillageStatisticRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizedInputs());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxYear = (int) date('Y') + 1;

        return [
            'statistic_type_id' => ['sometimes', 'required', 'exists:statistic_types,id'],
            'indicator_name' => ['sometimes', 'required', 'string', 'max:255'],
            'value' => ['sometimes', 'required', 'numeric'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:' . $maxYear],
            'period' => ['sometimes', 'nullable', 'string', 'max:50'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function sanitizedInputs(): array
    {
        $input = $this->all();
        $fields = ['indicator_name', 'unit', 'period', 'source', 'notes'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $input) && is_string($input[$field])) {
                $input[$field] = $this->cleanString($input[$field]);
            }
        }

        return $input;
    }

    protected function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(strip_tags($value));

        return $clean === '' ? null : $clean;
    }
}
