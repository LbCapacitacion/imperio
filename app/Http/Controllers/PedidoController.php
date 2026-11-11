<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Models\PedidoProducto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PedidoCollection;

class PedidoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new PedidoCollection(Pedido::with('user')->with('productos')->where('estado', 0)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'mesa' => 'required|string|max:255',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        $cantidades = collect($request->input('productos'))
            ->groupBy(fn (array $linea) => (int) $linea['id'])
            ->map(fn ($lineas) => (int) $lineas->sum('cantidad'));

        try {
            DB::beginTransaction();

            $productos = Producto::whereIn('id', $cantidades->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $pedidoProducto = [];

            foreach ($cantidades as $productoId => $cantidad) {
                $producto = $productos->get($productoId);

                if (! $producto || (int) $producto->disponible !== 1) {
                    DB::rollBack();
                    $nombre = $producto->nombre ?? $productoId;

                    return response()->json([
                        'error' => "Producto no disponible: {$nombre}",
                    ], 400);
                }

                if ($producto->stock < $cantidad) {
                    DB::rollBack();

                    return response()->json([
                        'error' => "Stock insuficiente o producto no encontrado: {$producto->nombre}",
                    ], 400);
                }

                $total += (float) $producto->precio * $cantidad;
                $producto->decrement('stock', $cantidad);

                $pedidoProducto[] = [
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }

            $pedido = new Pedido;
            $pedido->user_id = Auth::id();
            $pedido->mesa = $request->mesa;
            $pedido->total = round($total, 2);
            $pedido->save();

            foreach ($pedidoProducto as &$linea) {
                $linea['pedido_id'] = $pedido->id;
            }
            unset($linea);

            PedidoProducto::insert($pedidoProducto);

            DB::commit(); // Si todo fue bien, guardar los cambios

            return response()->json([
                'message' => 'Pedido realizado correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Algo falló, revertir todo
            Log::error('Error creando pedido con stock: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al procesar el pedido'
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Pedido $pedido)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pedido $pedido)
    {
        $pedido->estado = 1;
        $pedido->save();
        return [
            'pedido' => $pedido
        ];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pedido $pedido)
    {
        //
    }

    //mi code
    //para los tickets
    public function complete(Request $request, Pedido $pedido)
    {
        try {

            $pedido->estado = 1;
            //'updated_at' => now(),
            $pedido->save();


            // devolver el pedido actualizado (incluye productos si la relación se llama 'productos')
            return response()->json([
                'message' => 'Pedido completado',
                'data' => $pedido->load('productos', 'user')
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error completando pedido: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json(['message' => 'Error al completar pedido'], 500);
        }
    }


    public function ventasDelDia(Request $request)
    {
        // recibir ?date=YYYY-MM-DD, por defecto hoy
        $dateString = $request->query('date', now()->toDateString());

        try {
            $date = Carbon::parse($dateString);

            // Inicio: 20:00 del día $date
            $start = $date->copy()->setTime(14, 0, 0);
            // Fin: 06:00 del día siguiente
            $end = $date->copy()->addDay()->setTime(8, 0, 0);

            $pedidos = Pedido::with('productos', 'user')
                ->where('estado', 1)
                ->whereBetween('updated_at', [$start, $end])
                ->get();

            $totalDia = $pedidos->sum('total');

            return response()->json([
                'date' => $date->toDateString(),
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
                'total_sales' => $totalDia,
                'count' => $pedidos->count(),
                'pedidos' => $pedidos
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error generando reporte de ventas: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return response()->json(['message' => 'Error al generar reporte de ventas'], 500);
        }
    }
    // ...existing code...
}
