<?php

namespace App\Services;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Support\Str;

class KnowledgeBaseArticleService
{
    public function create(array $data): KnowledgeBaseArticle
    {
        $data['slug'] = $this->generateSlug($data['title']);

        // Set explicitly rather than relying on the DB column default —
        // Eloquent's create() never re-queries the row afterward, so an
        // in-memory model returned to the controller with a blank
        // article_status would report it wrong for the rest of this same
        // request (the exact bug caught and fixed in Project Expenses).
        $data['article_status'] = $data['article_status'] ?? 'draft';

        $data = $this->withDerivedFields($data);

        return KnowledgeBaseArticle::create($data);
    }

    public function update(KnowledgeBaseArticle $knowledgeBaseArticle, array $data): KnowledgeBaseArticle
    {
        // The slug is issued once at creation and is immutable afterward —
        // the same "server-issued reference, never re-derived on edit"
        // precedent as Ticket::ticket_number/Asset::asset_code — so any
        // submitted slug is stripped before writing.
        unset($data['slug']);

        $data = $this->withDerivedFields($data, $knowledgeBaseArticle);

        $knowledgeBaseArticle->update($data);

        return $knowledgeBaseArticle->fresh();
    }

    public function delete(KnowledgeBaseArticle $knowledgeBaseArticle): bool
    {
        return $knowledgeBaseArticle->delete();
    }

    /**
     * Stamps published_at the moment an article first becomes published
     * (preserving the original timestamp across later saves, rather than
     * re-stamping it every time), and clears it again if the article is
     * moved back to draft — the same "the service owns completion
     * consistency" pattern Project/Task/Asset Disposal already established
     * for their own completed-state timestamps.
     *
     * Moving to 'archived' deliberately leaves published_at untouched (the
     * key is simply omitted from the returned array) — an archived article
     * was retired, not un-published, so its "originally published on"
     * history is worth keeping rather than erasing.
     */
    protected function withDerivedFields(array $data, ?KnowledgeBaseArticle $article = null): array
    {
        $newStatus = $data['article_status'] ?? $article?->article_status ?? 'draft';

        if ($newStatus === 'published') {
            $data['published_at'] = $article?->published_at ?: now();
        } elseif ($newStatus === 'draft') {
            $data['published_at'] = null;
        }

        return $data;
    }

    /**
     * Builds a unique, URL-safe slug from the title — appending -2, -3, ...
     * on collision, the same idea every server-issued reference number in
     * this project uses, just for a text identifier instead of a sequential
     * number.
     */
    protected function generateSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $suffix = 2;

        while (KnowledgeBaseArticle::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
