<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_article', function (Blueprint $table): void {
            $table->foreignId('news_cluster_id')->constrained('news_clusters')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->decimal('similarity_score', 5, 4)->nullable();
            $table->timestamps();

            $table->primary(['news_cluster_id', 'article_id']);
            $table->index(['article_id', 'news_cluster_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_article');
    }
};
