<?php

declare(strict_types=1);

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
        Schema::create('watch_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->foreignId('anime_id')->constrained('animes')->cascadeOnDelete();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->unsignedInteger('progress_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('last_watched_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'last_watched_at']);
            $table->index(['session_id', 'last_watched_at']);
            $table->index(['anime_id', 'episode_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_histories');
    }
};
