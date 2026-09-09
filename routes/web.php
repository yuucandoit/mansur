<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\BoardListController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Auth;

// Dashboard - redirect to first project or show all
Route::get('/', function() {
    if(Auth::check()){
        $project = \App\Models\Project::where('user_id',Auth::user()->id)->with('workspace')->orderByDesc('updated_at')->first();
        if ($project) {
            return redirect()->route('projects.board', $project);
        }
        return view('welcome');
    }else{
        return view('auth.login');
    }
});

Route::middleware(['auth'])->group(function(){
    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/board', [ProjectController::class, 'show'])->name('projects.board');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/star', [ProjectController::class, 'toggleStar'])->name('projects.star');

    // Board Lists (columns)
    Route::post('/board-lists', [BoardListController::class, 'store'])->name('board-lists.store');
    Route::put('/board-lists/{boardList}', [BoardListController::class, 'update'])->name('board-lists.update');
    Route::post('/board-lists/order', [BoardListController::class, 'updateOrder'])->name('board-lists.order');
    Route::delete('/board-lists/{boardList}', [BoardListController::class, 'archive'])->name('board-lists.archive');

    // Tasks
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('/tasks/order', [TaskController::class, 'updateOrder'])->name('tasks.order');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Workspaces
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('/workspaces/{workspace}/projects', [WorkspaceController::class, 'getProjects'])->name('workspaces.projects');
    
});


Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
