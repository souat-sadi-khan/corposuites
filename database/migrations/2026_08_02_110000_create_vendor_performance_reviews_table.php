<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_performance_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->decimal('quality_rating', 3, 1)->default(0);
            $table->decimal('delivery_rating', 3, 1)->default(0);
            $table->decimal('pricing_rating', 3, 1)->default(0);
            $table->decimal('communication_rating', 3, 1)->default(0);
            $table->decimal('overall_rating', 3, 1)->default(0);
            $table->text('remarks')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_performance_reviews');
    }
};
