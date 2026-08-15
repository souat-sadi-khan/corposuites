<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Base Admin Panel feature — Document Management System (DMS).
     *
     * Deliberately a single table for both folders and files (kind = folder|file),
     * mirroring a real filesystem: a folder is just a row with no `path`/`disk`,
     * and files/folders share the same parent_id tree, favorite/trash flags,
     * sort, search and pagination logic. This is not a dynamic ERP module — it
     * is hardcoded into the sidebar and is meant to be reusable across projects.
     */
    public function up(): void
    {
        Schema::create('dms_items', function (Blueprint $table) {
            $table->id();

            $table->enum('kind', ['folder', 'file']);
            // Fixed classification used for icon/label + type filter — 'folder' for
            // folders, otherwise derived from the uploaded file's extension.
            $table->string('type', 20);

            $table->string('name');

            // Self-referencing tree. nullOnDelete (not cascade) — deleting a folder
            // hard-deletes its own subtree via DmsService's own recursive delete,
            // not via this FK; nullOnDelete here is only a safety net if a row is
            // ever removed outside that code path (e.g. direct DB maintenance).
            $table->foreignId('parent_id')->nullable()->constrained('dms_items')->nullOnDelete();

            // File-only columns — null for folders.
            $table->string('disk', 40)->nullable();
            $table->string('path')->nullable();
            $table->string('extension', 20)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes

            $table->boolean('favorite')->default(false);

            // Soft "moved to trash" flag — not Laravel's own soft-deletes, since a
            // trashed item must still be listable (Trash tab) and restorable, not
            // hidden from every query the way SoftDeletes would hide it by default.
            $table->boolean('trashed')->default(false);
            $table->timestamp('trashed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();

            $table->timestamps();

            $table->index(['parent_id', 'trashed']);
            $table->index(['kind', 'trashed']);
            $table->index(['favorite', 'trashed']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dms_items');
    }
};
