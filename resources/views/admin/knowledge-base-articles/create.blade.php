<form class="ajax-form" method="POST" action="{{ route('admin.knowledge-base-articles.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Add Article</h5>
            <p>Write a new knowledge base article</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <div class="fm-grid">
            <div class="fm-field fm-full">
                <label>Title <span class="req">*</span></label>
                <input type="text" class="form-control" name="title" placeholder="e.g., How to reset your password" required autocomplete="off">
            </div>
            <div class="fm-field">
                <label>Category <span class="req">*</span></label>
                <select name="knowledge_base_category_id" class="form-select select" required>
                    <option value="">Select category</option>
                    @foreach ($knowledgeBaseCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Related Ticket Topic</label>
                <select name="ticket_category_id" class="form-select select">
                    <option value="">Not tied to a ticket topic</option>
                    @foreach ($ticketCategories as $ticketCategory)
                        <option value="{{ $ticketCategory->id }}">{{ $ticketCategory->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-field">
                <label>Visibility <span class="req">*</span></label>
                <select name="visibility" class="form-select" required>
                    <option value="internal" selected>Internal (staff only)</option>
                    <option value="public">Public</option>
                </select>
            </div>
            <div class="fm-field">
                <label>Article State <span class="req">*</span></label>
                <select name="article_status" class="form-select" required>
                    <option value="draft" selected>Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div class="fm-field fm-full">
                <label>Excerpt</label>
                <textarea class="form-control" name="excerpt" rows="2" maxlength="500" placeholder="Short summary shown in listings"></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Content <span class="req">*</span></label>
                <textarea class="form-control" name="content" rows="8" placeholder="Write the article content" required></textarea>
            </div>
            <div class="fm-field fm-full">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
            <i class="ri-information-line"></i> Marking the state Published stamps the publish date automatically; moving it back to Draft clears it.
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
            <button type="submit" class="btn-nx-primary" id="submit">
                <i class="ri-check-line me-1"></i> Create
            </button>
            <button type="button" class="btn-nx-primary" id="submitting" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Submitting...
            </button>
        </div>
    </div>
</form>
