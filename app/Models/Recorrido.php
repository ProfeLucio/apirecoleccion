<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recorrido extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'ruta_id',
        'vehiculo_id',
        'conductor_id',
        'ts_inicio',
        'ts_fin',
        'estado',
    ];

    public function conductor()
    {
        return $this->belongsTo(User::class, 'conductor_id');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }

    public function posiciones()
    {
        return $this->hasMany(Posicion::class);
    }
}
