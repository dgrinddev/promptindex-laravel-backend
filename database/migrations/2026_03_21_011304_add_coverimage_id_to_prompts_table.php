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
        Schema::table('prompts', function (Blueprint $table) {
            $table->foreignId('coverimage_id')
                ->nullable() // allows a prompt to have no cover-image
                ->after('category_id') // places the new column after 'category_id'
                ->constrained('images') // refers to the 'id' column in the 'images' table
                ->nullOnDelete(); // if the referenced image is deleted, the field is set to NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->dropForeign(['coverimage_id']);
            $table->dropColumn('coverimage_id');
        });
    }
};