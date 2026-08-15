<?php

namespace App\Services\Email;

use App\Models\Email\DefaultEmailTemplate;
use Illuminate\Support\Str;

class TemplatePreviewService
{
    /**
     * Generate a preview of the template (both subject and body) using dummy data.
     */
    public function preview(DefaultEmailTemplate $template): array
    {
        $dummyData = $this->generateDummyData($template);
        $renderer = app(TemplateRendererService::class);

        return [
            'subject' => $renderer->renderSubject($template, $dummyData),
            'body'    => $renderer->renderBody($template, $dummyData),
            'data'    => $dummyData,
        ];
    }

    /**
     * Generate dummy values for all variables defined in the template.
     */
    public function generateDummyData(DefaultEmailTemplate $template): array
    {
        $variables = $template->variables ?? [];
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?? [];
        }
        // Ensure it's an array
        $variables = is_array($variables) ? $variables : [];
        $dummy = [];
        foreach ($variables as $key) {
            $dummy[$key] = $this->dummyValueFor($key);
        }
        return $dummy;
    }

    /**
     * Provide a realistic dummy value based on variable name.
     */
    private function dummyValueFor(string $key): string
    {
        $map = [
            'name'      => 'John Doe',
            'email'     => 'john@example.com',
            'link'      => 'https://example.com/verify/123456',
            'date'      => now()->toDateString(),
            'time'      => now()->toTimeString(),
            'amount'    => '$99.99',
            'order_id'  => 'ORD-' . strtoupper(Str::random(8)),
            'username'  => 'johndoe',
            'password'  => '••••••••',
            'company'   => 'Acme Inc.',
            'address'   => '123 Main St, Anytown',
        ];

        return $map[$key] ?? '{{' . $key . '}}';
    }
}
