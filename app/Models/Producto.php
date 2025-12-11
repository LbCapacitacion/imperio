<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model
{
 
    
    use HasFactory;

    protected $fillable = [
        'nombre',
        'precio',
        'imagen',
        'disponible',
        'categoria_id',
        'stock'
    ];

    public function getImagenUrlAttribute()
    {
        return asset('storage/productos/' . $this->imagen);
    }
}
