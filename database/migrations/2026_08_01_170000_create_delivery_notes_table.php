<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();

            $table->string('note_number')->unique();
            $table->foreignId('delivery_id')->unique()->constrained('deliveries')->cascadeOnDelete();
            $table->date('issued_date');
            $table->string('received_by')->nullable();
            $table->date('received_date')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_notes');
    }
};
