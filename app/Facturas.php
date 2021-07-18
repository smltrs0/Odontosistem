<?php

namespace App;

use App\Abonos;
use App\citas_medicas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facturas extends Model
{
    protected $fileable = [
        'id',
        'cita_medica_id',
        'abono_creacion'
    ];


    public function Cita(){
        return $this->hasOne(citas_medicas::class, 'id', 'cita_medica_id');
    }
    
    public function Abonos(){
        return $this->hasMany(Abonos::class, 'factura_id', 'cita_medica_id');
    }

}
