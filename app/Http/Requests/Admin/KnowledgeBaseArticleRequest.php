<?php

namespace App\Http\Requests\Admin;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KnowledgeBaseArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            // Required — every article must be filed under a category, the
            // same "required in validation, nullable/nullOnDelete in the
            // schema" split Asset Register uses for asset_category_id.
            'knowledge_base_category_id' => 'required|exists:knowledge_base_categories,id',
            // Genuinely optional cross-reference — never required.
            'ticket_category_id' => 'nullable|exists:ticket_categories,id',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'visibility' => ['required', Rule::in(KnowledgeBaseArticle::VISIBILITIES)],
            'article_status' => ['required', Rule::in(KnowledgeBaseArticle::STATUSES)],
            'status' => 'required|boolean',
            // slug/authored_by/published_at are deliberately absent —
            // server-generated/derived only, never client-submitted.
        ];
    }

    public function messages(): array
    {
        return [
            'knowledge_base_category_id.required' => 'Please select a knowledge base category for this article.',
        ];
    }
}
