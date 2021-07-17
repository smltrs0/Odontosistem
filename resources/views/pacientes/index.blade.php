@extends('layouts.app')
@section('content')
<div class="row ">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Todos los pacientes</div>

            <div class="card-body">
                @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
                @endif
                <table class="table table-bordered show" id="tabla-pacientes">
                    <thead>
                        <tr>
                            <th width="30">No</th>
                            <th>{{__('Full name')}}</th>
                            <th>{{__('Phone')}}</th>
                            <th>{{__('Address')}}</th>
                            <th width="210px">Acción</th>
                        </tr>
                    </thead>
                   <tbody>
                   @foreach ($pacientes ?? '' as $paciente)
                       <tr>
                           <td>{{ ++$i }}</td>
                           <td>
                               <a href="{{route('pacientes.show',$paciente->id) }}">{{ $paciente->name." ".$paciente->secont_name." ".$paciente->last_name." ".$paciente->second_last_name }}</a>
                           </td>
                           <td>{{ $paciente->phone }}</td>
                           <td>{{$paciente->address}}</td>
                           <td>
                               <form action="{{ route('pacientes.destroy',$paciente->id) }}" method="POST">

                                   <a class="btn btn-info" title="Ver datos del paciente" href="{{ route('pacientes.show',$paciente->id) }}"><i class="pe-7s-note2"> </i></a>

                                   <a href="javascript:ventanaSecundaria('{{ url("citas-medicas/{$paciente->id}") }}')" class="btn btn-success" title="Ver citas medicas del paciente">
                                    <i class="pe-7s-news-paper"></i>
                                   </a>

                                   <a class="btn btn-primary" title="Editar datos de este paciente" href="{{ route('pacientes.edit',$paciente->id) }}"><i class="fa fa-edit"></i></a>
                                   @csrf
                                   @method('DELETE')

                                   <button type="submit" title="Eliminar este paciente" onclick="return confirm('Estas seguro de que deceas eliminar este paciente?')" class="btn btn-danger"><i class="pe-7s-trash"></i></button>
                               </form>
                           </td>
                       </tr>
                   @endforeach
                   </tbody>
                </table>
                {!! $pacientes ?? ''->links() !!}
            </div>
        </div>
    </div>
</div>

@endsection
