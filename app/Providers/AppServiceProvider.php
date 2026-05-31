<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Vistas propias de paginación en resources/views/pagination/
        Paginator::defaultView('pagination.taller');
        Paginator::defaultSimpleView('pagination.taller-simple');
    }
}
