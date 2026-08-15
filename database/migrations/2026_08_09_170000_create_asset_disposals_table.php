<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            // An asset is disposed of once — enforced at the DB level, the
            // same unique-FK one-to-one shape `asset_purchases` uses.
            $table->foreignId('asset_id')->unique()->constrained('assets')->cascadeOnDelete();
            $table->date('disposal_date');
            $table->enum('disposal_method', ['sold', 'scrapped', 'donated', 'written_off', 'traded_in', 'lost'])->default('sold');
            $table->string('recipient')->nullable();
            $table->decimal('proceeds', 15, 2)->default(0);
            // Both snapshotted at disposal time by the service — a disposal
            // is a historical financial event and must not be rewritten by
            // a later change to the asset's cost or category settings.
            $table->decimal('book_value_at_disposal', 15, 2)->default(0);
            $table->decimal('gain_loss', 15, 2)->default(0);
            $table->enum('disposal_status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
