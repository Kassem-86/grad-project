<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Models\Glucose;
use App\Policies\GlucosePolicy;
use App\Models\Meal;
use App\Policies\MealPolicy;
use App\Models\Medication;
use App\Policies\MedicationPolicy;
use App\Models\Post;
use App\Policies\PostPolicy;
use App\Models\Comment;
use App\Policies\CommentPolicy;
use App\Models\Like;
use App\Observers\LikeObserver;
use App\Observers\CommentObserver;

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
        URL::forceScheme('https');

        // Register policies
        Gate::policy(Glucose::class, GlucosePolicy::class);
        Gate::policy(Meal::class, MealPolicy::class);
        Gate::policy(Medication::class, MedicationPolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);

        // Register observers
        Like::observe(LikeObserver::class);
        Comment::observe(CommentObserver::class);
    }
}


