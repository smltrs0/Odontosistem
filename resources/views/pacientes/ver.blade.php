@extends('layouts.app')
@section('content')
@section('title', 'Ver paciente')
<div class="row ">
    <div class="col-md-12">
        <div class="card mb-5">
            <div class="card-header">
                <div>
                    Paciente: {{$paciente->name." ". $paciente->second_name." ". $paciente->last_name." ".$paciente->second_last_name}}</div>
                <a class="float-right position-relative btn btn-primary btn-sm" href="{{ route('pacientes.edit',$paciente->id) }}">Editar
                    paciente</a>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-end flex-column">
                </div>
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-datos-personales-tab" data-toggle="tab"
                           href="#nav-datos-personales" role="tab" aria-controls="nav-datos-personales"
                           aria-selected="true">Datos personales</a>
                        <a class="nav-item nav-link" id="nav-antecedentes-tab" data-toggle="tab"
                           href="#nav-antecedentes" role="tab" aria-controls="nav-antecedentes"
                           aria-selected="false">Anamnesis
                            general</a>
                        <a class="nav-item nav-link ml-auto" href="javascript:ventanaSecundaria('{{ url("paciente/{$paciente->id}/odontograma") }}')">Odontograma</a>
                        <a class="nav-item nav-link nav-item " href="javascript:ventanaSecundaria('{{ url("citas-medicas/{$paciente->id}") }}')">Citas medicas</a>
                    </div>
                </nav>
                <!--Final de las pestañas-->

                <div class="container m-3 mt-4">
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-datos-personales" role="tabpanel"
                             aria-labelledby="nav-datos-personales-tab">
                            <label>{{__('Full name')}}:</label>
                            <div class="row form-group">
                                <div class="col">
                                    {{ $paciente->name." ".$paciente->second_name." ".$paciente->last_name." ".$paciente->second_last_name}}
                                </div>
                            </div>
                            <label> Documento nacional de identificacion:</label>
                            <div class="row form-group">
                                <div class="col-4">
                                    <label>Tipo de documento:</label>
                                    Cedula de identidad <!--Cambiar-->
                                </div>
                                <div class="col-8">
                                    <label for="">Numero de documento:</label>
                                    {{$paciente->dni}}
                                </div>
                            </div>
                            <label for="sex">Sexo:</label>
                            <div class="form-group" id="sex">
                                @if ($paciente->sex== 1)
                                    Hombre
                                @else
                                    Mujer
                                @endif
                            </div>
                            <label for="birth_date">Fecha de nacimiento:</label>
                            <div class="form-group">
                                {{ $paciente->birth_date }}
                            </div>
                            <div class="form-group">
                                <label for="inputPhone">Telefono de contacto:</label>
                                <p id="inputPhone">{{ $paciente->phone}}</p>
                            </div>
                            <div class="form-group">
                                <label for="">Correo electronico:</label>
                                {{ $paciente->email}}
                            </div>
                            <label>Dirección:</label>
                            <div class="form-group">
                                {{ $paciente->address}}
                            </div>

                        </div>
                        <!--Final de datos personales-->
                        <div class="tab-pane fade" id="nav-antecedentes" role="tabpanel"
                             aria-labelledby="nav-antecedentes-tab">
                            <div class="form-gropu">
                                <label for="motivo-consulta">Primer motivo consulta:</label>
                                <p>{{ $paciente->motivoConsulta }}</p>
                            </div>
                            <div class="form-row mt-1">
                                <div class="col">
                                    <label for="antecedentes-m">Antecedentes Médicos:</label>
                                   <p>
                                       {{ $paciente->antecedentes }}
                                   </p>

                                </div>
                                <div class="col">
                                    <label for="alergias">Alergias:</label>
                                    <ul>
                                        @if(is_null($paciente->alergias))
                                            {{'Ninguna'}}
                                        @else
                                            {{ $paciente->alergias  }}
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <div class="form-row mt-1">
                                <div class="col">
                                    <label>Medicamentos en uso:</label>
                                   <p>
                                        @if($paciente->medical_history)
                                            <ul>
                                                {{ $paciente->medical_history }}
                                            </ul>
                                        @else
                                            Nínguno
                                        @endif
                                   </p>

                                </div>
                                <div class="col">
                                    <label for="habitos">Hábitos:</label>
                                    <ul>
                                       {{ $paciente->habitos }}
                                    </ul>
                                </div>
                            </div>
                            <div class="form-row mt-1">
                                <div class="col">
                                    <label for="antecedentes-f">Antecedentes familiares:</label>
                                    <p>
                                        {{ $paciente->antecedentes }}
                                    </p>
                                </div>
                                <div class="col">
                                    <label for="otros">Otros:</label>
                                    <p>
                                        @if($paciente->otros)
                                        {{ $paciente->otros }}
                                        @else
                                            Nínguno
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="form-row mt-2">
                                <div class="col">
                                    <label for="peso">Peso (kgs):</label>
                                    <p>{{ $paciente->height }}</p>
                                </div>
                                <div class="col">
                                    <label for="altura">Altura (cms):</label>
                                    <p id="peso">{{ $paciente->weight }}</p>
                                </div>
                            </div>
                            <div class="form-row mt-4">
                                <div class="col-md-4">
                                    <label>¿Paciente Embarazada?</label>
                                    <div>
                                        <label class="radio-inline">
                                            <input type="radio" name="embarazada" value="1" readonly>Si</label>
                                        <label class="radio-inline">
                                            <input type="radio" name="embarazada" value="0" checked="" readonly>No</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label>¿Ha presentado problemas de Coagulación?</label>
                                    <div>
                                        <label class="radio-inline">
                                            <input type="radio" name="coagulacion" value="1" readonly>Si</label>
                                        <label class="radio-inline">
                                            <input type="radio" name="coagulacion" value="0" checked="" readonly>No</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label>¿Ha presentado problemas con Anestésicos Locales?</label>
                                    <div>
                                        <label class="radio-inline">
                                            <input type="radio" name="anestesicos" value="1" readonly>Si</label>
                                        <label class="radio-inline">
                                            <input type="radio" name="anestesicos" value="0" checked="" readonly>No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--Final de antecedentes-->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

