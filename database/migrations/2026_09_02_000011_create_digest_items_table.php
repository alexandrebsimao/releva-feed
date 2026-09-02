<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digest_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_digest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('news_cluster_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['daily_digest_id', 'news_cluster_id']);
            $table->unique(['daily_digest_id', 'position']);
            $table->index(['news_cluster_id', 'daily_digest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digest_items');
    }
};
