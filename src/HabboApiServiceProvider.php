<?php

namespace HabboFeeling\HabboWebApi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HabboApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-habbo-web-api')
            ->hasConfigFile('habbo-api');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(HabboApi::class, fn () => new HabboApi(
            config('habbo-api.domain'),
            config('habbo-api.request_timeout'),
        ));

        $this->app->alias(HabboApi::class, 'habbo-api');
    }
}
