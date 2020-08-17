@extends('layouts.app')

@section('content')
<div class="row ">
    <div class="col-md-12">
        <div class="card mb-1">
            <div class="card-header">Agregar nuevo paciente</div>

            <div class="card-body">
                <form action="{{route('pacientes.store')}}" method="POST">
                    @csrf
                    <input type="text" name="registered_by" value="{{Auth::user()->id}}" hidden>
                    @include('pacientes.__formulario')
                    <button class="btn btn-primary" name="crear-paciente">Cear nuevo paciente</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
