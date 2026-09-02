<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_topic', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->decimal('confidence', 5, 4);
            $table->string('detected_by', 50);
            $table->timestamps();

            $table->primary(['article_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_topic');
    }
};
