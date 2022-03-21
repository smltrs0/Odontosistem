@extends('layouts.app')
@section('content')

    <div class="mb-3">
        <div class="card">
            <div class="card-header">Procedimientos</div>
            <div class="card-body">
                <a href="{{ route('procedures.create') }}" class="btn btn-primary mb-2">Crear procedimiento</a>
               
                @if(false)
                    <div class="alert alert-warning text-center">
                        No tienes ningún procedimiento registrado.
                    </div>
                @endif
                
           
                
                <table class="table table-hover table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Costo</th>
                            <th>Nombre</th>
                            <th>Estilo</th>
                            <th width="">Acción</th>
                        </tr>
                    </thead>
                    @foreach ($procedimientos as $index => $procedure)
                    <tr>
                        <td class="text-center">{{ ($index+1) }}</td>
                        <td>{{  $procedure->price }}</td>
                        <td>{{  $procedure->title }}</td>
                        <td>{{  $procedure->type }}</td>
                        <td style="display: flex"> <a href="{{ route('procedures.edit',$procedure->id) }}" class="btn btn-warning btn-sm text-white mr-1">
                            <span class="fa fa-edit"></span></a> 
                            {{-- <a href="{{ route('procedures.destroy',$procedure->id) }}" class="btn btn-sm btn-danger ml-1"><span class="fa fa-trash-alt"></span></a> --}}
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

