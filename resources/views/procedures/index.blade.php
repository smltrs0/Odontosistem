@extends('layouts.app')
@section('content')

    <div class="mb-3">
        <div class="card">
            <div class="card-header">Procedimientos</div>
            <div class="card-body">
                <a href="{{ route('procedures.create') }}" class="btn btn-primary mb-2">Crear procedimiento</a>
               
                @if(false)
                    <div class="alert alert-warning text-center">
                        No tienes ningun procedimiento registrado.
                    </div>
                @endif
                
           
                
                <table class="table table-hover table-sm">
                    <tr>
                        <td>Code</td>
                        <td>Costo</td>
                        <td>Nombre</td>
                        <td>type</td>
                        <td width="">Acción</td>
                    </tr>
                    @foreach ($procedimientos as $procedure)
                    <tr>
                        <td>{{  $procedure->code }}</td>
                        <td>{{  $procedure->price }}</td>
                        <td>{{  $procedure->title }}</td>
                        <td>{{  $procedure->type }}</td>
                        <td> <a href="{{ route('procedures.edit',$procedure->id) }}" class="btn btn-warning btn-sm text-white">
                            <span class="fa fa-edit"></span></a> 
                            {{-- <a href="{{ route('procedures.destroy',$procedure->id) }}" class="btn btn-sm btn-danger"><span class="fa fa-trash-alt"></span></a> --}}
                            <form method="POST" action="{{ route('procedures.destroy', $procedure->id) }}">
                               @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-sm btn-danger"><span class="fa fa-trash-alt"></span></button>
                            </form>
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

