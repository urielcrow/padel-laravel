<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


// Route::middleware('api')->get('/custome/saludo', function (Request $request) {
//     return passwdCrow();
// });


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
    Route::post('register', 'AuthController@register');
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'perfil',
], function ($router) {
    Route::patch('/passwd',"PerfilController@passwd")->name("update.pass.user");
    Route::patch('/avatar',"PerfilController@avatar")->name("update.avatar.user");
});


Route::group([
    'middleware' => 'api',
    'prefix' => 'users',
], function ($router) {
    Route::get('/',"UserController@showAllUsers")->name("show.all.users");
    Route::post('/',"UserController@addUser")->name("add.user");
    Route::patch('/',"UserController@updateUser")->name("update.user");
    Route::delete('/{id}',"UserController@deleteUser")->name("delete.user");
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'tournaments',
], function ($router) {
    Route::get('/',"TournamentController@showAllTournaments")->name("view.all.tournaments");
    Route::get('/table/{id}',"TournamentController@tableGeneral")->name("view.table.general"); 
    Route::get('/count/{id}',"TournamentController@tableGeneralByUser")->name("view.table.general.by.user"); 
    Route::get('/list',"TournamentController@showTournamentByUser")->name("view.tournament.by.user"); 
    Route::get('/{id}/{journal}',"TournamentController@showTournament")->name("view.tournament.by.id");  
    Route::post('/',"TournamentController@addTournament")->name("add.tournament");
    Route::put('/',"TournamentController@updateTournament")->name("update.tournament");
    Route::put('/journal_close',"TournamentController@updateCloseJournalTournament")->name("update.journal.tournament");
    Route::put('/status',"TournamentController@statusTournament")->name("update.status.tournament");
    Route::delete('/{id}',"TournamentController@deleteTournament")->name("delete.tournament");
    // Route::get('/{id}',"TournamentController@showTournament")->where(array(
    //     'id'=>'[0-9]+'
    // ))->name("view.tournament.by.id");  
});

//Personalizamos la respuesta para rutas inexistentes
Route::any('{any}', function(){
    return response()->json([
    	'status' => 'error',
        'message' => 'Resource not found'], 404);
})->where('any', '.*')->name("not.found");

