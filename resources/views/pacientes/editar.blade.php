@extends('layouts.app')

@section('content')
@section('title', 'Editar paciente')
<div class="row ">
    <div class="col-md-12">
        <div class="card mb-3">
        <div class="card-header text-center"><div>Editar paciente</div></div>
            <div class="card-body">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                      <a class="nav-item nav-link active" id="nav-datos-personales-tab" data-toggle="tab" href="#nav-datos-personales" role="tab" aria-controls="nav-datos-personales" aria-selected="true">Datos personales</a>
                      <a class="nav-item nav-link" id="nav-antecedentes-tab" data-toggle="tab" href="#nav-antecedentes" role="tab" aria-controls="nav-antecedentes" aria-selected="false">Antecedentes medicos</a>
                      <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab" aria-controls="nav-contact" aria-selected="false">Citas medicas</a>
                    </div>
                </nav>
                <!--Final de las pestañas-->
            
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-datos-personales" role="tabpanel" aria-labelledby="nav-datos-personales-tab">
                        <form action="{{ route('pacientes.update', $paciente->id)}}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('pacientes.__formulario')
                            <button class="btn btn-primary">Modificar datos del paciente</button>
                        </form>
                    </div>
                    <!--Final de datos personales-->
                    <div class="tab-pane fade" id="nav-antecedentes" role="tabpanel" aria-labelledby="nav-antecedentes-tab">Antecedentes medicos</div>
                    <!--Final de antecedentes-->
                    <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
                        Citas medicas

                    </div>
                    <!--Final de citas medicas-->
                </div>
                
            </div>
        </div>
    </div>
</div>

@endsection