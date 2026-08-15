<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_communications', function (Blueprint $table) {
            $table->id();

            $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
            $table->string('from_email')->nullable();
            $table->string('to_email')->nullable();
            $table->string('subject');
            $table->text('body')->nullable();
            $table->dateTime('sent_at');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_communications');
    }
};
