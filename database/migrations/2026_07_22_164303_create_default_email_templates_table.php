<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('default_email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('category')->nullable();
            $table->string('subject');
            $table->longText('body'); // HTML content
            $table->json('variables')->nullable(); // JSON field for variables
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->tinyInteger('status')->default(1); // e.g., 1=active, 0=inactive
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_email_templates');
    }
};
