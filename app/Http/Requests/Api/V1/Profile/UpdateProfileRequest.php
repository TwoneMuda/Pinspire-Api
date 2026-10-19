<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => [
                'sometimes',
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:30',
                Rule::unique('users', 'username')->ignore($this->user()->id),
            ],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
        ];
    }
}
