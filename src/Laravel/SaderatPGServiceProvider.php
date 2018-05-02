<?php

namespace SaderatPaymentGateway\Laravel;

use SaderatPaymentGateway\SaderatPG;
use Illuminate\Support\ServiceProvider;

class SaderatPGServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('SaderatPG', function () {

            $saderat_configs = config('services.saderat-pg');

            $tid = isset($saderat_configs['tid']) ? $saderat_configs['tid'] : null;
            $mid = isset($saderat_configs['mid']) ? $saderat_configs['mid'] : null;
            $public_key = isset($saderat_configs['public-key']) ? $saderat_configs['public-key'] : null;
            $private_key = isset($saderat_configs['private-key']) ? $saderat_configs['private-key'] : null;
            $callback_url = isset($saderat_configs['callback-url']) ? $saderat_configs['callback-url'] : null;

            return new SaderatPG($tid, $mid, $public_key, $private_key, $callback_url);
        });
    }

}