<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            $table->string('quotation_number')->unique();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->enum('quotation_status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotations');
    }
};
