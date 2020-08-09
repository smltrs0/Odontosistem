@extends('layouts.app')
@section('content')

<div class="mb-3">
    <div class="card">
        <div class="card-header">Mis citas</div>
        <div class="card-body">
           @if(!isset(auth()->user()->pacientes->name))
               primero debes completar los datos
            @elseif($citas->count() == 0)
            <div class="alert alert-warning text-center">
                citas.blade.php

                No tienes ninguna cita.
            </div>
            @endif
            <ul class="list-group">
                @foreach($citas ?? '' as $elemento)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    {{$elemento->fecha}}
                    <div>
                        <a href="{{ route('citas.edit',$elemento->id)}}" class="badge badge-primary badge-pill mb-1" title="Editar cita">
                            <i class="fa fa-edit"></i>
                        </a>
                        <form action="{{ route('citas.destroy', $elemento->id)}}" method="post">
                            @csrf
                            @method('DELETE')
                            <button class="badge badge-danger badge-pill" onclick="return confirm('Estas seguro de que deceas eliminar esta cita?')" type="submit"title="Eliminar cita">
                                <i class="fa fa-trash"></i></button>
                          </form>
                    </div>
                </li>
                @endforeach
            </ul>

        </div>

        <!-- Modal -->
        <div class="modal fade" id="ModalCitas" role="dialog" aria-labelledby="ModalCitaLabel" data-backdrop="false">
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
                                <input id="cita"  class="form-control" min="" type="date" name="fecha" value="">
                            </div>
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

