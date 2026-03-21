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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('prompt_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('hashed_filename')->unique();
            $table->string('original_filename');
            $table->string('extension');
            $table->unsignedInteger('size');
            $table->uuid('upload_image_token')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'upload_image_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
