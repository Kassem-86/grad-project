<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Glucose;
use App\Policies\GlucosePolicy;
use App\Models\Meal;
use App\Policies\MealPolicy;
use App\Models\Medication;
use App\Policies\MedicationPolicy;

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
        // Register policies
        Gate::policy(Glucose::class, GlucosePolicy::class);
        Gate::policy(Meal::class, MealPolicy::class);
        Gate::policy(Medication::class, MedicationPolicy::class);
    }
}
