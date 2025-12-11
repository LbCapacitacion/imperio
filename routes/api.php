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



    //almacenar pedidos
    Route::apiResource('/pedidos', PedidoController::class);
    Route::apiResource('/categorias', CategoriaController::class);

    //Route::get('/productos/export', [ProductoController::class, 'exportExcel']);
    Route::get('/productos/admin', [ProductoController::class, 'adminIndex']);
    
    Route::apiResource('/productos', ProductoController::class);


    Route::put('/productos/{producto}/agotado', [ProductoController::class, 'marcarAgotado']);
    Route::put('/productos/{producto}/disponible', [ProductoController::class, 'marcarDisponible']);
    Route::put('/productos/{producto}/recargar-stock', [ProductoController::class, 'recargarStock']);

    //mi code
    Route::patch('/pedidos/{pedido}/complete', [PedidoController::class, 'complete']); //para imprimir ticket
    //Route::put('/pedidos/{pedido}/agregar-productos', [PedidoController::class, 'agregarProductos']);

    Route::get('/reportes/ventas', [PedidoController::class, 'ventasDelDia']);
    
});



//Route::api('/categorias', [CategoriaController::class, 'index']);



//autenticacion

Route::post('/registro', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
