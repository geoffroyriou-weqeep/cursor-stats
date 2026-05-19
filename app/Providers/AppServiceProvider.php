<?php

namespace App\Providers;

use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\Contracts\SessionCredentialResolver;
use App\Services\Cursor\EnvSessionCredentialResolver;
use App\Services\Cursor\HttpCursorUsageClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SessionCredentialResolver::class, EnvSessionCredentialResolver::class);
        $this->app->bind(CursorUsageClient::class, HttpCursorUsageClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
