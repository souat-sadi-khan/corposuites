<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEmailProviderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:smtp,sendgrid,mailgun,ses,resend,postmark,brevo,custom_api',
            'timeout' => 'nullable|integer|min:1',
            'is_enabled' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'sandbox_mode' => 'nullable|boolean',
            'maintenance_mode' => 'nullable|boolean',
        ];

        // If type is being updated, apply rules; else, check current type from DB
        $type = $this->type ?? ($this->route('provider') ? $this->route('provider')->type : null);

        if ($type) {
            switch ($type) {
                case 'smtp':
                    $rules['config.host'] = 'sometimes|required|string';
                    $rules['config.port'] = 'sometimes|required|integer|min:1|max:65535';
                    $rules['config.username'] = 'nullable|string';
                    $rules['config.password'] = 'nullable|string';
                    $rules['config.encryption'] = 'nullable|in:tls,ssl';
                    $rules['config.verify_peer'] = 'nullable|boolean';
                    break;
                case 'sendgrid':
                case 'mailgun':
                case 'ses':
                case 'resend':
                case 'postmark':
                case 'brevo':
                    $rules['config.api_key'] = 'sometimes|required|string';
                    break;
                case 'custom_api':
                    $rules['config.endpoint'] = 'sometimes|required|url';
                    $rules['config.api_key'] = 'sometimes|required|string';
                    break;
            }
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
