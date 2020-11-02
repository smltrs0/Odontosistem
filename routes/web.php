<?php

use App\Permission\Models\Role;
use App\procedure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
//use App\Permission\Models\Role;
//use App\Permission\Models\Permission;
//use Illuminate\Support\Facades\Gate;


Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

// Ruta que permite modificar el usuario creado por laravel
// agregamos solo un middleware ya que solo necesitamos que el usuario este logueado

Route::resource('/mi-cuenta', 'MyAcountController');

Route::get('/home', 'HomeController@index')->name('home');

Route::resource('/pacientes', 'PacientesController');

Route::resource('/role', 'RoleController')->names('role');

Route::resource('/procedures', 'procedureController');

Route::resource('/user', 'UserController', ['except' => [
    'create', 'store'
]])->names('user');

Route::resource('citas', 'CitasController');

// Rutas para el backup
Route::get('backup', 'BackupController@index')->name('respaldo');
Route::get('backup/create', 'BackupController@create');
Route::get('backup/download/{file_name}', 'BackupController@download');
Route::get('backup/delete/{file_name}', 'BackupController@delete');

// Solo vistas
Route::resource('/citas-hoy','AdminCitasController');

Route::get('pagos',function (){
   return view('finanzas.EstadoPagos');
})->name('pagos');

Route::get('finanzas', function () {
    return view('finanzas.finanzas');
})->name('finanzas');

// Ruta para crear PDF de ejemplo
Route::get('generate-pdf','PDFController@generatePDF');

Route::get('test', function(){
    $procedures = procedure::find(50);
   
   dd($procedures);
});


