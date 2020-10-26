@extends('layouts.app')
@section('content')

    <div class="mb-3">
        <div class="card">
            <div class="card-header">Procedimientos</div>
            <div class="card-body">
                <a href="{{ route('procedimientos.create') }}" class="btn btn-primary">Crear procedimiento</a>
               
                @if(false)
                    <div class="alert alert-warning text-center">
                        No tienes ningun procedimiento registrado.
                    </div>
                @endif
                
           
                
                <table class="table table-hover table-sm">
                    <tr>
                        <td>id</td>
                        <td>code</td>
                        <td>Nombre</td>
                        <td>type</td>
                        <td width=" 15%">Acción</td>
                    </tr>
                    @foreach ($procedimientos as $procedure)
                    <tr>
                        <td>{{  $procedure->id }}</td>
                        <td>{{  $procedure->code }}</td>
                        <td>{{  $procedure->title }}</td>
                        <td>{{  $procedure->type }}</td>
                        <td> <a href="" class="btn btn-warning btn-sm text-white">
                            <span class="fa fa-edit"></span></a> 
                            <a href="{{ route('procedures.delete',$subject->id) }}" class="btn btn-sm btn-danger"><span class="fa fa-trash-alt"></span></a>
                    </tr>
                    @endforeach
                </table>

            </div>
            <div class="card-footer">
                {{ $procedimientos->links() }}
            </div>

           
        </div>
        
    </div>


@endsection

