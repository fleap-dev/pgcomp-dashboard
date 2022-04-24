<?php

use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Dashboard\ProductionsController;
use App\Http\Controllers\Api\Dashboard\ProgramsController;
use App\Http\Controllers\Api\Dashboard\QualisController;
use App\Http\Controllers\Api\Dashboard\StudentsController;
use App\Http\Controllers\Api\PanelAdmin\AreaController;
use App\Http\Controllers\Api\PanelAdmin\SubareaController;
use App\Http\Controllers\Api\PanelAdmin\StratumQualisController;
use App\Http\Controllers\Api\PanelAdmin\UsersController;
use App\Http\Controllers\Api\PanelAdmin\StudentController;
use App\Http\Controllers\Api\PanelAdmin\ProfessorController;
use App\Http\Controllers\Api\JournalsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\IsAdmin;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['name' => 'dashboard.', 'prefix' => 'dashboard'], function () {
    //TODO: Dar nomes melhores e mais padrao
    Route::get('program', [ProgramsController::class, 'programName']);
    Route::get('all_production', [ProductionsController::class, 'totalProductionsPerYear']);
    Route::get('students_production', [ProductionsController::class, 'studentsProductions']);
    Route::get('production_per_qualis', [StratumQualisController::class, 'productionPerQualis']);
    Route::get('fields', [StudentsController::class, 'studentsArea']);
    Route::get('subfields', [StudentsController::class, 'studentsSubarea']);
    Route::get('total_students_per_advisor', [DashboardController::class, 'advisors']);
});

Route::group(['middleware' => ['auth:sanctum'], 'name' => 'portal.', 'prefix' => 'portal'], function () {
    Route::post('user/lattes-update', [UserController::class, 'importLattesFile']);

    Route::group(['name' => 'admin.', 'prefix' => 'admin', 'middleware' => [IsAdmin::class]], function () {
        Route::resource('journals', JournalsController::class);
        // @todo add admin routes
        //CRUD Area
        Route::resource('qualis', StratumQualisController::class)->except(['destroy']);
        Route::resource('fields', AreaController::class);
        Route::resource('subfields', SubareaController::class);
        Route::resource('users', UsersController::class)->except(['store']);
        Route::resource('students', StudentsController::class)->except(['store']);
        Route::resource('professors', ProfessorController::class)->except(['store']);
    });
});
