<?php

namespace Byte5\Addressable;

use Byte5\Addressable\App\Contracts as Contracts;
use Byte5\Addressable\App\Services as Services;
use Illuminate\Support\ServiceProvider;

class AddressableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // load package translations
        $this->loadTranslationsFrom(__DIR__.'/lang', 'byte5-addressable');

        if ($this->app->runningInConsole()) {
            // publish the migration stub (timestamped) so it can be customised (e.g. UUID/ULID keys)
            $this->publishes([
                __DIR__.'/../database/migrations/create_addresses_table.php.stub' => $this->app->databasePath(
                    'migrations/'.date('Y_m_d_His').'_create_addresses_table.php'
                ),
            ], 'byte5-addressable/migrations');
        }
    }

    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/addressable.php',
            'byte5-addressable'
        );

        $this->app->singleton(Contracts\AddressRules::class, Services\AddressRules::class);
        $this->app->singleton(Contracts\Countries::class, Services\Countries::class);
        $this->app->singleton(Contracts\CreatesAddresses::class, Services\AddressCreator::class);

        $this->app->singleton(Services\AddressLookupManager::class);
        $this->app->singleton(Services\AddressValidationManager::class);

        $this->app->singleton(
            Contracts\LookupFactory::class,
            fn ($app) => $app->make(Services\AddressLookupManager::class),
        );

        $this->app->singleton(
            Contracts\ValidationFactory::class,
            fn ($app) => $app->make(Services\AddressValidationManager::class),
        );

        $this->app->singleton(
            Contracts\AddressLookup::class,
            fn ($app) => $app->make(Services\AddressLookupManager::class)->driver(),
        );

        $this->app->singleton(
            Contracts\ValidatesAddresses::class,
            fn ($app) => $app->make(Services\AddressValidationManager::class)->driver(),
        );

        if ($this->app->runningInConsole()) {
            // publish package config
            $this->publishes([
                __DIR__.'/../config/addressable.php' => $this->app->configPath('byte5-addressable.php'),
            ], 'byte5-addressable/config');
        }
    }
}
