@extends('layouts.app')

@section('content')
    <div class="">
        <div class="card">
            <div class="card-header">Citas medicas del paciente
                <div class="float-right">
                    <button class="btn-shadow btn btn-outline-success"><i class="fa fa-file-medical"></i> Registrar evaluacion del paciente</button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="">
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
                                             aria-labelledby="list-home-list">
                                            <button type="button" class="btn
                                                 btn-primary" data-toggle="modal" data-target="#evaluacionModal">
                                                Realizar evaluacion
                                            </button>
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
                                            </p>
                                            <div class="">
                                                <button class="btn btn-primary btn-sm">Modificar evaluacion</button>
                                                <input type="button" class="btn btn-sm btn-primary" value="Generar Factura">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!--Final de citas medicas-->
            </div>
            
        </div>
    </div>
        <!-- Modal -->
        <div class="modal fade" id="evaluacionModal" role="dialog" >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ModalCitaLabel">Evaluacion del paciente</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-md-6 col-sm-12">
                                <label for="evaluacion">Evaluacion</label>
                                <textarea class="form-control" name="evaluacion" id="evaluacion">
                                        </textarea>
                            </div>
                            <div class="form-group col-md-6 col-sm-12">
                                <label for="medicacion">Medicacion</label>
                                <textarea class="form-control" name="medicacion" id="medicacion"></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 col-sm-12">
                                <label for="analisis">Análisis clínico solicitados</label>
                                <textarea class="form-control" name="analisis" id="analisis"></textarea>
                            </div>
                            <div class="form-group col-md-6 col-sm-12">
                                <label for="comentario-paciente">Comentario (Visible para el paciente)</label>
                                <textarea class="form-control" name="comentario-paciente"
                                          id="comentario-paciente"></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="comentario-medico">Comentario (Solo visible para el médico)</label>
                            <textarea class="form-control" name="comentario-medico" id="comentario-medico"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="elSubmit" class="btn btn-primary">Registrar evaluación médica
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!--Final modal Registrar cita medica-->
@endsection

