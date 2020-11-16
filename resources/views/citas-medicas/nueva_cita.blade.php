@extends('layouts.app')
@section('content')
    <div class="">
        <div class="card">
            <div class="card-header">Nueva cita medicas
            </div>
            
            <div class="card-body">
                <div class="tab-pane fade mt-3 " id="nav-contact" role="tabpanel"
                             aria-labelledby="nav-contact-tab">
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
                                                <input type="button" class="btn btn-sm btn-primary" value="Imprimir">
                                                <button class="btn btn-sm btn-secondary">Enviar por correo</button>
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

@endsection

