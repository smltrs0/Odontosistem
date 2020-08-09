<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    protected $fillable = [
        'fecha',
    ];

    public function citas()
    {
        return $this->belongsTo('App\Pacientes', 'paciente_id', 'pacientes');
    }
}
