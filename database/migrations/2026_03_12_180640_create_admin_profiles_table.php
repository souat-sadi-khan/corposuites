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
        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');

            // Bio
            $table->string('designation')->nullable();
            $table->string('cover_photo')->nullable();

            // Address
            $table->tinyText('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('whatsapp')->nullable();

            // Education
            $table->string('highest_education')->nullable();
            $table->string('university')->nullable();
            $table->string('major')->nullable();

            // Professional
            $table->string('current_job_title')->nullable();
            $table->string('current_company')->nullable();
            $table->integer('years_of_experience')->nullable();

            // Connections
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('pinterest_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('website_url')->nullable();

            $table->json('skills')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_profiles');
    }
};
