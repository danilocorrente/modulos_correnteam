<?php

namespace Danilocorrente\ModulosCorrenteam;

use Illuminate\Support\ServiceProvider;
use Danilocorrente\ModulosCorrenteam\Services\ApiClient;

class ModulosCorrenteamServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('modulos_correnteam', function () {
            return new ApiClient();
        });
    }

    public function boot()
    {
        $this->publishes(
            [
                __DIR__ . '/../config/modulos_correnteam.php' => config_path('modulos_correnteam.php'),
            ],
            'config',
        );
    }
}
