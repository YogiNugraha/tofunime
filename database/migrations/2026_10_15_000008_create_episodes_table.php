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
        Schema::create('episodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('anime_id')->constrained('animes')->cascadeOnDelete();
            $table->string('episode_number', 20);
            $table->decimal('episode_order', 6, 2);
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->unique();
            $table->string('source_episode_url', 500)->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamp('aired_at')->nullable();
            $table->timestamps();

            $table->unique(['anime_id', 'episode_number']);
            $table->index(['anime_id', 'episode_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
