<?php

namespace App;

use App\Facturas;
use App\Pacientes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Abonos extends Model
{

    protected $fileable=[
        'paciente_id',
        'abonado',
        'factura_id',
        'referencia',
        'adjunto',
        'nota',
        'methos_pay_id'
    ];

    public function factura(){
        return $this->belongsTo(Facturas::class, 'factura_id', 'id');
    }
    
    public function paciente(){
        return $this->belongsTo(Pacientes::class, 'paciente_id', 'id');
    }
}
