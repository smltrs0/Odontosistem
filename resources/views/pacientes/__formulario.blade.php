<div class="mt-4">
    <label>{{__('Full name')}}</label>
    <div class="row form-group">
        <div class="col">
            <input onkeypress="onlyString(event)" type="text" class="form-control" name="name" placeholder="{{__('First name')}}"
                   value="{{old('name', $paciente->name)}}" required>
        </div>
        <div class="col">
            <input onkeypress="onlyString(event)" type="text" class="form-control" name="second_name" placeholder="{{__('Second name')}}"
                   value="{{old('second_name', $paciente->second_name)}}">
        </div>
        <div class="col">
            <input onkeypress="onlyString(event)" type="text" class="form-control" name="last_name" placeholder="{{__('Last name')}}"
                   value="{{old('last_name', $paciente->last_name)}}" required>
        </div>
        <div class="col">
            <input onkeypress="onlyString(event)" type="text" class="form-control" name="second_last_name" placeholder="{{__('Second last name')}}"
                   value="{{old('second_last_name', $paciente->second_last_name)}}">
        </div>
    </div>
    <label> Documento nacional de identificacion</label>
    <div class="row form-group">
        <div class="col-4">
            <select name="dni_type" class="form-control" required>
                <option value="" disabled selected>-Seleccione-</option>
                <option value="1">Cedula</option>
                <option value="2">RIF</option>
                <option value="3">Pasaporte</option>
            </select>
        </div>
        <div class="col-8">
            <input class="form-control numbers-only" onkeypress="onliNumbers(event)" type="text" name="dni" placeholder="{{__('DNI')}}"
                   value="{{old('dni', $paciente->dni)}}" required>
        </div>
    </div>
    <label for="sex">Sexo</label>
    <div class="form-group" id="sex">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="sex" id="hombre" value="1" @if(old('sex', $paciente->sex == 1))
            checked
                   @endif required>
            <label class="form-check-label" for="hombre">
                Hombre
            </label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="sex" id="mujer" value="0" @if(old('sex', $paciente->sex===0))checked @endif required>
            <label class="form-check-label" for="mujer">
                Mujer
            </label>
        </div>
    </div>
    <label for="birth_date">Fecha de nacimiento</label>
    <div class="form-group">
        <input type="date" class="form-control" id="birth_date" name="birth_date" value="{{old('birth_date', $paciente->birth_date)}}" required>
    </div>
    <div class="form-group">
        <label for="inputPhone">Telefono de contacto</label>
        <input class="form-control" onkeypress="onliNumbers(event)" type="text" name="phone" id="inputPhone" value="{{old('phone', $paciente->phone)}}" required>
    </div>
    <div class="form-group">
        <label for="inputCorreo">Correo electronico</label>
        <input class="form-control" type="email" name="email" id="inputCorreo" value="{{old('email', $paciente->email)}}" required>
    </div>
    <label>Dirección</label>
    <div class="form-group">
        <textarea class="form-control" name="address">{{old('address', $paciente->address)}}</textarea required>
    </div>
</div>
<script>
    function onliNumbers(event) {
        var key = window.event ? event.keyCode : event.which;
        if (key < 48 || key > 57) {
            event.preventDefault();
        } else {
            return true;
        }
    }

    function onlyString(event) {
        var key = window.event ? event.keyCode : event.which;
        if (key < 65 || key > 90 && key < 97 || key > 122) {
            event.preventDefault();
        } else {
            return true;
        }
    }
</script>
