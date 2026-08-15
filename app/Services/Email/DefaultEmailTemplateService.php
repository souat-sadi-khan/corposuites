<?php

namespace App\Services\Email;

use App\Models\Email\DefaultEmailTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DefaultEmailTemplateService
{
    public function getAll(): Collection
    {
        return DefaultEmailTemplate::orderBy('sort_order')->get();
    }

    public function getById(int $id): ?DefaultEmailTemplate
    {
        return DefaultEmailTemplate::find($id);
    }

    public function getByKey(string $key): ?DefaultEmailTemplate
    {
        return DefaultEmailTemplate::where('key', $key)->first();
    }

    public function create(array $data): DefaultEmailTemplate
    {
        // Auto-generate a key if not provided
        if (empty($data['key'])) {
            $data['key'] = Str::slug($data['name'] ?? 'template');
        }

        // Ensure JSON variables are cast properly
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables']);
        }

        return DefaultEmailTemplate::create($data);
    }

    public function update(int $id, array $data): DefaultEmailTemplate
    {
        $template = $this->getById($id);
        if (!$template) {
            throw new \Exception('Template not found');
        }

        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables']);
        }

        $template->update($data);
        return $template->fresh();
    }

    public function delete(int $id): bool
    {
        $template = $this->getById($id);
        if (!$template) {
            throw new \Exception('Template not found');
        }
        return $template->delete();
    }

    public function duplicate(int $id, ?string $newKey = null): DefaultEmailTemplate
    {
        $original = $this->getById($id);
        if (!$original) {
            throw new \Exception('Template not found');
        }

        $newData = $original->toArray();
        unset($newData['id'], $newData['created_at'], $newData['updated_at'], $newData['deleted_at']);

        $newData['name'] = $original->name . ' (Copy)';
        $newData['key'] = $newKey ?? Str::slug($newData['name']) . '-' . Str::random(4);
        $newData['is_system'] = false; // Copies are never system templates

        return $this->create($newData);
    }

    public function changeStatus(int $id, int $status): DefaultEmailTemplate
    {
        $template = $this->getById($id);
        if (!$template) {
            throw new \Exception('Template not found');
        }

        $template->update(['status' => $status]);
        return $template->fresh();
    }
}
