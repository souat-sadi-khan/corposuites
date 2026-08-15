<form class="ajax-form" method="POST" action="{{ route('admin.knowledge-base-articles.update', $knowledgeBaseArticle->id) }}">
    @method('PATCH')
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Edit Article</h5>
            <p>/{{ $knowledgeBaseArticle->slug }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" value="{{ old('title', $knowledgeBaseArticle->title) }}" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category <span class="req">*</span></label>
                <select name="knowledge_base_category_id" class="form-select select" required>
                    <option value="">Select category</option>
                    @foreach ($knowledgeBaseCategories as $category)
                        <option value="{{ $category->id }}" {{ $knowledgeBaseArticle->knowledge_base_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Ticket Topic</label>
                <select name="ticket_category_id" class="form-select select">
                    <option value="">Not tied to a ticket topic</option>
                    @foreach ($ticketCategories as $ticketCategory)
                        <option value="{{ $ticketCategory->id }}" {{ $knowledgeBaseArticle->ticket_category_id == $ticketCategory->id ? 'selected' : '' }}>{{ $ticketCategory->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Visibility <span class="req">*</span></label>
                <select name="visibility" class="form-select" required>
                    <option value="internal" {{ $knowledgeBaseArticle->visibility === 'internal' ? 'selected' : '' }}>Internal (staff only)</option>
                    <option value="public" {{ $knowledgeBaseArticle->visibility === 'public' ? 'selected' : '' }}>Public</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Article State <span class="req">*</span></label>
                <select name="article_status" class="form-select" required>
                    <option value="draft" {{ $knowledgeBaseArticle->article_status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ $knowledgeBaseArticle->article_status === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ $knowledgeBaseArticle->article_status === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Excerpt</label>
                <textarea class="form-control" name="excerpt" rows="2" maxlength="500">{{ old('excerpt', $knowledgeBaseArticle->excerpt) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Content <span class="req">*</span></label>
                <textarea class="form-control" name="content" rows="8" required>{{ old('content', $knowledgeBaseArticle->content) }}</textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $knowledgeBaseArticle->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ! $knowledgeBaseArticle->status ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i>
            @if ($knowledgeBaseArticle->published_at)
                Originally published {{ $knowledgeBaseArticle->published_at->format('d M Y, h:i A') }}.
            @endif
            Moving the state back to Draft clears the publish date; archiving keeps it.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Update
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
