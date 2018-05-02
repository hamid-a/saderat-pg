<?php
namespace SaderatPaymentGateway\Laravel\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static getToken($amount, $crn, $callback_url)
 * @method static verify($crn, $trn)
 */
class SaderatPG extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'SaderatPG';
    }
}