<?php

namespace App\Http\Requests;

use App\Services\Cursor\DatePreset;
use Illuminate\Foundation\Http\FormRequest;

class UsageDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preset' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }

    public function preset(): DatePreset
    {
        $value = $this->query('preset');

        if (! is_string($value) || $value === '') {
            return DatePreset::Today;
        }

        return DatePreset::tryFrom($value) ?? DatePreset::Today;
    }

    public function hadInvalidPreset(): bool
    {
        $value = $this->query('preset');

        return is_string($value) && $value !== '' && DatePreset::tryFrom($value) === null;
    }
}
