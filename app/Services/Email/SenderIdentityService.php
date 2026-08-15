<?php

namespace App\Services\Email;

use App\Models\Email\EmailSenderIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SenderIdentityService
{
    /**
     * Create a new sender identity for a provider.
     *
     * @param string $providerId
     * @param array $data
     * @return array{success: bool, message: string, data?: EmailSenderIdentity, errors?: array}
     */
    public function create(array $data): array
    {
        $validator = Validator::make($data, [
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ];
        }

        try {
            DB::beginTransaction();

            // If setting as default, unset existing default for this provider
            if (!empty($data['is_default'])) {
                EmailSenderIdentity::where('provider_id', $data['provider_id'])
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $identity = EmailSenderIdentity::create([
                'provider_id' => $data['provider_id'],
                'email' => $data['email'],
                'name' => $data['name'] ?? null,
                'is_default' => $data['is_default'] ?? false,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Sender identity created successfully',
                'data' => $identity,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to create sender identity',
                'errors' => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * Update an existing sender identity.
     *
     * @param string $identityId
     * @param array $data
     * @return array{success: bool, message: string, data?: EmailSenderIdentity, errors?: array}
     */
    public function update(string $identityId, array $data): array
    {
        $identity = EmailSenderIdentity::find($identityId);
        if (!$identity) {
            return [
                'success' => false,
                'message' => 'Sender identity not found',
                'errors' => ['id' => 'Invalid identity ID'],
            ];
        }

        $validator = Validator::make($data, [
            'email' => 'sometimes|email|max:255',
            'name' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ];
        }

        try {
            DB::beginTransaction();

            if (isset($data['is_default']) && $data['is_default']) {
                // Unset default for other identities of the same provider
                EmailSenderIdentity::where('provider_id', $identity->provider_id)
                    ->where('id', '!=', $identityId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $identity->update($data);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Sender identity updated successfully',
                'data' => $identity->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to update sender identity',
                'errors' => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * Delete a sender identity (soft delete).
     *
     * @param string $identityId
     * @return array{success: bool, message: string}
     */
    public function delete(string $identityId): array
    {
        $identity = EmailSenderIdentity::find($identityId);
        if (!$identity) {
            return [
                'success' => false,
                'message' => 'Sender identity not found',
            ];
        }

        try {
            $identity->delete();
            return [
                'success' => true,
                'message' => 'Sender identity deleted successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete sender identity: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Set a sender identity as default for its provider.
     *
     * @param string $identityId
     * @return array{success: bool, message: string}
     */
    public function setDefault(string $identityId): array
    {
        $identity = EmailSenderIdentity::find($identityId);
        if (!$identity) {
            return [
                'success' => false,
                'message' => 'Sender identity not found',
            ];
        }

        try {
            DB::transaction(function () use ($identity) {
                // Unset default for all other identities of the same provider
                EmailSenderIdentity::where('provider_id', $identity->provider_id)
                    ->where('id', '!=', $identity->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                $identity->update(['is_default' => true]);
            });

            return [
                'success' => true,
                'message' => 'Default sender identity updated',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to set default: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all sender identities for a given provider.
     *
     * @param string $providerId
     * @return array{success: bool, data: \Illuminate\Database\Eloquent\Collection}
     */
    public function getByProvider(string $providerId): array
    {
        $identities = EmailSenderIdentity::where('provider_id', $providerId)
            ->orderBy('is_default', 'desc')
            ->orderBy('email')
            ->get();

        return [
            'success' => true,
            'data' => $identities,
        ];
    }

    /**
     * Get the default sender identity for a provider.
     *
     * @param string $providerId
     * @return EmailSenderIdentity|null
     */
    public function getDefault(string $providerId): ?EmailSenderIdentity
    {
        return EmailSenderIdentity::where('provider_id', $providerId)
            ->where('is_default', true)
            ->first();
    }
}
