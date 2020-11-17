@extends('layouts.app')
@section('content')
@section('title', 'Editar paciente')

<div class="row ">
    <div class="col-md-12">
        <div class="card mb-5">
            <div class="card-header text-center">
                <div>Editar paciente</div>
            </div>
            <div class="card-body">
                <form action="{{ route('pacientes.update', $paciente->id)}}" method="POST">
                    @csrf
                    @method('PUT')
                    <nav class="mb-4 mx-auto">
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

                    <div class="tab-content mt-1" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-datos-personales" role="tabpanel"
                             aria-labelledby="nav-datos-personales-tab">
                            @include('pacientes.__formulario')

                        </div>
                        <!--Final de datos personales-->
                        <div class="tab-pane fade" id="nav-antecedentes" role="tabpanel"
                             aria-labelledby="nav-antecedentes-tab">
                            <div class="form-gropu">
                                <label for="motivo-consulta">Primer motivo consulta</label>
                                <textarea class="form-control" name="motivo-consulta" id="motivo-consulta">{{$paciente->motivoConsulta}}</textarea>
                            </div>
                            <div class="form-row mt-1">
                                <div class="col">
                                    <label for="antecedentes-m">Antecedentes Médicos:</label>
                                    <textarea class="form-control" name="antecedentes" id="antecedentes-m">{{ $paciente->antecedentes}}</textarea>
                                </div>
                                <div class="col">
                                    <label for="alergias">Alergias</label>
                                    <textarea class="form-control" name="alergias" id="alergias">{{ $paciente->alergias}}</textarea>
                                </div>
                            </div>
                            <div class="form-row mt-1">
                                <div class="col">
                                    <label for="medicamentos">Medicamentos en uso</label>
                                    <textarea class="form-control" name="medicamentos" id="medicamentos">{{$paciente->medicamentos}}</textarea>
                                </div>
                                <div class="col">
                                    <label for="habitos">Hábitos</label>
                                    <textarea class="form-control" name="habitos" id="habitos">{{ $paciente->habitos}}</textarea>
                                </div>
                            </div>
                            <div class="form-row mt-1">
                                <div class="col">
                                    <label for="antecedentes-f">Antecedentes familiares</label>
                                    <textarea class="form-control" name="antecedentes-f" id="antecedentes-f">{{$paciente->antecedentes}}</textarea>
                                </div>
                                <div class="col">
                                    <label for="otros">Otros</label>
                                    <textarea class="form-control" name="otros"
                                              id="otros">{{$paciente->otros}}</textarea>
                                </div>
                            </div>
                            <div class="form-row mt-2">
                                <div class="col">
                                    <label for="peso">Peso (kgs):</label>
                                    <input id="peso" name="weight" class="form-control" type="text" value="{{$paciente->weight}}">
                                </div>
                                <div class="col">
                                    <label for="altura">Altura (cms):</label>
                                    <input id="altura" class="form-control" type="text" name="height" value="{{$paciente->height}}">
                                </div>
                            </div>
                            <div class="form-row mt-4">
                                <div class="col-md-4">
                                    <label for="embaraso">¿Paciente Embarazada?</label>
                                    <div id="embaraso">
                                        <label class="radio-inline">
                                            <input type="radio" name="embarazada" value="1">Si</label>
                                        <label class="radio-inline">
                                            <input type="radio" name="embarazada" value="0" checked="">No</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label>¿Ha presentado problemas de Coagulación?</label>
                                    <div>
                                        <label class="radio-inline">
                                            <input type="radio" name="coagulacion" value="1">Si</label>
                                        <label class="radio-inline">
                                            <input type="radio" name="coagulacion" value="0" checked="">No</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label>¿Ha presentado problemas con Anestésicos Locales?</label>
                                    <div>
                                        <label class="radio-inline">
                                            <input type="radio" name="anestesicos" value="1">Si</label>
                                        <label class="radio-inline">
                                            <input type="radio" name="anestesicos" value="0" checked="">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--Final de antecedentes-->


                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        var bPreguntar = true;

        window.onbeforeunload = preguntarAntesDeSalir;

        function preguntarAntesDeSalir () {
            var respuesta;
            if ( bPreguntar ) {
                respuesta = confirm ( '¿Seguro que quieres salir?' );

                if ( respuesta ) {
                    window.onunload = function () {
                        return true;
                    }
                } else {
                    return false;
                }
            }
        }
    </script>

@endsection()
