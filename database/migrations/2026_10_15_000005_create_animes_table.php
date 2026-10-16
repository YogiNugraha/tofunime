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
        Schema::create('animes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('mal_id')->nullable()->unique();
            $table->foreignId('studio_id')->nullable()->constrained('studios')->nullOnDelete();
            $table->foreignId('season_id')->nullable()->constrained('seasons')->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('english_title', 255)->nullable();
            $table->string('japanese_title', 255)->nullable();
            $table->json('synonyms')->nullable();
            $table->text('synopsis')->nullable();
            $table->text('background_info')->nullable();
            $table->string('source_material', 80)->nullable();
            $table->string('poster_url', 500)->nullable();
            $table->string('banner_url', 500)->nullable();
            $table->string('trailer_youtube_id', 50)->nullable();
            $table->string('type', 20)->default('TV');
            $table->string('status', 20)->default('Ongoing');
            $table->unsignedSmallInteger('total_episodes')->nullable();
            $table->string('duration', 60)->nullable();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->string('aired_string', 100)->nullable();
            $table->string('release_day', 20)->nullable();
            $table->decimal('rating_score', 4, 2)->default(0.00);
            $table->unsignedInteger('scored_by')->default(0);
            $table->unsignedInteger('rank')->nullable();
            $table->string('age_rating', 80)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_recommended')->default(false)->index();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamp('latest_episode_at')->nullable();
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->timestamps();

            // Indexes
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'english_title', 'japanese_title']);
            }
            $table->index(['status', 'latest_episode_at']);
            $table->index(['type', 'status', 'rating_score']);
            $table->index(['release_day', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animes');
    }
};
