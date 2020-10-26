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
            <div class="card-header">
                <div>
                    Paciente: {{$paciente->name." ". $paciente->second_name." ". $paciente->last_name." ".$paciente->second_last_name}}</div>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-end flex-column">
                    <a class="mt-auto btn btn-primary btn-sm" href="{{ route('pacientes.edit',$paciente->id) }}">Editar
                                                                                                                 paciente</a>
                </div>
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
                            <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Illo ad fugiat eligendi nulla
                               fugit aliquid ipsam consectetur minus. Ut suscipit totam natus libero perferendis nulla
                               eos fugiat facilis consequatur dolorum.</p>
                        </div>
                        <div class="form-row mt-1">
                            <div class="col">
                                <label for="antecedentes-m">Antecedentes Médicos:</label>
                                <?php
                                $separada = explode(',', 'Precion arterial,Diabetes,Cancer de piel');
                                ?>
                                <ul>
                                    @foreach($separada as $item)
                                        <li>{{$item}}</li>
                                    @endforeach
                                </ul>


                            </div>
                            <div class="col">
                                <label for="alergias">Alergias:</label>
                                <ul>
                                    @if(true)
                                        {{'Ninguna'}}
                                    @else
                                        {{--aqui va un foreach--}}
                                        <li>{{'Alguna'}}</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                        <div class="form-row mt-1">
                            <div class="col">
                                <label>Habitos:</label>
                                <ul>
                                    <li>Lorem ipsum dolor.</li>
                                    <li>Itaque, sequi, vero.</li>
                                    <li>Impedit, mollitia possimus.</li>
                                    <li>Id, provident, similique.</li>
                                </ul>
                            </div>
                            <div class="col">
                                <label for="habitos">Hábitos:</label>
                                <ul>
                                    <li>Lorem ipsum dolor sit amet.</li>
                                    <li>Cupiditate nam quam quibusdam temporibus?</li>
                                    <li>Assumenda aut eos iure sit!</li>
                                </ul>
                            </div>
                        </div>
                        <div class="form-row mt-1">
                            <div class="col">
                                <label for="antecedentes-f">Antecedentes familiares:</label>
                                <ul>
                                    <li>Lorem ipsum dolor sit.</li>
                                    <li>Eveniet impedit officiis totam.</li>
                                    <li>A aut ducimus mollitia.</li>
                                </ul>
                            </div>
                            <div class="col">
                                <label for="otros">Otros:</label>
                                <ul>
                                    <li>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</li>
                                    <li>Ducimus earum hic, incidunt iste maxime perferendis veniam!</li>
                                    <li>Blanditiis debitis dicta ex praesentium soluta tempore voluptate!</li>
                                </ul>
                            </div>
                        </div>
                        <div class="form-row mt-2">
                            <div class="col">
                                <label for="peso">Peso (kgs):</label>
                                <p id="peso">80Kg</p>
                            </div>
                            <div class="col">
                                <label for="altura">Altura (cms):</label>
                                <input id="altura" class="form-control" type="text" readonly>
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
                                         aria-labelledby="list-home-list">primera cita
                                    </div>
                                    <div class="tab-pane fade" id="list-profile" role="tabpanel"
                                         aria-labelledby="list-profile-list">segunda cita
                                    </div>
                                    <div class="tab-pane fade" id="list-messages" role="tabpanel"
                                         aria-labelledby="list-messages-list">...
                                    </div>
                                    <div class="tab-pane fade" id="list-settings" role="tabpanel"
                                         aria-labelledby="list-settings-list">...
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!--Final de citas medicas-->
                    <div class="tab-pane fade" id="nav-odontograma" role="tabpanel"
                         aria-labelledby="nav-odontograma-tab">
                        <div class="container-scroller">
                            <div class="col-12">
                                <div class="content-wrapper">
                                    <div class="row">
                                        <div class="text-center w-100">
                                            <div class="text-right mr-3">
                                                <div class="form-check form-check-info ">
                                                    <label class="form-check-label">
                                                        <input type="checkbox" class="form-check-input primary-toggle">
                                                        Dentición Decidua
                                                        <i class="input-helper"></i></label>
                                                </div>
                                            </div>
                                            @include('pacientes.__odontogram')
                                        </div>

                                        <div class="col-12 mb-3">
                                            <div class="card-header">
                                                <h2> Descripcion del Odontograma</h2>
                                            </div>
                                            <div class="card-body">
                                                <div>
                                                    <label for="lista">Procedimientos:</label>
                                                    <div id="lista">
                                                    </div>
                                                    <textarea hidden name="procedures" id="procedures"
                                                              class="form-control full-textarea" rows="10">[]</textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="comentario">Comentario:</label>
                                                    <p id="comentario">Lorem ipsum dolor sit amet consectetur,
                                                                       adipisicing elit. Hic nisi aut quod similique
                                                                       incidunt veniam
                                                                       illo soluta perferendis magnam, aspernatur minima
                                                                       quibusdam?
                                                                       Iure delectus minus ipsum dignissimos sint vitae
                                                                       ullam.</p>
                                                </div>
                                            </div>


                                        </div>

                                    </div>
                                    <!-- ./row -->
                                </div>
                                <!-- content-wrapper ends -->

                            </div>


                            <div id="offcanvas-bg"></div>
                            <div id="offcanvas-menu">
                                <div class="close-panel"><i class="mdi mdi-close"></i></div>
                                <header class="over-top">
                                    PROCEDIMIENTOS
                                </header>
                                <div class="row scrollable">
                                    <div class="col-lg-6 px-1">
                                        <header>
                                            <small class="float-right text-muted">* La tecla borrar limpia todos los
                                                                                  procedimientos del diente
                                                                                  seleccionado</small>
                                            <h5 class="text-danger">Hallazgos por realizar</h5>
                                        </header>
                                        <div class="scrollable" id="procedures_pending">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="list-group">
                                                        <a href="#" data-action="97"
                                                           class="list-group-item list-group-item-action">
                                                            <b>a.</b> Ángulo distal o mesial
                                                            <span class="float-right">
										<i class="mdi mdi-chevron-right icon-md"></i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="98"
                                                           class="list-group-item list-group-item-action">
                                                            <b>b.</b> Obturación en mal estado
                                                            <span class="float-right">
										<span class="tooth demo-tooth">
											<span class="side side_center pro_sealing"></span>
										</span>
									</span>
                                                        </a>
                                                        <a href="#" data-action="99"
                                                           class="list-group-item list-group-item-action">
                                                            <b>c.</b> Superficie Cariada
                                                            <span class="float-right">
										<i class="mdi mdi-checkbox-blank-circle icon-md"></i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="100"
                                                           class="list-group-item list-group-item-action">
                                                            <b>d.</b> Endodoncia
                                                            <span class="float-right">
										<i class="mdi mdi-arrow-up icon-md"></i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="101"
                                                           class="list-group-item list-group-item-action">
                                                            <b>e.</b> Exodoncia
                                                            <span class="float-right">
										<i class="mdi mdi-close icon-md"></i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="113"
                                                           class="list-group-item list-group-item-action">
                                                            <b>q.</b> Exodoncia Quirúrgica
                                                            <span class="float-right">
										<i>Qx</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="102"
                                                           class="list-group-item list-group-item-action">
                                                            <b>f.</b> Línea de Fractura
                                                            <span class="float-right">
										<span class="tooth demo-tooth">
											<span class="side side_center pro_fracture"></span>
										</span>
									</span>
                                                        </a>
                                                        <a href="#" data-action="111"
                                                           class="list-group-item list-group-item-action">
                                                            <b>o.</b> Corona
                                                            <span class="float-right">
										<i class="mdi mdi-checkbox-blank-circle-outline icon-md"></i>
									</span>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="list-group">
                                                        <a href="#" data-action="112"
                                                           class="list-group-item list-group-item-action">
                                                            <b>p.</b> Pulido
                                                            <span class="float-right">
										<i>P &nbsp;</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="114"
                                                           class="list-group-item list-group-item-action">
                                                            <b>r.</b> Resina preventiva
                                                            <span class="float-right">
										<i>RPR</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="115"
                                                           class="list-group-item list-group-item-action">
                                                            <b>s.</b> Sellante
                                                            <span class="float-right">
										<i>S &nbsp;</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="118"
                                                           class="list-group-item list-group-item-action">
                                                            <b>v.</b> Restauración Cervical
                                                            <span class="float-right">
										<span class="tooth demo-tooth">
											<span class="side side_bottom pro_restoration"
                                                  style="border-top: 0 solid transparent; border-left: 0 solid transparent; border-radius: 50%; border-width: 2px;"></span>
										</span>
									</span>
                                                        </a>
                                                        <a href="#" data-action="120"
                                                           class="list-group-item list-group-item-action">
                                                            <b>x.</b> Radiografía
                                                            <span class="float-right">
										<i>Rx</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="49"
                                                           class="list-group-item list-group-item-action">
                                                            <b>1.</b> Movilidad Grado I
                                                            <span class="float-right">
										<i style="font-family: 'Times New Roman'">I &nbsp;</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="50"
                                                           class="list-group-item list-group-item-action">
                                                            <b>2.</b> Movilidad Grado II
                                                            <span class="float-right">
										<i style="font-family: 'Times New Roman'">II &nbsp;</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="51"
                                                           class="list-group-item list-group-item-action">
                                                            <b>3.</b> Movilidad Grado III
                                                            <span class="float-right">
										<i style="font-family: 'Times New Roman'">III &nbsp;</i>
									</span>
                                                        </a>

                                                    </div>
                                                </div>
                                            </div>
                                        </div><!-- ./scrollable -->
                                    </div>
                                    <div class="col-lg-6 px-1">
                                        <header>
                                            <h5 class="text-success">Hallazgos completados</h5>
                                        </header>
                                        <div class="scrollable" id="procedures_done">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="list-group">
                                                        <a href="#" data-action="65"
                                                           class="list-group-item list-group-item-action">
                                                            <b>A.</b> Ángulo distal o mesial
                                                            <span class="float-right">
										<i class="mdi mdi-chevron-right icon-md"></i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="66"
                                                           class="list-group-item list-group-item-action">
                                                            <b>B.</b> Obturación en Buen estado
                                                            <span class="float-right">
										<span class="tooth demo-tooth">
											<span class="side side_center pro_sealing_done"></span>
										</span>
									</span>
                                                        </a>
                                                        <a href="#" data-action="67"
                                                           class="list-group-item list-group-item-action">
                                                            <b>C.</b> Tratamiento Realizado
                                                            <span class="float-right">
										<i class="mdi mdi-checkbox-blank-circle icon-md"></i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="68"
                                                           class="list-group-item list-group-item-action">
                                                            <b>D.</b> Endodoncia Realizada
                                                            <span class="float-right">
										<i class="mdi mdi-arrow-up icon-md"></i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="69"
                                                           class="list-group-item list-group-item-action">
                                                            <b>E.</b> Exodoncia Realizada
                                                            <span class="float-right">
										<i class="mdi mdi-close icon-md"></i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="77"
                                                           class="list-group-item list-group-item-action">
                                                            <b>M.</b> Diente sin Erupcionar
                                                            <span class="float-right">
										<i>| &nbsp;</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="79"
                                                           class="list-group-item list-group-item-action">
                                                            <b>O.</b> Corona Buena
                                                            <span class="float-right">
										<i class="mdi mdi-checkbox-blank-circle-outline icon-md"></i>
									</span>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="list-group">
                                                        <a href="#" data-action="80"
                                                           class="list-group-item list-group-item-action">
                                                            <b>P.</b> Pulido Realizado
                                                            <span class="float-right">
										<i>P&nbsp;</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="82"
                                                           class="list-group-item list-group-item-action">
                                                            <b>R.</b> Resina Preventiva Buena
                                                            <span class="float-right">
										<i>RPR</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="83"
                                                           class="list-group-item list-group-item-action">
                                                            <b>S.</b> Sellante Bueno
                                                            <span class="float-right">
										<i>S &nbsp;</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="86"
                                                           class="list-group-item list-group-item-action">
                                                            <b>V.</b> Restauración Cervical Buena
                                                            <span class="float-right">
										<span class="tooth demo-tooth">
											<span class="side side_bottom pro_restoration_done"
                                                  style="border-top: 0px solid transparent; border-left: 0px solid transparent; border-radius: 50%; border-width: 2px;"></span>
										</span>
									</span>
                                                        </a>
                                                        <a href="#" data-action="88"
                                                           class="list-group-item list-group-item-action">
                                                            <b>X.</b> Radiografía Realizada
                                                            <span class="float-right">
										<i>Rx</i>
									</span>
                                                        </a>
                                                        <a href="#" data-action="90"
                                                           class="list-group-item list-group-item-action">
                                                            <b>Z.</b> Diente Extraído
                                                            <span class="float-right">
										<i>----</i>
									</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> <!-- ./scrollable -->
                                    </div>
                                </div>

                            </div>
                            <!-- ./offcanvas-menu -->


                        </div>
                        <!--Final de odontograma-->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">

        $(':input').prop('disabled', 'disabled');
        $(':input').addClass('form-control-plaintext', true);
        $('select').prop('disabled', 'disabled');

        $(document).ready(function () {
            console.log('documento cargado')

            //START THE ODONTOGRAM
            const odontogram = new Odontogram();

            $.ajax({
                type: "GET",
                url: "{{ asset('./assets/scripts/odontograma/procedures.json') }}", // All procedures
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

