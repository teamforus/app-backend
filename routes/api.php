<?php

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

$router = app()->make('router');

/**
 * No authorization required
 */
$router->group([], function() use ($router) {
    $router->group(['prefix' => '/identity'], function() use ($router) {
        $router->post('/', 'Api\IdentityController@store');

        $router->group(['prefix' => '/proxy'], function() use ($router) {
            $router->post('/code', 'Api\IdentityController@proxyAuthorizationCode');
            $router->post('/token', 'Api\IdentityController@proxyAuthorizationToken');
            $router->post('/email', 'Api\IdentityController@proxyAuthorizationEmailToken');

            $router->get('/redirect/email/{source}/{emailToken}', 'Api\IdentityController@proxyRedirectEmail');
            $router->get('/authorize/email/{source}/{emailToken}', 'Api\IdentityController@proxyAuthorizeEmail');

            $router->get('/check-token', 'Api\IdentityController@checkToken');
        });

        /**
         * Record types
         */
        $router->group(['prefix' => '/record-types'], function() use ($router) {
            $router->get('/', 'Api\Identity\RecordTypeController@index');
        });
    });
});

/**
 * Authorization required
 */
$router->group(['middleware' => ['api.auth']], function() use ($router) {
    $router->group(['prefix' => '/identity'], function() use ($router) {
        $router->get('/', 'Api\IdentityController@getPublic');
        $router->get('/pin-code/{pinCode}', 'Api\IdentityController@checkPinCode');
        $router->post('/pin-code', 'Api\IdentityController@updatePinCode');

        /**
         * Identity proxies
         */
        $router->group(['prefix' => '/proxy'], function() use ($router) {
            $router->delete('/', 'Api\IdentityController@proxyDestroy');

            $router->group(['prefix' => '/authorize'], function() use ($router) {
                $router->post('/code', 'Api\IdentityController@proxyAuthorizeCode');
                $router->post('/token', 'Api\IdentityController@proxyAuthorizeToken');
            });
        });

        /**
         * Record categories
         */
        $router->group(['prefix' => '/record-categories'], function() use ($router) {
            $router->get('/', 'Api\Identity\RecordCategoryController@index');
            $router->post('/', 'Api\Identity\RecordCategoryController@store');
            $router->patch('/sort', 'Api\Identity\RecordCategoryController@sort');
            $router->get('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@show');
            $router->patch('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@update');
            $router->delete('/{recordCategoryId}', 'Api\Identity\RecordCategoryController@destroy');
        });

        /**
         * Record
         */
        $router->group(['prefix' => '/records'], function() use ($router) {
            $router->get('/', 'Api\Identity\RecordController@index');
            $router->post('/', 'Api\Identity\RecordController@store');
            $router->get('/types', 'Api\Identity\RecordController@typeKeys');
            $router->patch('/sort', 'Api\Identity\RecordController@sort');
            $router->get('/{recordId}', 'Api\Identity\RecordController@show');
            $router->patch('/{recordId}', 'Api\Identity\RecordController@update');
            $router->delete('/{recordId}', 'Api\Identity\RecordController@destroy');
        });

        /**
         * Record validations
         */
        $router->group(['prefix' => '/record-validations'], function() use ($router) {
            $router->post('/', 'Api\Identity\RecordValidationController@store');
            $router->get('/{recordUuid}', 'Api\Identity\RecordValidationController@show');
            $router->patch('/{recordUuid}/approve', 'Api\Identity\RecordValidationController@approve');
            $router->patch('/{recordUuid}/decline', 'Api\Identity\RecordValidationController@decline');
        });
    });

    $router->get('/status', function() {
        return [
            'status' => 'ok'
        ];
    });

    $router->resource('medias', 'Api\MediaController', [
        'only' => ['index', 'show', 'store', 'destroy'],
        'parameters' => [
            'medias' => 'media_uid'
        ]
    ]);

    $router->get('/debug', 'TestController@test');
});