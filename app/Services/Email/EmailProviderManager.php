<?php

namespace App\Services\Email;

use App\Models\Email\EmailProvider;
use App\Models\Email\EmailSenderIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmailProviderManager
{
    public function create(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:smtp,sendgrid,mailgun,ses,resend,postmark,brevo,custom_api',
            'config' => 'nullable|array',
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'sandbox_mode' => 'boolean',
            'maintenance_mode' => 'boolean',
            'timeout' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()->toArray()];
        }

        try {
            DB::beginTransaction();
            if ($data['is_default'] ?? false) {
                EmailProvider::where('is_default', true)->update(['is_default' => false]);
            }
            $provider = EmailProvider::create($data);

            DB::commit();
            return ['success' => true, 'message' => 'Provider created', 'data' => $provider];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Creation failed', 'errors' => ['exception' => $e->getMessage()]];
        }
    }

    public function update(string $id, array $data): array
    {
        $provider = EmailProvider::find($id);
        if (!$provider) {
            return ['success' => false, 'message' => 'Provider not found'];
        }

        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:smtp,sendgrid,mailgun,ses,resend,postmark,brevo,custom_api',
            'config' => 'nullable|array',
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'sandbox_mode' => 'boolean',
            'maintenance_mode' => 'boolean',
            'timeout' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()->toArray()];
        }

        try {
            DB::beginTransaction();
            if (isset($data['is_default']) && $data['is_default']) {
                EmailProvider::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
            }
            $provider->update($data);
            DB::commit();
            return ['success' => true, 'message' => 'Provider updated', 'data' => $provider->fresh()];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Update failed', 'errors' => ['exception' => $e->getMessage()]];
        }
    }

    public function delete(string $id): array
    {
        $provider = EmailProvider::find($id);
        if (!$provider) {
            return ['success' => false, 'message' => 'Provider not found'];
        }
        try {
            $provider->delete();
            return ['success' => true, 'message' => 'Provider deleted'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()];
        }
    }

    public function enable(string $id): array
    {
        $provider = EmailProvider::find($id);
        if (!$provider) return ['success' => false, 'message' => 'Provider not found'];
        $provider->update(['is_enabled' => true]);
        return ['success' => true, 'message' => 'Provider enabled'];
    }

    public function disable(string $id): array
    {
        $provider = EmailProvider::find($id);
        if (!$provider) return ['success' => false, 'message' => 'Provider not found'];
        $provider->update(['is_enabled' => false]);
        return ['success' => true, 'message' => 'Provider disabled'];
    }

    public function setDefault(string $id): array
    {
        $provider = EmailProvider::find($id);
        if (!$provider) return ['success' => false, 'message' => 'Provider not found'];
        try {
            DB::transaction(function () use ($id) {
                EmailProvider::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
                EmailProvider::where('id', $id)->update(['is_default' => true]);
            });
            return ['success' => true, 'message' => 'Default provider set'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to set default: ' . $e->getMessage()];
        }
    }

    public function validateConfiguration(string $type, array $config): array
    {
        $rules = $this->getConfigRules($type);
        $validator = Validator::make($config, $rules);
        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Configuration invalid', 'errors' => $validator->errors()->toArray()];
        }
        return ['success' => true, 'message' => 'Configuration is valid'];
    }

    private function getConfigRules(string $type): array
    {
        return match ($type) {
            'smtp' => [
                'host' => 'required|string',
                'port' => 'required|integer|min:1|max:65535',
                'username' => 'nullable|string',
                'password' => 'nullable|string',
                'encryption' => 'nullable|in:tls,ssl',
                'timeout' => 'nullable|integer|min:1',
                'verify_peer' => 'nullable|boolean',
            ],
            'sendgrid', 'mailgun', 'ses', 'resend', 'postmark', 'brevo', 'custom_api' => [
                'api_key' => 'required|string',
            ],
            default => [],
        };
    }
}
