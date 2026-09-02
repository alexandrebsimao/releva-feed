<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_metrics', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 50);
            $table->string('model', 150);
            $table->string('operation', 100);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status', 20);
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['provider', 'operation', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_metrics');
    }
};
