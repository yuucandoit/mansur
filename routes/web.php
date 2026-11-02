<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\BoardListController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AssigneeController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\AdminSettingController;
use App\Http\Controllers\AdminEmailTemplateController;
use App\Http\Controllers\AdminLanguageController;
use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminAiController;
use App\Http\Controllers\AiAgentController;
use Illuminate\Support\Facades\Auth;

// Dashboard - onboarding, project picker, or first project board
Route::get('/', [OnboardingController::class, 'index'])->name('onboarding');

// Public invitation landing page (login is required to accept)
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');

Route::middleware(['auth'])->group(function(){
    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/board', [ProjectController::class, 'board'])->name('projects.board');
    Route::get('/projects/{project}/list', [ProjectController::class, 'list'])->name('projects.list');
    Route::get('/projects/{project}/calendar', [ProjectController::class, 'calendar'])->name('projects.calendar');
    Route::get('/projects/{project}/gantt', [ProjectController::class, 'gantt'])->name('projects.gantt');
    Route::get('/projects/{project}/table', [ProjectController::class, 'table'])->name('projects.table');
    Route::get('/projects/{project}/members', [ProjectController::class, 'members'])->name('projects.members');
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

    // Assignees
    Route::post('/tasks/{task}/assignees', [AssigneeController::class, 'store'])->name('assignees.store');
    Route::delete('/assignees/{assignee}', [AssigneeController::class, 'destroy'])->name('assignees.destroy');

    // Labels
    Route::post('/tasks/{task}/labels', [LabelController::class, 'store'])->name('labels.store');
    Route::put('/labels/{label}', [LabelController::class, 'update'])->name('labels.update');
    Route::delete('/labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');

    // Attachments
    Route::post('/tasks/{task}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Checklists
    Route::post('/tasks/{task}/checklists', [\App\Http\Controllers\ChecklistController::class, 'store'])->name('checklists.store');
    Route::put('/checklists/{checklist}', [\App\Http\Controllers\ChecklistController::class, 'update'])->name('checklists.update');
    Route::delete('/checklists/{checklist}', [\App\Http\Controllers\ChecklistController::class, 'destroy'])->name('checklists.destroy');

    // Comments
    Route::post('/tasks/{task}/comments', [\App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');

    // Workspaces
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('/workspaces/{workspace}/projects', [WorkspaceController::class, 'getProjects'])->name('workspaces.projects');
    Route::get('/workspaces/{workspace}/members', [WorkspaceController::class, 'members'])->name('workspaces.members');
    Route::patch('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'updateMemberRole'])->name('workspaces.members.update');
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember'])->name('workspaces.members.remove');
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');

    // Workspace → project requirement
    Route::get('/workspaces/{workspace}/join-project', [OnboardingController::class, 'joinProject'])->name('workspaces.join-project');
    Route::post('/workspaces/{workspace}/join-project', [OnboardingController::class, 'storeJoinProject'])->name('workspaces.join-project.store');

    // Invitations
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::post('/invitations/paste', [InvitationController::class, 'paste'])->name('invitations.paste');
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

    // AI Assistant
    Route::get('/ai', [AiAgentController::class, 'index'])->name('ai.index');
    Route::post('/ai/chat', [AiAgentController::class, 'chat'])->middleware('throttle:20,1')->name('ai.chat');

    // User preferences (settings sheet)
    Route::patch('/preferences', [PreferenceController::class, 'update'])->name('preferences.update');

    // Administration (global admin only)
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
        Route::put('/users/{user}/password', [AdminUserController::class, 'updatePassword'])->name('users.update-password');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.update-status');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // AI agent settings
        Route::get('/ai', [AdminAiController::class, 'index'])->name('ai.index');
        Route::post('/ai', [AdminAiController::class, 'update'])->name('ai.update');
        Route::delete('/ai/key', [AdminAiController::class, 'clearKey'])->name('ai.clear-key');

        // Global settings
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettingController::class, 'store'])->name('settings.store');
        Route::put('/settings/{setting}', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::delete('/settings/{setting}', [AdminSettingController::class, 'destroy'])->name('settings.destroy');

        // Email templates
        Route::get('/email-templates', [AdminEmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('/email-templates/create', [AdminEmailTemplateController::class, 'create'])->name('email-templates.create');
        Route::post('/email-templates', [AdminEmailTemplateController::class, 'store'])->name('email-templates.store');
        Route::get('/email-templates/{emailTemplate}/edit', [AdminEmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('/email-templates/{emailTemplate}', [AdminEmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::delete('/email-templates/{emailTemplate}', [AdminEmailTemplateController::class, 'destroy'])->name('email-templates.destroy');

        // Languages
        Route::get('/languages', [AdminLanguageController::class, 'index'])->name('languages.index');
        Route::post('/languages', [AdminLanguageController::class, 'store'])->name('languages.store');
        Route::put('/languages/{language}', [AdminLanguageController::class, 'update'])->name('languages.update');
        Route::delete('/languages/{language}', [AdminLanguageController::class, 'destroy'])->name('languages.destroy');

        // Backups
        Route::get('/backups', [AdminBackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [AdminBackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{backup}/download', [AdminBackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{backup}', [AdminBackupController::class, 'destroy'])->name('backups.destroy');

        // Legacy roles
        Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');
    });
});


Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
