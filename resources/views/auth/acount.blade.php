@extends('layouts.app')
@section('content')
<div class="card mb-3">
<div class="card-header">Tu perfil de usuario</div>
  <div class="card-body">
    <form action="{{ route('pacientes.update', auth()->user()->id)}}" method="POST">
      @csrf
      @method('PUT')
      <input type="text" name="update" value="true" hidden>
      <div class="row form-group">
        <div class="col">
          <label for="name">Primer nombre</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="{{__('First name')}}"
                value="{{old('name', $paciente->pacientes->name ?? '')}}">
        </div>
        <div class="col">
          <label for="second_name">Segundo nombre</label>
            <input type="text" class="form-control" id="second_name" name="second_name" placeholder="{{__('Second name')}}"
                value="{{old('second_name', $paciente->pacientes->second_name ?? '')}}">
        </div>
        <div class="col">
          <label for="last_name">Primer apellido</label>
            <input type="text" class="form-control" id="last_name" name="last_name" placeholder="{{__('Last name')}}"
                value="{{old('last_name', $paciente->pacientes->last_name ?? '')}}">
        </div>
        <div class="col">
          <label for="second_last_name">Segundo apellido</label>
            <input type="text" class="form-control" name="second_last_name" placeholder="{{__('Second last name')}}"
                value="{{old('second_last_name', $paciente->pacientes->second_last_name ?? '')}}">
        </div>
    </div>
    <div>
      <label>Tipo de documento nacional de identificacion </label>
<div class="row form-group">
    <div class="col-4">
        <select name="dni_type" class="form-control">
            <option value="">Selecciona un tipo de dni</option>
        </select>
    </div>
    <div class="col-8">
        <input class="form-control" id="dni" type="text" name="dni" placeholder="{{__('DNI')}}"
            value="{{old('dni', $paciente->pacientes->dni ?? '')}}">
    </div>
    </div>
</div>
<label for="sex">Sexo</label>
<div class="form-group" id="sex">
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="sex" id="hombre" value="1" required @if($paciente->pacientes->sex == 1)
         checked
        @endif>
        <label class="form-check-label" for="hombre">
          Hombre
        </label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="sex" id="mujer" value="0" @if($paciente->pacientes->sex == 0)
        checked
       @endif required>
        <label class="form-check-label" for="mujer">
         Mujer
        </label>
      </div>
</div>
<label for="birth_date">Fecha de nacimiento</label>
<div class=" form-group">
    <input type="date" class="form-control" id="birth_date" name="birth_date" value="{{old('birth_date', $paciente->pacientes->birth_date ?? '')}}">
</div>
<div class="form-group">
    <label for="inputPhone">Telefono de contacto</label>
    <input class="form-control" type="text" name="phone" id="inputPhone" value="{{old('phone', $paciente->pacientes->phone ?? '')}}">
</div>
<div class="form-group">
    <label for="inputCorreo">Correo electronico</label>
    <input class="form-control" type="email" name="email" id="inputCorreo" value="{{old('email', $paciente->email ?? '')}}">
</div>
<label>Dirección</label>
<div class="form-group">
    <textarea class="form-control" name="address">{{old('address', $paciente->pacientes->address ?? '')}}</textarea>
</div>
      <input class="btn btn-primary btn-block" type="submit" value="Actualizar">
  </form>
  </div>
</div>
@endsection