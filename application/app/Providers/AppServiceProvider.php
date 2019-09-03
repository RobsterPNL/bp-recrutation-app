<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Model\RegistrationServiceInterface;
use App\Model\ReminderServiceInterface;
use App\Services\RegistrationService;
use App\Services\ReminderService;
use Illuminate\Support\ServiceProvider;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind('Illuminate\Contracts\Auth\Registrar', 'App\Services\Registrar');
        $this->app->bind('App\Authy\Service', 'App\Services\Authy');
        $this->app->bind(RegistrationServiceInterface::class, RegistrationService::class);
        $this->app->bind(ReminderServiceInterface::class, ReminderService::class);
    }

}
