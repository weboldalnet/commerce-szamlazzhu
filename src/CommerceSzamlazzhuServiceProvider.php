<?php

namespace Weboldalnet\CommerceSzamlazzhu;

use Illuminate\Support\ServiceProvider;
use Weboldalnet\CommerceSzamlazzhu\Support\PackageHelper;
use Weboldalnet\CommerceSzamlazzhu\Console\ExtendViewsCommerceSzamlazzhuCommand;
use Weboldalnet\CommerceSzamlazzhu\Console\InstallCommerceSzamlazzhuCommand;
use Weboldalnet\CommerceCore\Managers\InvoiceManager;
use Weboldalnet\CommerceSzamlazzhu\Providers\SzamlazzhuInvoiceProvider;
use Weboldalnet\CommerceSzamlazzhu\Services\SzamlazzhuService;
use Weboldalnet\CommerceSzamlazzhu\Services\SzamlazzhuSettingsService;
use Weboldalnet\CommerceCore\Services\ProviderLogger;

class CommerceSzamlazzhuServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Alap útvonal beállítása a Számla Agent SDK-hoz
        $basePath = storage_path('app/private/commerce-szamlazzhu');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0775, true);
        }
        
        // Alkönyvtárak létrehozása
        foreach (['logs', 'pdf', 'xmls', 'attachments'] as $dir) {
            $fullDir = $basePath . DIRECTORY_SEPARATOR . $dir;
            if (!file_exists($fullDir)) {
                mkdir($fullDir, 0775, true);
            }
        }
        
        SzamlaAgentUtil::setBasePath($basePath . DIRECTORY_SEPARATOR);

        // route-ok
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../settings/views', PackageHelper::PACKAGE_PREFIX);

        // Provider regisztráció
        if (SzamlazzhuSettingsService::getBool('enabled', false)) {
            try {
                $invoiceManager = $this->app->make(InvoiceManager::class);
                $invoiceManager->register(
                    'szamlazzhu',
                    $this->app->make(SzamlazzhuInvoiceProvider::class)
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Számlázz.hu regisztrációs hiba: ' . $e->getMessage());
            }
        }

        // migrációk
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $publishList = [];
        foreach (PackageHelper::PACKAGE_LIST as $name => $publish) {
            $this->publishes([
                $publish['source'] => base_path($publish['destination']),
            ], PackageHelper::PACKAGE_PREFIX . '-' . $name);

            $publishList[$publish['source']] = base_path($publish['destination']);
        }

        $this->publishes($publishList, PackageHelper::PACKAGE_PREFIX . '-all');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/commerce-szamlazzhu.php', 'commerce-szamlazzhu');

        $this->app->singleton(SzamlazzhuSettingsService::class, function ($app) {
            return new SzamlazzhuSettingsService();
        });

        $this->app->singleton(SzamlazzhuService::class, function ($app) {
            return new SzamlazzhuService(
                $app->make(SzamlazzhuSettingsService::class),
                $app->make(ProviderLogger::class)
            );
        });

        $this->app->singleton(SzamlazzhuInvoiceProvider::class, function ($app) {
            return new SzamlazzhuInvoiceProvider($app->make(SzamlazzhuService::class));
        });

        $this->commands([
            InstallCommerceSzamlazzhuCommand::class,
        ]);

        $this->commands([
            ExtendViewsCommerceSzamlazzhuCommand::class,
        ]);
    }
}
