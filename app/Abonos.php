<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Abonos extends Model
{

    protected $fileable=[
        'abonado',
        'refencia',
        'nota',
    ];


    public function factura()
    {
        return $this->belongsTo('App\Facturas');
    }
    
    public function paciente()
    {
        return $this->belongsTo('App\Pacientes');
    }
}
