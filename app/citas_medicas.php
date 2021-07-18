<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class citas_medicas extends Model
{
    //


    protected $fillable= [
        'date',	
        'pacientes_id',
        'evaluacion',	
        'medicacion',	
        'analisis_solocitados',	
        'comentario_paciente',	
        'comentario_doctor',
    ];



    public function Paciente()
    {
        return $this->belongsTo(Pacientes::class, 'pacientes_id');
    }

    public function procedimientos() {
        return $this->belongsToMany('App\procedure')->withPivot('cantidad');
    }
}
