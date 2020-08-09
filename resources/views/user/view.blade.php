@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><h2>Ver datos del usuario</h2></div>

                    <div class="card-body">
                        @include('custom.message')

                        <div class="container">
                            <div class="form-group">
                                <div>
                                    <label for="name">Nombre</label>
                                    <p id="name">{{ old('name', $user->name)}}</p>
                                </div>
                                <div>
                                    <label for="email">Correo electronico</label>
                                    <p id="email">{{ old('email' , $user->email)}}</p>
                                </div>

                            <div>
                                <label for="roles">Roles:</label>
                                <ul id="roles">
                                    @foreach($roles as $role)
                                        <li value="{{ $role->id }}"
                                                @isset($user->roles[0]->name)
                                                @if($role->name ==  $user->roles[0]->name)
                                                selected
                                            @endif
                                            @endisset

                                        >{{ $role->name }}</li>
                                    @endforeach
                                </ul>
                            </div>


                            <hr>

                            <a class="btn btn-success" href="{{route('user.edit',$user->id)}}">Editar</a>
                            <a class="btn btn-danger" href="{{route('user.index')}}">Regresar</a>


                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
