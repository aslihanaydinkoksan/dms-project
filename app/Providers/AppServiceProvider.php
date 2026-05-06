<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Document;
use App\Observers\DocumentObserver;
use App\Models\DocumentPhysicalMovement;
use App\Observers\DocumentPhysicalMovementObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Interfaces\AssistantServiceInterface::class,
            \App\Services\RuleBasedAssistantService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Document::observe(DocumentObserver::class);
        Paginator::useBootstrapFive();
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
        DocumentPhysicalMovement::observe(DocumentPhysicalMovementObserver::class);
        // Rol matrisi için
        \App\Models\FolderRolePermission::observe(\App\Observers\FolderPermissionObserver::class);

        // Pivot tablo için özel event tanımları
        \App\Models\FolderUserPermission::observe(\App\Observers\FolderPermissionObserver::class);
    }
}
