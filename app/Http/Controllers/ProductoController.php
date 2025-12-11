<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use App\Exports\ProductosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ProductoCollection;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Producto::orderBy('id', 'DESC');

        if (!$user->admin) {
            $query->where('disponible', 1);
        }

        return new ProductoCollection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'imagen' => 'nullable|image|max:2048',
            'disponible' => 'boolean',
            'categoria_id' => 'required|exists:categorias,id',
            'stock' => 'sometimes|integer|min:0'

        ]);

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('productos', 'public');
            $validated['imagen'] = basename($path);
        }

        $producto = Producto::create($validated);

        return response()->json($producto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'imagen' => 'nullable|image|max:2048',
            'stock' => 'sometimes|integer|min:0'



        ]);
        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($producto->imagen && Storage::disk('public')->exists('productos/' . $producto->imagen)) {
                Storage::disk('public')->delete('productos/' . $producto->imagen);
            }

            // Guardar nueva imagen
            $path = $request->file('imagen')->store('productos', 'public');
            $validated['imagen'] = basename($path);
        }



        $producto->update($validated);

        return response()->json([
            'message' => 'Producto actualizado correctamente'

        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto)
    {
        if ($producto->imagen && Storage::disk('public')->exists('productos/' . $producto->imagen)) {
            Storage::disk('public')->delete('productos/' . $producto->imagen);
        }

        $producto->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);
    }
    public function marcarAgotado(Producto $producto)
    {
        $producto->disponible = 0;
        $producto->save();

        return response()->json(['message' => 'Producto marcado como agotado']);
    }

    public function marcarDisponible(Producto $producto)
    {
        $producto->disponible = 1;

        $producto->save();

        return response()->json(['message' => 'Producto disponible nuevamente']);
    }

    public function adminIndex()
    {
        return new ProductoCollection(Producto::orderBy('id', 'DESC')->get());
    }

    public function recargarStock(Request $request, Producto $producto)
    {
        $user = $request->user();

        if (!$user->admin) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'cantidad' => 'required|integer|min:1'
        ]);

        $producto->increment('stock', $validated['cantidad']);

        $producto->refresh();
        return response()->json(['message' => 'Stock recargado correctamente', 'producto' => $producto]);
    }
    
    
}
