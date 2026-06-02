<?php

use App\Http\Controllers\PurificadoraColibri\PurifidadoraPedidoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Purificadora Colibrí — pedidos de agua (público)
|--------------------------------------------------------------------------
*/

Route::prefix('puricadora')
    ->name('puricadora.')
    ->group(function () {
        Route::post('pedidos', [PurifidadoraPedidoController::class, 'store'])
            ->name('pedidos.store');
    });
