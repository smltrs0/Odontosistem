<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class citas_medicas extends Model
{
    //


    protected $fillable= [
        'date',	
        'evaluacion',	
        'medicacion',	
        'analisis_solocitados',	
        'comentario_paciente',	
        'comentario_doctor',
    ];



    public function Paciente()
    {
        return $this->belongsTo('App\Pacientes');
    }

    public function procedimientos() {
        return $this->belongsToMany('App\procedure')->withPivot('cantidad');
    }
}
