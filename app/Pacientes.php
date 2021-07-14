<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pacientes extends Model
{
    //
    protected $fillable= [
        'name',
        'second_name',
        'last_name',
        'second_last_name',
        'dni',
        'birth_date',
        'phone',
        'address',
        'height',
        'weight',
        'medical_history',
        'procedures',
        'antecedentes',
        'alergias',
        'habitos',
        'motivoConsulta',
        'registered_by',
        'medical_history',
        'sex',
        'user_id',
        'email',
        'coagulacion',
        'embarazo',
        'anestesicos'
                ];

    public function user(){
        return  $this->belongsTo('App\User');
    }

    public function citas(){
        return $this->hasMany('App\Citas');
    }

    public function citas_medicas(){
        return $this->hasMany('App\citas_medicas');
    }
}
