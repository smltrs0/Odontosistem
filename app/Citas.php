<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    protected $fillable = [
        'fecha',
        'hora',
    ];

    public function citas()
    {
        return $this->belongsTo('App\Pacientes', 'paciente_id', 'id');
    }
}
