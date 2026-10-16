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
        Schema::create('scraper_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_name', 50)->default('otakudesu');
            $table->string('target_url', 500)->nullable();
            $table->string('action', 100);
            $table->string('status', 30);
            $table->unsignedInteger('items_processed')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['provider_name', 'action', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_logs');
    }
};
