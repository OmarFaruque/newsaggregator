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
        Schema::create('articles', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->string('source'); 
            $table->string('title'); 
            $table->text('description')->nullable(); 
            $table->longText('content')->nullable(); 
            $table->string('author')->nullable(); 
            $table->string('category')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('url')->unique();
            $table->string('url_to_image')->nullable(); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
