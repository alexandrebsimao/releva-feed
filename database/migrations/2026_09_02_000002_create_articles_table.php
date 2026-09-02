<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
            $table->string('guid', 2048)->nullable()->unique();
            $table->string('url', 2048)->unique();
            $table->string('canonical_url', 2048)->nullable()->unique();
            $table->text('original_title');
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->timestamp('published_at');
            $table->timestamp('imported_at');
            $table->string('content_hash', 64)->unique();
            $table->string('processing_status', 20)->default('pending')->index();
            $table->timestamps();

            $table->index(['feed_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
