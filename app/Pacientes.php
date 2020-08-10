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
        'motivo-consulta',
        'registered_by',
        'sex',
        'user_id',
        'email',
                ];

    public function user()
    {
        return  $this->belongsTo('App\User');
    }
    public function citas()
    {
        return $this->hasMany('App\Citas');
    }
}
