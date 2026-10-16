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
        Schema::create('download_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('anime_id')->constrained('animes')->cascadeOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained('episodes')->nullOnDelete();
            $table->boolean('is_batch')->default(false);
            $table->string('format', 20)->default('MP4');
            $table->string('quality', 20);
            $table->string('host_name', 80);
            $table->text('url');
            $table->string('file_size', 40)->nullable();
            $table->timestamps();

            $table->index(['anime_id', 'is_batch']);
            $table->index(['episode_id', 'quality']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_links');
    }
};
