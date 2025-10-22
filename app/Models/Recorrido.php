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
        'perfil_id',
        'ts_inicio',
        'ts_fin',
        'estado',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }

    public function perfil()
    {
        return $this->belongsTo(Perfil::class);
    }

}
