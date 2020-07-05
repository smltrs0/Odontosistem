@extends('layouts.app')
@section('content')
<div class="mb-3">
        <div class="card">
            <div class="card-header">Citas para hoy</div>
                <div class="card-body">
                    <div class="alert alert-warning text-center">
                        No tienes ninguna cita para hoy
                    </div>
                    <input type="button" class="btn btn-primary" value="Crear paciente">
                    <button class="btn btn-outline-danger">Seleccionar un paciente</button>
                </div>
        </div>
</div>
@endsection


