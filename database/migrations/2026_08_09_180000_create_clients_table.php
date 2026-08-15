<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Project Management owns its own `clients` table.
     *
     * Deliberately NOT reusing Sales' `customers` or CRM's `companies`:
     * a customer is someone who buys product against invoices/orders, and a
     * CRM company is a pre-sale account record. A project client is the party
     * a project is delivered and billed to, and later Project Management
     * sub-modules (Projects, Project Billing, Profitability) will FK to
     * `clients.id`. No naming collision with either existing table.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_code')->unique(); // server-generated, immutable
            $table->string('name');
            $table->enum('client_type', ['individual', 'company'])->default('company');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
