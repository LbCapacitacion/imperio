<?php

use Illuminate\Http\Request;
use App\Exports\ProductosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CategoriaController;



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Mesero: ver el menú disponible y tomar pedidos.
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::post('/pedidos', [PedidoController::class, 'store']);

    Route::middleware('admin')->group(function () {
        Route::apiResource('/pedidos', PedidoController::class)->except(['store']);
        Route::apiResource('/categorias', CategoriaController::class)->except(['index']);

        Route::get('/productos/admin', [ProductoController::class, 'adminIndex']);
        Route::apiResource('/productos', ProductoController::class)->except(['index']);

        Route::put('/productos/{producto}/agotado', [ProductoController::class, 'marcarAgotado']);
        Route::put('/productos/{producto}/disponible', [ProductoController::class, 'marcarDisponible']);
        Route::put('/productos/{producto}/recargar-stock', [ProductoController::class, 'recargarStock']);

        Route::patch('/pedidos/{pedido}/complete', [PedidoController::class, 'complete']);
        Route::get('/reportes/ventas', [PedidoController::class, 'ventasDelDia']);
    });
});



//Route::api('/categorias', [CategoriaController::class, 'index']);



//autenticacion

Route::post('/registro', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
