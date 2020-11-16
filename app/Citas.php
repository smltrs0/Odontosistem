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
        return $this->hasOne('App\Pacientes', 'id', 'paciente_id');
    }
}
