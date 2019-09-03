<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Model\OneTouchRepositoryInterface;
use App\Repositories\OneTouchRepository;
use Illuminate\Support\ServiceProvider;

/**
 * @author Robert Matuszewski <robmatu@gmail.com>
 */
class AppRepositoryProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(OneTouchRepositoryInterface::class, OneTouchRepository::class);
    }
}
