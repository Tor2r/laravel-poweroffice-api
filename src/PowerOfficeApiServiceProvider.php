<?php

namespace Tor2r\PowerOfficeApi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PowerOfficeApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('poweroffice-api')
            ->hasConfigFile('poweroffice-api');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PowerOfficeClient::class, function () {
            $environment = config('poweroffice-api.environment', 'demo');
            $urls = config("poweroffice-api.environments.{$environment}");

            return new PowerOfficeClient(
                appKey: config('poweroffice-api.app_key'),
                clientKey: config('poweroffice-api.client_key'),
                subscriptionKey: config('poweroffice-api.subscription_key'),
                baseUrl: $urls['base_url'],
                tokenUrl: $urls['token_url'],
                tokenTtl: config('poweroffice-api.token_ttl', 900),
            );
        });
    }
}
