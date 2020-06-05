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
                <table class="table table-bordered">
                    <tr>
                        <th width="30">No</th>
                        <th>{{__('Full name')}}</th>
                        <th>{{__('Phone')}}</th>
                        <th>{{__('Address')}}</th>
                        <th width="210px">Action</th>
                    </tr>
                    @foreach ($pacientes ?? '' as $paciente)
                    <tr>
                        <td>{{ ++$i }}</td>
                        <td><a href="{{route('pacientes.show',$paciente->id) }}">{{ $paciente->name." ".$paciente->secont_name." ".$paciente->last_name." ".$paciente->second_last_name }}</a>
                        </td>
                        <td>{{ $paciente->phone }}</td>
                        <td>{{$paciente->address}}</td>
                        <td>
                            <form action="{{ route('pacientes.destroy',$paciente->id) }}" method="POST">

                                <a class="btn btn-info"
                                    href="{{ route('pacientes.show',$paciente->id) }}">{{__('Show')}}</a>

                                <a class="btn btn-primary"
                                    href="{{ route('pacientes.edit',$paciente->id) }}">{{__('Edit')}}</a>

                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-danger">{{__('Delete')}}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </table>

                {!! $pacientes ?? ''->links() !!}
            </div>
        </div>
    </div>
</div>

@endsection