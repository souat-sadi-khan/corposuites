<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEmailProviderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:smtp,sendgrid,mailgun,ses,resend,postmark,brevo,custom_api',
            'timeout' => 'nullable|integer|min:1',
            'is_enabled' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'sandbox_mode' => 'nullable|boolean',
            'maintenance_mode' => 'nullable|boolean',
        ];

        // Add conditional rules for config fields
        switch ($this->type) {
            case 'smtp':
                $rules['config.host'] = 'required|string';
                $rules['config.port'] = 'required|integer|min:1|max:65535';
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
                $rules['config.api_key'] = 'required|string';
                break;
            case 'custom_api':
                $rules['config.endpoint'] = 'required|url';
                $rules['config.api_key'] = 'required|string';
                break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'type.required' => 'Provider type is required.',
            'type.in' => 'Selected provider type is invalid.',
            'config.host.required' => 'SMTP host is required.',
            'config.port.required' => 'SMTP port is required.',
            'config.api_key.required' => 'API key is required.',
            'config.endpoint.required' => 'Endpoint URL is required.',
        ];
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
