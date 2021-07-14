@extends('layouts.app')

@section('content')
    <div class="container -body-block pb-5">
        <div class="card">
            <div class="card-header">
                Restaurar la base de datos
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label for="restore">Selecciona el archivo que contiene el respaldo de la base de datos.</label>
                    <input type="file" id="restore" class="form-control-file">

                    <div class="text-center mt-5">
                        <input type="submit" class="btn btn-success" value="Restaurar" onclick="return confirm('¿Estas totalmente seguro que deseas restaurar la base de datos a una version anterior?')"
                        >
                    </div>
                </div>


            </div>
        </div>
    </div>
@endsection
