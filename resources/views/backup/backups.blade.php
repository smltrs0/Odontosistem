@extends('layouts.app')

@section('content')
    <div class="container -body-block pb-5">
        <div class="card">
            <div class="card-header">
                Respaldo de la base de datos
            </div>
            <div class="card-body">
                <a href="{{ url('backup/create') }}" class="btn btn-danger" title="Crear nuevo backup">
                    <i class="fa fa-plus" aria-hidden="true"></i> Crear nuevo respaldo
                </a>

                <div class="py-4"></div>
                @include('backup.backups-table')
                <div class="py-3"></div>

            </div>
        </div>
    </div>
@endsection
