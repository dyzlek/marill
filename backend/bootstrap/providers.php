<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class, // back-office Filament sur /admin (A1 à A11)
    FortifyServiceProvider::class,
];
