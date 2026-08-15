<?php

namespace App\Services\Email;

use App\Models\Email\DefaultEmailTemplate;
use Illuminate\Support\Arr;

class TemplateRendererService
{
    /**
     * Render the subject with given data.
     */
    public function renderSubject(DefaultEmailTemplate $template, array $data = []): string
    {
        return $this->replaceVariables($template->subject, $data);
    }

    /**
     * Render the HTML body with given data.
     */
    public function renderBody(DefaultEmailTemplate $template, array $data = []): string
    {
        return $this->replaceVariables($template->body, $data);
    }

    /**
     * Replace placeholders like {{variable}} in a string.
     */
    public function replaceVariables(string $content, array $data = []): string
    {
        // Support both {{variable}} and {{ variable }} with optional spaces
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($matches) use ($data) {
            $key = $matches[1];
            return Arr::get($data, $key, '');
        }, $content);
    }

    /**
     * Validate that all required variables are present in the data.
     * Returns an array of missing keys.
     */
    public function validateVariables(DefaultEmailTemplate $template, array $data = []): array
    {
        $required = $template->variables ?? [];
        if (empty($required)) {
            return [];
        }

        $missing = [];
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                $missing[] = $key;
            }
        }
        return $missing;
    }
}
