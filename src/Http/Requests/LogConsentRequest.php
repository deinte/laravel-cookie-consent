<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Http\Requests;

use Deinte\CookieConsent\Enums\ConsentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'categories' => ['present', 'array'],
            'categories.*' => ['string', Rule::in(ConsentCategory::values())],
            'version' => ['required', 'string', 'max:50'],
            'policyHash' => ['nullable', 'string', 'max:64'],
            'url' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'categories.*.in' => 'Unknown consent category.',
        ];
    }
}
