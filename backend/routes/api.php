<?php

use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Dashboard\ProductionsController;
use App\Http\Controllers\Api\Dashboard\ProgramsController;
use App\Http\Controllers\Api\Dashboard\QualisController;
use App\Http\Controllers\Api\Dashboard\StudentsController;
use App\Http\Controllers\Api\PanelAdmin\AreaController;
use App\Http\Controllers\Api\PanelAdmin\AuthController;
use App\Http\Controllers\Api\PanelAdmin\ConferenceController;
use App\Http\Controllers\Api\PanelAdmin\CourseController;
use App\Http\Controllers\Api\PanelAdmin\JournalController;
use App\Http\Controllers\Api\PanelAdmin\ProductionController as ProductionAdminController;
use App\Http\Controllers\Api\PanelAdmin\ProfessorProductionController;
use App\Http\Controllers\Api\PanelAdmin\StudentProductionController;
use App\Http\Controllers\Api\PanelAdmin\ProfessorController;
use App\Http\Controllers\Api\PanelAdmin\ProgramController as ProgramAdminController;
use App\Http\Controllers\Api\PanelAdmin\StratumQualisController;
use App\Http\Controllers\Api\PanelAdmin\StudentController as StudentAdminController;
use App\Http\Controllers\Api\PanelAdmin\SubareaController;
use App\Http\Controllers\Api\PanelAdmin\UserController as UserAdminController;
use App\Http\Controllers\Api\PanelAdmin\UserProgramController;
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

    Route::get('production_per_qualis', [QualisController::class, 'productionPerQualis']);

    Route::get('subfields', [StudentsController::class, 'studentCountPerSubArea']);
    Route::get('fields', [StudentsController::class, 'studentCountPerArea']);

    Route::get('total_students_per_advisor', [DashboardController::class, 'advisors']);
});

Route::group(['middleware' => ['auth:sanctum'], 'name' => 'portal.', 'prefix' => 'portal'], function () {
    Route::post('user/lattes-update', [UserController::class, 'importLattesFile']);

    Route::group(['name' => 'admin.', 'prefix' => 'admin', 'middleware' => [IsAdmin::class]], function () {
        Route::apiResource('journals', JournalController::class)->except(['destroy']);
        Route::apiResource('conferences', ConferenceController::class)->except(['destroy']);
        Route::apiResource('courses', CourseController::class)->except(['destroy']);
        Route::apiResource('productions', ProductionAdminController::class)->except(['destroy']);
        Route::apiResource('programs', ProgramAdminController::class)->except(['destroy']);
        Route::apiResource('qualis', StratumQualisController::class)->except(['destroy']);
        Route::apiResource('areas', AreaController::class);
        Route::apiResource('subareas', SubareaController::class);
        Route::apiResource('users', UserAdminController::class)->except(['destroy']);

        Route::apiResource('students', StudentAdminController::class)->except(['destroy']);
        Route::apiResource('students.productions', StudentProductionController::class)
            ->except(['destroy']);
        Route::apiResource('professors', ProfessorController::class)->except(['destroy']);
        Route::apiResource('professors.productions', ProfessorProductionController::class)
            ->except(['destroy']);

        Route::apiResource('user_program', UserProgramController::class)->except(['destroy']);

        Route::get('all_subareas_per_area', [AreaController::class, 'subareaPerArea']);
    });
});

Route::get('healthcheck', function (Request $request) {
    \Illuminate\Support\Facades\DB::getPdo();
    $startTime = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT');

    return ['success' => true, 'response_time_in_ms' => floor((microtime(true) - $startTime) * 1000)];
});

Route::post('login', [AuthController::class, 'login']);
