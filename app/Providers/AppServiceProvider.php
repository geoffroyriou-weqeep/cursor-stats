<?php

namespace App\Providers;

use App\Services\Cursor\Clients\HttpCursorUsageClient;
use App\Services\Cursor\Contracts\ComposerSessionRegistry;
use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\Contracts\SessionCredentialResolver;
use App\Services\Cursor\Registries\SqliteComposerSessionRegistry;
use App\Services\Cursor\Resolvers\CompositeSessionCredentialResolver;
use App\Services\Cursor\Resolvers\EnvSessionCredentialResolver;
use App\Services\Cursor\Resolvers\SqliteSessionCredentialResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SessionCredentialResolver::class, function ($app): SessionCredentialResolver {
            return new CompositeSessionCredentialResolver([
                $app->make(SqliteSessionCredentialResolver::class),
                $app->make(EnvSessionCredentialResolver::class),
            ]);
        });
        $this->app->bind(CursorUsageClient::class, HttpCursorUsageClient::class);
        $this->app->bind(ComposerSessionRegistry::class, SqliteComposerSessionRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
