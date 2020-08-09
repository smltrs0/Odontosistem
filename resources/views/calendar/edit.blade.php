@extends('layouts.app')
@section('content')
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Editar cita</div>
            <div class="card-body">
                <form action="{{ route('citas.update', $citas->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="CitaCreada">Fecha creacion</label>
                        <p id="CitaCreada">{{$citas->created_at}}</p>
                    </div>
                    <div class="form-group">
                        <label for="FechaActualizada">Ultima actualizacion</label>
                        <p id="FechaActualizada">{{$citas->updated_at}}</p>
                    </div>
                    <div class="form-group">
                        <label for="ActualizarCita">Selecciona la nueva fecha para la cita</label>
                        <input id="ActualizarCita" class="form-control" name="fecha" type="date" min=""
                               value="{{$citas->fecha}}">
                    </div>
                    <input type="submit" class="btn btn-primary" value="Actualizar">
                </form>
            </div>
        </div>
    </div>

    <script>
        var fecha = new Date();
        var anio = fecha.getFullYear();
        var dia = fecha.getDate();
        var _mes = fecha.getMonth();//viene con valores de 0 al 11
        _mes = _mes + 1;//ahora lo tienes de 1 al 12
        if (_mes < 10)//ahora le agregas un 0 para el formato date
        { var mes = "0" + _mes;}
        else
        { var mes = _mes.toString;}
        document.getElementById("ActualizarCita").min = anio+'-'+mes+'-'+dia;
    </script>

@endsection



