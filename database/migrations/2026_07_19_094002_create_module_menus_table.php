<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('module_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('label');
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->string('route')->nullable();
            $table->string('url')->nullable();
            $table->string('permission')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('module_menus')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('module_menus');
    }
};
