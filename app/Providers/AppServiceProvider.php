<?php

namespace App\Providers;

use App\Models\Anime;
use App\Models\Episode;
use App\Observers\AnimeObserver;
use App\Observers\EpisodeObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Anime::observe(AnimeObserver::class);
        Episode::observe(EpisodeObserver::class);
    }
}
