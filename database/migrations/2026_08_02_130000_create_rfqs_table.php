<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();

            $table->string('rfq_number')->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
            $table->date('rfq_date');
            $table->date('due_date')->nullable();
            $table->enum('rfq_status', ['draft', 'sent', 'closed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rfq_vendors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->enum('sent_status', ['pending', 'sent', 'responded', 'declined'])->default('pending');
            $table->timestamps();

            $table->unique(['rfq_id', 'vendor_id'], 'rfq_vendor_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rfq_vendors');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfqs');
    }
};
