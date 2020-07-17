@extends('layouts.app')
@section('content')
@section('title', 'Editar paciente')

<?php
$mysql_result = [
    "event_id" => 11492,     // ID del paciente
    "name" => "Samuel Trias",  // Nombre del paciente
    "showsPrimary" => false,   // Dientes de leche, si es true existen
    "comments" => "Este es un comentario de prueba desde un json",
    "procedures" => '[{"tooth":22,"pro":82,"title":"Resina preventiva buena","side":false, "type": "Completado"},
	  {"tooth":23,"pro":99,"title":"Superficie Cariada","side":"center", "type": "Pendiente"}
	  ]',
];
?>
<div class="row ">
    <div class="col-md-12">
        <div class="card mb-5">
            <div class="card-header text-center">
                <div>Editar paciente</div>
            </div>
            <div class="card-body">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-datos-personales-tab" data-toggle="tab"
                           href="#nav-datos-personales" role="tab" aria-controls="nav-datos-personales"
                           aria-selected="true">Datos personales</a>
                        <a class="nav-item nav-link" id="nav-antecedentes-tab" data-toggle="tab"
                           href="#nav-antecedentes" role="tab" aria-controls="nav-antecedentes" aria-selected="false">Anamnesis
                                                                                                                      general</a>
                        <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-contact"
                           role="tab" aria-controls="nav-contact" aria-selected="false">Citas medicas</a>
                        <a class="nav-item nav-link" id="nav-odontograma-tab" data-toggle="tab" href="#nav-odontograma"
                           role="tab" aria-controls="nav-odontograma" aria-selected="false">Odontograma</a>
                    </div>
                </nav>
                <!--Final de las pestañas-->

                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-datos-personales" role="tabpanel"
                         aria-labelledby="nav-datos-personales-tab">
                        <form action="{{ route('pacientes.update', $paciente->id)}}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('pacientes.__formulario')
                            <button class="btn btn-primary">Modificar datos del paciente</button>
                        </form>
                    </div>
                    <!--Final de datos personales-->
                    <div class="tab-pane fade" id="nav-antecedentes" role="tabpanel"
                         aria-labelledby="nav-antecedentes-tab">
                        <div class="form-gropu">
                            <label for="motivo-consulta">Primer motivo consulta</label>
                            <textarea class="form-control" name="motivo-consulta" id="motivo-consulta"></textarea>
                        </div>
                        <div class="form-row mt-1">
                            <div class="col">
                                <label for="antecedentes-m">Antecedentes Médicos:</label>
                                <textarea class="form-control" name="antecedentes" id="antecedentes-m"></textarea>
                            </div>
                            <div class="col">
                                <label for="alergias">Alergias</label>
                                <textarea class="form-control" name="alergias" id="alergias"></textarea>
                            </div>
                        </div>
                        <div class="form-row mt-1">
                            <div class="col">
                                <label for="medicamentos">Habitos</label>
                                <textarea class="form-control" name="medicamentos" id="medicamentos"></textarea>
                            </div>
                            <div class="col">
                                <label for="habitos">Hábitos</label>
                                <textarea class="form-control" name="habitos" id="habitos"></textarea>
                            </div>
                        </div>
                        <div class="form-row mt-1">
                            <div class="col">
                                <label for="antecedentes-f">Antecedentes familiares</label>
                                <textarea class="form-control" name="antecedentes-f" id="antecedentes-f"></textarea>
                            </div>
                            <div class="col">
                                <label for="otros">Otros</label>
                                <textarea class="form-control" name="otros" id="otros"></textarea>
                            </div>
                        </div>
                        <div class="form-row mt-2">
                            <div class="col">
                                <label for="peso">Peso (kgs):</label>
                                <input id="peso" class="form-control" type="text">
                            </div>
                            <div class="col">
                                <label for="altura">Altura (cms):</label>
                                <input id="altura" class="form-control" type="text">
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
                    <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
                        <div class="row">
                            <div class="col-4">
                                <div class="list-group" id="list-tab" role="tablist">
                                    <a class="list-group-item list-group-item-action active" id="list-home-list"
                                       data-toggle="list" href="#list-home" role="tab" aria-controls="home">Cita el
                                                                                                            19-07-2017</a>
                                    <a class="list-group-item list-group-item-action" id="list-profile-list"
                                       data-toggle="list" href="#list-profile" role="tab" aria-controls="profile">Cita
                                                                                                                  el
                                                                                                                  19-07-2018</a>
                                    <a class="list-group-item list-group-item-action" id="list-messages-list"
                                       data-toggle="list" href="#list-messages" role="tab" aria-controls="messages">Cita
                                                                                                                    el
                                                                                                                    19-07-2020</a>
                                    <a class="list-group-item list-group-item-action" id="list-settings-list"
                                       data-toggle="list" href="#list-settings" role="tab" aria-controls="settings">Cita
                                                                                                                    el
                                                                                                                    19-09-2020</a>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="tab-content" id="nav-tabContent">
                                    <div class="tab-pane fade show active" id="list-home" role="tabpanel"
                                         aria-labelledby="list-home-list"><p>Lorem ipsum dolor sit amet, consectetur
                                                                             adipisicing elit. Ab adipisci architecto
                                                                             asperiores assumenda beatae
                                                                             consequuntur ducimus fugiat hic ipsum iste
                                                                             itaque minima necessitatibus
                                                                             nulla
                                                                             omnis reprehenderit, repudiandae sapiente
                                                                             vero voluptate.</p>
                                    </div>
                                    <div class="tab-pane fade" id="list-profile" role="tabpanel"
                                         aria-labelledby="list-profile-list">
                                        <p>
                                            Accusamus, commodi doloremque ducimus
                                            earum eligendi nam odit possimus provident
                                            quis sapiente sed sunt tempore velit veniam vitae! Deserunt facilis fuga
                                            fugit
                                            laborum perferendis reprehenderit repudiandae tempore veritatis, vitae
                                            voluptatibus?
                                        </p>
                                    </div>
                                    <div class="tab-pane fade" id="list-messages" role="tabpanel"
                                         aria-labelledby="list-messages-list">
                                        <p>Accusamus, commodi doloremque ducimus
                                           earum eligendi nam odit possimus
                                           provident
                                           quis sapiente sed sunt tempore velit
                                           veniam vitae! Deserunt facilis fuga
                                           fugit
                                           laborum perferendis reprehenderit
                                           repudiandae tempore veritatis, vitae
                                           voluptatibus?
                                        </p>
                                    </div>
                                    <div class="tab-pane fade" id="list-settings" role="tabpanel"
                                         aria-labelledby="list-settings-list">
                                        <p>Accusamus, commodi doloremque ducimus
                                           earum eligendi nam odit possimus
                                           provident quis sapiente sed sunt tempore velit
                                           veniam vitae! Deserunt facilis fuga fugit
                                           laborum perferendis reprehenderit
                                           repudiandae tempore veritatis, vitae
                                           voluptatibus?
                                        </p></div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!--Final de citas medicas-->
                    <div class="tab-pane fade" id="nav-odontograma" role="tabpanel"
                         aria-labelledby="nav-odontograma-tab">
                        @include('pacientes.__odontogram');
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {

            // // Boton toggler editar
            // $('#editar').click(function () {
            //     if ($('.form-control-plaintext').hasClass('form-control-plaintext')) {
            //         $('#editar').html('Guardar');
            //         $('.form-control-plaintext').removeAttr('readonly').addClass('form-control').removeClass('form-control-plaintext');
            //     } else {
            //         $('#editar').html('Editar');
            //         $('.form-control').attr('readonly', true).addClass('form-control-plaintext').removeClass('form-control');
            //     }
            // })

            //START THE ODONTOGRAM
            const odontogram = new Odontogram();

            $.ajax({
                type: "GET",
                url: "{{ asset('./assets/scripts/odontograma/procedures.json') }}", // Get all procedures
                success: function (initialProcedures) {
                    odontogram.procedures = initialProcedures;
                    odontogram.config = <?= json_encode($mysql_result); ?>;

                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert("Status: " + textStatus);
                    alert("Error: " + errorThrown);
                }
            });
        });


    </script>

@endsection

@section('odontograma')
    <!--Odontograma-->
        <link href="{{ asset('css/odontogram.css') }}" rel="stylesheet"/>
        <script type="text/javascript" src="{{ asset('assets/scripts/odontograma/vendor.bundle.base.js') }}"></script>
        <!--chard.js-->
        <script type="text/javascript" src="{{ asset('assets/scripts/odontograma/odontogram.js') }}"></script>
@endsection
