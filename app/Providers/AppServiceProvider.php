<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Models\Glucose;
use App\Policies\GlucosePolicy;
use App\Models\Meal;
use App\Policies\MealPolicy;
use App\Models\Post;
use App\Policies\PostPolicy;
use App\Models\Comment;
use App\Policies\CommentPolicy;
use App\Models\RecordMedication;
use App\Policies\RecordMedicationPolicy;
use App\Models\SelectedMedication;
use App\Policies\SelectedMedicationPolicy;
use App\Models\Like;
use App\Observers\LikeObserver;
use App\Observers\CommentObserver;
use App\Models\Reminder;
use App\Policies\ReminderPolicy;

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
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(RecordMedication::class, RecordMedicationPolicy::class);
        Gate::policy(SelectedMedication::class, SelectedMedicationPolicy::class);
        Gate::policy(Reminder::class, ReminderPolicy::class);

        // Register observers
        Like::observe(LikeObserver::class);
        Comment::observe(CommentObserver::class);
    }
}


