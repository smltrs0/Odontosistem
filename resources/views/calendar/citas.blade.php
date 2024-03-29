<?php
    $admin = false;
    foreach (Auth::user()->roles as $rol) {
        if ($rol->slug == "admin") $admin = true;
    }
?>
@extends('layouts.app')
@section('content')

    <div class="mb-3">
        <div class="card">
            <div class="card-header">Mis citas</div>
            <div class="card-body">
                @if(!$existe)
                   <div class="alert alert-warning text-center">
                       <p>Antes de crear una cita, es necesario que completes los datos de tu perfil</p>
                       <a href="{{route('mi-cuenta.index')}}" class="btn btn-primary">Agregar datos de mi cuenta</a>
                   </div>
                @else
                @if($citas->count() == 0)
                    <div class="alert alert-warning text-center">
                        No tienes ninguna cita.
                    </div>
                @endif
                <ul class="list-group">
                    @foreach($citas ?? '' as $elemento)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Fecha :{{$elemento->fecha}} Hora {{$elemento->hora}}
                            <div>
                                <a href="{{ route('citas.edit',$elemento->id)}}"
                                   class="badge badge-primary badge-pill mb-1" title="Editar cita">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form action="{{ route('citas.destroy', $elemento->id)}}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button class="badge badge-danger badge-pill"
                                            onclick="return confirm('Estas seguro de que deceas eliminar esta cita?')"
                                            type="submit" title="Eliminar cita">
                                        <i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>

            </div>

            <!-- Modal -->
            <div class="modal fade" id="ModalCitas" role="dialog" aria-labelledby="ModalCitaLabel">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="ModalCitaLabel">Crear cita</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{route('citas.store')}}" method="POST" id="elForm">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="cita-fecha">Fecha</label>
                                    <input id="cita-fecha" class="form-control" min="" type="date" name="fecha" value="" required>
                                </div>
                                <div class="form-group">
                                    <label for="cita-hora">Hora</label>
                                    <input class="form-control" type="time" name="hora" id="cita-hora" required>
                                </div>
                                @if ($admin)
                                    <div class="form-group">
                                        <label for="cita-hora">Usuario</label>
                                        <select class="form-control" name="usuario" required>
                                            <option value="" disabled selected>-Seleccione-</option> 
                                            @foreach($usuarios as $usuario)
                                                <option value="{{$usuario->id}}">{{$usuario->name}}</option> 
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="submit" id="elSubmit" class="btn btn-primary">Crear</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-1">
            <!-- Boton disparador del modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalCitas">
                Crear cita
            </button>
        </div>
        @endif
    </div>

    <script>
        var fecha = new Date();
        var anio = fecha.getFullYear();
        var dia = fecha.getDate();
        var _mes = fecha.getMonth(); //viene con valores de 0 al 11
        _mes = _mes + 1; //ahora lo tienes de 1 al 12
        if (_mes < 10) //ahora le agregas un 0 para el formato date
        {
            var mes = "0" + _mes;
        } else {
            var mes = _mes.toString;
        }
        document.getElementById("cita").min = anio + '-' + mes + '-' + dia;


    </script>

@endsection

