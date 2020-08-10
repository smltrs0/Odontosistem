<?php

use Illuminate\Support\Facades\Route;
use App\Pacientes;
use App\User;
use App\Permission\Models\Role;
use App\Permission\Models\Permission;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

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

Route::resource('/user', 'UserController', ['except' => [
    'create', 'store'
]])->names('user');

Route::resource('citas', 'CitasController');

// Solo vistas
Route::resource('/citas-hoy','AdminCitasController');

Route::get('finanzas', function () {
    return view('finanzas.finanzas');
})->name('finanzas');


// Ruta para crear PDF de ejemplo
Route::get('generate-pdf','PDFController@generatePDF');

Route::get('test', function(){
    $paciente =DB::table('pacientes')
        ->rightJoin('citas', 'pacientes.id', '=', 'citas.paciente_id')
        ->get();
   dd($paciente);
});


