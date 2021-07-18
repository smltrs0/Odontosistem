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

Route::get('paciente/{id}/odontograma', 'PacientesController@viewOdontograma');

Route::patch('guardarOdontograma/{id}','PacientesController@guardarOdontograma')->name('guardarOdontograma');

Route::resource('/role', 'RoleController')->names('role');

Route::resource('/procedures', 'procedureController');

Route::resource('/user', 'UserController', ['except' => [
    'create', 'store'
]])->names('user');

Route::resource('/citas-hoy','AdminCitasController');

Route::resource('citas', 'CitasController');

// Rutas para el backup
Route::get('backup', 'BackupController@index')->name('respaldo');
Route::get('backup/create', 'BackupController@create');
Route::get('backup/download/{file_name}', 'BackupController@download');
Route::get('backup/delete/{file_name}', 'BackupController@delete');
Route::get('/restauracion', function (){
    return view('backup.restore');
})->name('restauracion');

// Solo vistas

Route::resource('citas-medicas', 'CitasMedicasController')->names('citas-medicas');


Route::resource('pagos', 'AbonosController')->names('pagos');
Route::get('generar-pago/{id}', 'AbonosController@registrarPago')->name('generar-pago');
Route::get('detalle-pago/{id}', 'PagosController@detallersAbono')->name('detalle-pago');

Route::post('registrar-pago-factura/{id}', 'AbonosController@registrarPagoFactura')->name('registrar-pago-factura');


Route::get('cuentas-por-cobrar', 'AbonosController@cuentasPorCobrar')->name('cuentas-por-cobrar');
Route::get('cuenta-por-cobrar/{id}', 'AbonosController@show')->name('cuenta-por-cobrar');

Route::get('registrar-pago',function (){
    return view('finanzas.RegistrarPago');
 })->name('registrar-pago');


Route::get('estadisticas-pacientes', function () {
    return view('finanzas.finanzas');
})->name('estadisticas-pacientes');

Route::get('facturas', 'FacturasController@index')->name('facturas');

Route::post('crear-factura', 'PDFController@registrarPagoCreacionFactura')->name('crear-factura');

Route::get('generar-factura/{id}','PDFController@generatePDF')->name('generar-factura');


Route::get('confirmar-asistencia/{id}','AsistenciaController@confirmar')->name('confirmar-asistencia');
Route::get('cancelar-asistencia/{id}','AsistenciaController@cancelar')->name('cancelar-asistencia');

Route::get('test', function(){
    $procedures = procedure::find(50);

   dd($procedures);
});


