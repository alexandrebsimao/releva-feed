<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('digest_enabled')->default(true);
            $table->time('digest_time')->default('08:00:00');
            $table->unsignedTinyInteger('digest_min_score')->default(50);
            $table->unsignedTinyInteger('digest_max_items')->default(10);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['digest_enabled', 'digest_time', 'digest_min_score', 'digest_max_items']);
        });
    }
};
