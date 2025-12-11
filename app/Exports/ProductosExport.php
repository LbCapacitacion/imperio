<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProductosExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Producto::select('nombre', 'precio', 'disponible', 'stock')->get();

    }
    public function headings(): array
    {
        // Encabezados para el Excel
        return ['Nombre', 'Precio', 'Disponible', 'Stock'];
    }

}
