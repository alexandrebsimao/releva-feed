<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->json('ai_classification')->nullable();
            $table->timestamp('ai_classified_at')->nullable();
            $table->text('ai_last_error')->nullable();
        });

        Schema::table('news_clusters', function (Blueprint $table): void {
            $table->timestamp('ai_summary_at')->nullable();
            $table->text('ai_last_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['ai_classification', 'ai_classified_at', 'ai_last_error']);
        });

        Schema::table('news_clusters', function (Blueprint $table): void {
            $table->dropColumn(['ai_summary_at', 'ai_last_error']);
        });
    }
};
