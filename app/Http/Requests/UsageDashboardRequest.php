<?php

namespace App\Http\Requests;

use App\Services\Cursor\Dto\ReportingPeriod;
use App\Services\Cursor\Enums\DatePreset;
use App\Services\Cursor\Factories\ReportingPeriodFactory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'from' => ['required_with:to', 'nullable', 'date', 'date_format:Y-m-d'],
            'to' => ['required_with:from', 'nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'from.required_with' => 'Indiquez une date de début et une date de fin.',
            'to.required_with' => 'Indiquez une date de début et une date de fin.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $timezone = config('cursor_stats.timezone');
            $today = CarbonImmutable::now($timezone)->startOfDay();

            foreach (['from', 'to'] as $field) {
                $value = $this->query($field);

                if (! is_string($value) || $value === '') {
                    continue;
                }

                $date = CarbonImmutable::createFromFormat('Y-m-d', $value, $timezone)->startOfDay();

                if ($date->greaterThan($today)) {
                    $validator->errors()->add($field, 'La date ne peut pas être dans le futur.');
                }
            }
        });
    }

    public function usesCustomRange(): bool
    {
        return $this->filled('from') && $this->filled('to');
    }

    public function reportingPeriod(ReportingPeriodFactory $periodFactory): ReportingPeriod
    {
        if ($this->usesCustomRange()) {
            return $periodFactory->forRange(
                $this->customRangeStart(),
                $this->customRangeEnd(),
            );
        }

        return $periodFactory->forPreset($this->preset());
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
        if ($this->usesCustomRange()) {
            return false;
        }

        $value = $this->query('preset');

        return is_string($value) && $value !== '' && DatePreset::tryFrom($value) === null;
    }

    public function customFrom(): ?string
    {
        $value = $this->query('from');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function customTo(): ?string
    {
        $value = $this->query('to');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function customRangeStart(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d',
            (string) $this->query('from'),
            config('cursor_stats.timezone'),
        );
    }

    private function customRangeEnd(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d',
            (string) $this->query('to'),
            config('cursor_stats.timezone'),
        );
    }
}
