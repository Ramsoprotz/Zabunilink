<?php

use Illuminate\Support\Facades\Route;

// Filament admin panel is auto-registered at /admin

// Serve Vue SPA for root
Route::get('/', function () {
    return file_get_contents(public_path('spa/index.html'));
});

// Catch-all: serve the Vue SPA for all non-API, non-admin routes
Route::get('/{any}', function () {
    return file_get_contents(public_path('spa/index.html'));
})->where('any', '^(?!api|admin|livewire|filament|storage).*$');
