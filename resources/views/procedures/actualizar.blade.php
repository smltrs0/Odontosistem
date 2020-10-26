@extends('layouts.app')
@section('content')
    <div class="mb-3">
        <div class="card">
            <div class="card-header">Crear nuevo procedimiento</div>
            <div class="card-body">
               <form method="POST" action="{{  route('procedimientos.update') }}">
                @csrf
                @method('PUT')
                   <div class="form-group">
                       <label for="">Nombre</label>
                    <input class="form-control" type="text" name="title" value="{{ $procedimiento->title }}">
                   </div>
                   <div class="form-group">
                       <label for="">Tecla</label>
                    <input class="form-control" type="text" name="key_p" value="{{ $procedimiento->key_p }}">
                   </div>
                   <div class="form-group">
                       <label for="">Codigo</label>
                    <input class="form-control" type="text" name="code" value="code">
                   </div>
                   <div class="form-group">
                           <label for="type">Tipo</label>
                           <select id="type" class="form-control" name="type" value="type">
                               <option value="pendiente">Pendiente</option>
                               <option value="completado">Completado</option>
                           </select>
                   </div>
                   <div class="form-group">
                       <label for="">Estilo a aplicar</label>
                    <input class="form-control" type="text" name="className">
                   </div>
               
             
            <div class="card-footer">
                <a href="#" onclick="history.back()" class="btn btn-danger btn-sm">Cancelar</a>
                <input class="btn btn-success" type="submit">
            </div>
            </form>
           
        </div>
        
    </div>


@endsection

