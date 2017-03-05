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

// ----------------------------------------------------
// public API
// ----------------------------------------------------

Route::group(['middleware' => [ 'api', 'jsonapi' ], 'namespace' => 'Api'], function () {

    // access tokens

    Route::post('access_tokens', [ 'as' => 'access_tokens.create', 'uses' => 'AccessTokensController@create' ]);

    // ----------------------------------------------------
    // private API
    // ----------------------------------------------------

    Route::group(['middleware' => [ 'jsonapi.jwt', 'jwt.auth' ]], function () {

        Route::get('', 'ApiController@index');

        // primary resources

        Route::resource('users', 'UsersController', ['except' => ['destroy', 'edit']]);
        Route::resource('tasks', 'TasksController', ['except' => ['edit']]);
        Route::resource('projects', 'ProjectsController', ['except' => ['edit']]);

        // sub resources

        // ... users
        Route::get('users/{id}/projects', [ 'as' => 'users.projects.index', 'uses' => 'UsersController@indexRelated' ]);
        Route::get('users/{id}/tasks', [ 'as' => 'users.tasks.index', 'uses' => 'UsersController@indexRelated' ]);

        // ... projects : editors
        Route::get('projects/{id}/editors', [ 'as' => 'projects.editors.index', 'uses' => 'ProjectsController@indexRelated' ]);

        // ... projects : owner
        Route::get('projects/{id}/owner', [ 'as' => 'projects.owner.show', 'uses' => 'ProjectsController@showRelated' ]);

        // ... projects : tasks
        Route::get('projects/{id}/tasks', [ 'as' => 'projects.tasks.index', 'uses' => 'ProjectsController@indexRelated' ]);

        // ... tasks : editors
        Route::get('tasks/{id}/editors', [ 'as' => 'projects.editors.index', 'uses' => 'TasksController@indexRelated' ]);

        // ... tasks : owner
        Route::get('tasks/{id}/owner', [ 'as' => 'projects.owner.show', 'uses' => 'TasksController@showRelated' ]);

        // ... tasks : project
        Route::get('tasks/{id}/project', [ 'as' => 'projects.projects.show', 'uses' => 'TasksController@showRelated' ]);

        // ... tasks : users
        Route::get('tasks/{id}/editors', [ 'as' => 'projects.editors.index', 'uses' => 'TasksController@indexRelated' ]);

        // relationships

        // ... projects : editors
        //     we will update editors through the users relationship,
        //     to ensure we are explicit about what we are doing which is:
        //     replacing all of the project's user relationships,
        //     and not just updating those users that are flagged with is_editor
        //     so no PATCH requests to editors
        //     if we wanted to support PATCH requests to editors then we would have to have to return
        Route::get('projects/{id}/relationships/editors', [ 'as' => 'projects.relationships.editors.index', 'uses' => 'ProjectsController@indexRelated', 'is_minimal' => true ]);

        // ... projects : owner
        Route::get('projects/{id}/relationships/owner', [ 'as' => 'projects.relationships.owner.show', 'uses' => 'ProjectsController@showRelated', 'is_minimal' => true ]);
        Route::patch('projects/{id}/relationships/owner', [ 'as' => 'projects.relationships.owner.update', 'uses' => 'ProjectsController@updateRelated', 'is_minimal' => true ]);
        //    Route::delete('projects/{project}/relationships/owner', [ 'as' => 'projects.relationships.owner.destroy', 'uses' => 'ProjectsController@owner' ]);

        // ... projects : tasks
        Route::get('projects/{id}/relationships/tasks', [ 'as' => 'projects.relationships.tasks.index', 'uses' => 'ProjectsController@indexRelated', 'is_minimal' => true ]);
        Route::patch('projects/{id}/relationships/tasks', [ 'as' => 'projects.relationships.tasks.update', 'uses' => 'ProjectsController@updateRelated', 'is_minimal' => true ]);

        // ... projects : users
        Route::get('projects/{id}/relationships/users', [ 'as' => 'projects.relationships.users.index', 'uses' => 'ProjectsController@indexRelated', 'is_minimal' => true ]);
        Route::patch('projects/{id}/relationships/users', [ 'as' => 'projects.relationships.users.update', 'uses' => 'ProjectsController@updateRelated', 'is_minimal' => true ]);
        Route::post('projects/{id}/relationships/users', [ 'as' => 'projects.relationships.users.store', 'uses' => 'ProjectsController@storeRelated', 'is_minimal' => true ]);
        Route::delete('projects/{id}/relationships/users', [ 'as' => 'projects.relationships.users.destroy', 'uses' => 'ProjectsController@destroyRelated', 'is_minimal' => true ]);

        // ... tasks : editors
        Route::get('tasks/{id}/relationships/editors', [ 'as' => 'tasks.relationships.editors.index', 'uses' => 'TasksController@indexRelated', 'is_minimal' => true ]);

        // ... tasks : owner
        Route::get('tasks/{id}/relationships/owner', [ 'as' => 'tasks.relationships.owner.show', 'uses' => 'TasksController@showRelated', 'is_minimal' => true ]);
        Route::patch('tasks/{id}/relationships/owner', [ 'as' => 'tasks.relationships.owner.update', 'uses' => 'TasksController@updateRelated', 'is_minimal' => true ]);

        // ... tasks : project
        Route::get('tasks/{id}/relationships/project', [ 'as' => 'tasks.relationships.project.show', 'uses' => 'TasksController@showRelated', 'is_minimal' => true ]);
        Route::patch('tasks/{id}/relationships/project', [ 'as' => 'tasks.relationships.project.update', 'uses' => 'TasksController@updateRelated', 'is_minimal' => true ]);

        // ... tasks : users
        Route::get('tasks/{id}/relationships/users', [ 'as' => 'tasks.relationships.users.index', 'uses' => 'TasksController@indexRelated', 'is_minimal' => true ]);

        // ... users
        Route::get('users/{id}/relationships/projects', [ 'as' => 'users.relationships.projects.index', 'uses' => 'UsersController@indexRelated', 'is_minimal' => true ]);
        Route::get('users/{id}/relationships/tasks', [ 'as' => 'users.relationships.tasks.index', 'uses' => 'UsersController@indexRelated', 'is_minimal' => true ]);

    });
});
