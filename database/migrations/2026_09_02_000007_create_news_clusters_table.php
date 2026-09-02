<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_clusters', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->text('title');
            $table->text('summary')->nullable();
            $table->text('why_it_matters')->nullable();
            $table->string('category')->nullable();
            $table->unsignedTinyInteger('importance_score')->nullable();
            $table->unsignedTinyInteger('general_score')->nullable();
            $table->timestamp('published_at');
            $table->timestamp('last_article_at');
            $table->string('public_status', 20)->default('automatic')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_clusters');
    }
};
