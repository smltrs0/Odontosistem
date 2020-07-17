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
                                <label for="">Altura (cms):</label>
                                <input class="form-control" type="text" readonly>
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
                                        el 19-07-2018</a>
                                    <a class="list-group-item list-group-item-action" id="list-messages-list"
                                       data-toggle="list" href="#list-messages" role="tab" aria-controls="messages">Cita
                                        el 19-07-2020</a>
                                    <a class="list-group-item list-group-item-action" id="list-settings-list"
                                       data-toggle="list" href="#list-settings" role="tab" aria-controls="settings">Cita
                                        el 19-09-2020</a>
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

                                            <div class="card-body">
                                                <div id="odontogram">
                                                    <div class="clearfix">
                                                        <h5 class="label">Vestibular</h5>
                                                    </div>
                                                    <div class="cuadrant">
                                                        <div class="tooth" id="tooth_18" data-id="18">
                                                            <label>18</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_17" data-id="17">
                                                            <label>17</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_16" data-id="16">
                                                            <label>16</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_15" data-id="15">
                                                            <label>15</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_14" data-id="14">
                                                            <label>14</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_13" data-id="13">
                                                            <label>13</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_12" data-id="12">
                                                            <label>12</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_11" data-id="11">
                                                            <label>11</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="primary text-right">
                                                            <div class="tooth" id="tooth_55" data-id="55">
                                                                <label>55</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_54" data-id="54">
                                                                <label>54</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_53" data-id="53">
                                                                <label>53</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_52" data-id="52">
                                                                <label>52</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_51" data-id="51">
                                                                <label>51</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="cuadrant">
                                                        <div class="tooth" id="tooth_21" data-id="21">
                                                            <label>21</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_22" data-id="22">
                                                            <label>22</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_23" data-id="23">
                                                            <label>23</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_24" data-id="24">
                                                            <label>24</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_25" data-id="25">
                                                            <label>25</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_26" data-id="26">
                                                            <label>26</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_27" data-id="27">
                                                            <label>27</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_28" data-id="28">
                                                            <label>28</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="primary text-left">
                                                            <div class="tooth" id="tooth_61" data-id="61">
                                                                <label>61</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_62" data-id="62">
                                                                <label>62</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_63" data-id="63">
                                                                <label>63</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_64" data-id="64">
                                                                <label>64</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_65" data-id="65">
                                                                <label>65</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="clearfix">
                                                        <h5 class="label">
                                                            <span class="right-label">Derecho</span>
                                                            <span class="center-label">Lingual</span>
                                                            <span class="left-label">Izquierdo</span>
                                                        </h5>
                                                    </div>
                                                    <div class="cuadrant">
                                                        <div class="primary text-right">
                                                            <div class="tooth" id="tooth_85" data-id="85">
                                                                <label>85</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_84" data-id="84">
                                                                <label>84</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_83" data-id="83">
                                                                <label>83</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_82" data-id="82">
                                                                <label>82</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_81" data-id="81">
                                                                <label>81</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_48" data-id="48">
                                                            <label>48</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_47" data-id="47">
                                                            <label>47</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_46" data-id="46">
                                                            <label>46</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_45" data-id="45">
                                                            <label>45</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_44" data-id="44">
                                                            <label>44</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_43" data-id="43">
                                                            <label>43</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_42" data-id="42">
                                                            <label>42</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_41" data-id="41">
                                                            <label>41</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="cuadrant">
                                                        <div class="primary text-left">
                                                            <div class="tooth" id="tooth_71" data-id="71">
                                                                <label>71</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_72" data-id="72">
                                                                <label>72</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_73" data-id="73">
                                                                <label>73</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_74" data-id="74">
                                                                <label>74</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                            <div class="tooth" id="tooth_75" data-id="75">
                                                                <label>75</label>
                                                                <div class="tooth-group">
                                                                    <div class="side side_top" data-side="top"></div>
                                                                    <div class="side side_left" data-side="left"></div>
                                                                    <div class="side side_center"
                                                                         data-side="center"></div>
                                                                    <div class="side side_right"
                                                                         data-side="right"></div>
                                                                    <div class="side side_bottom"
                                                                         data-side="bottom"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_31" data-id="31">
                                                            <label>31</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_32" data-id="32">
                                                            <label>32</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_33" data-id="33">
                                                            <label>33</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_34" data-id="34">
                                                            <label>34</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_35" data-id="35">
                                                            <label>35</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_36" data-id="36">
                                                            <label>36</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_37" data-id="37">
                                                            <label>37</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                        <div class="tooth" id="tooth_38" data-id="38">
                                                            <label>38</label>
                                                            <div class="tooth-group">
                                                                <div class="side side_top" data-side="top"></div>
                                                                <div class="side side_left" data-side="left"></div>
                                                                <div class="side side_center" data-side="center"></div>
                                                                <div class="side side_right" data-side="right"></div>
                                                                <div class="side side_bottom" data-side="bottom"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="clearfix">
                                                        <div class="label">
                                                            <h5>Vestibular</h5>
                                                        </div>
                                                    </div>

                                                </div>
                                                <!-- ./Odontogram -->
                                            </div>
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
                                                        adipisicing elit. Hic nisi aut quod similique incidunt veniam
                                                        illo soluta perferendis magnam, aspernatur minima quibusdam?
                                                        Iure delectus minus ipsum dignissimos sint vitae ullam.</p>
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
                                                procedimientos del diente seleccionado</small>
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

@section('odontograma')
    <!--Odontograma-->
        <link href="{{ asset('css/odontogram.css') }}" rel="stylesheet"/>
        <script type="text/javascript" src="{{ asset('assets/scripts/odontograma/vendor.bundle.base.js') }}"></script>
        <script type="text/javascript" src="{{ asset('assets/scripts/odontograma/odontogram.js') }}"></script>
</div>@endsection
