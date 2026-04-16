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
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained() // refers to the 'id' column in the 'users' table
                ->onDelete('cascade'); // if the referenced user is deleted, the prompt is also deleted
            $table->string('title', 100)->unique(); // Prompt title
            $table->enum('type', ['text', 'image', 'other'])->default('text'); // Type of prompt
            $table->mediumText('content'); // Main content
            $table->text('excerpt')->nullable(); // Excerpt of the main content
            $table->enum('status', ['private', 'public'])->default('public'); // Visibility
            $table->uuid('share_url')->unique()->nullable(); // For sharing via link
            $table->foreignId('category_id')
                ->nullable() // allows a prompt to have no category
                ->constrained() // refers to the 'id' column in the 'categories' table
                ->nullOnDelete(); // if the referenced category is deleted, the field is set to NULL
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};