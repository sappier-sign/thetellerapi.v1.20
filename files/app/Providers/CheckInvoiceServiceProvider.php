<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 07/01/2018
 * Time: 11:57 AM
 */

namespace App\Providers;


use App\Console\Commands\CheckInvoiceStatus;
use Illuminate\Support\ServiceProvider;

class CheckInvoiceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(CheckInvoiceStatus::class, function (){
            return CheckInvoiceStatus::class;
        });

        $this->commands(CheckInvoiceStatus::class);
    }
}