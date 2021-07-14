<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    protected $fillable = [
        'fecha',
        'hora',
        'asistencia_confirmada',
        'atendido'
    ];

    public function Paciente()
    {
        return $this->belongsTo('App\Pacientes', 'paciente_id', 'id');
    }
}
