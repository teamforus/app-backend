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

$router->group([], function() use ($router) {
    $router->get(
        '/organization-types',
        "Api\Platform\OrganizationTypeController@index"
    );

    $router->get(
        '/product-categories',
        "Api\Platform\ProductCategoryController@index"
    );

    $router->resource(
        'funds',
        "Api\Platform\FundsController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->resource(
        'offices',
        "Api\Platform\OfficesController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->resource(
        'products',
        "Api\Platform\ProductsController", [
        'only' => [
            'index', 'show'
        ]
    ]);

    $router->get(
        'config/{platform_config}',
        'Api\Platform\ConfigController@getConfig'
    );
});

/**
 * Authorization required
 */
$router->group(['middleware' => ['api.auth']], function() use ($router) {
    $router->resource(
        'organizations',
        "Api\Platform\OrganizationsController", [
        'only' => [
            'index', 'show', 'store', 'update'
        ]
    ]);

    $router->post(
        'funds/{fund_id}/apply',
        "Api\Platform\FundsController@apply"
    );

    $router->resource(
        'vouchers',
        "Api\Platform\VouchersController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'vouchers' => 'voucher_address'
        ]
    ]);

    $router->get(
        'vouchers/{voucher_address}/provider',
        "Api\Platform\VouchersController@provider"
    );

    $router->resource(
        'vouchers.transactions',
        "Api\Platform\Vouchers\TransactionsController", [
        'only' => [
            'index', 'show', 'store'
        ],
        'parameters' => [
            'vouchers' => 'voucher_address',
            'transactions' => 'transaction_address',
        ]
    ]);

    $router->get(
        'organizations/{organization}/funds/{fund}/finances',
        "Api\Platform\Organizations\FundsController@finances");

    $router->post(
        'organizations/{organization}/funds/{fund}/top-up',
        "Api\Platform\Organizations\FundsController@topUp");

    $router->resource(
        'organizations.funds',
        "Api\Platform\Organizations\FundsController", [
        'only' => [
            'index', 'show', 'store', 'update'
        ]
    ]);

    $router->resource(
        'organizations.providers',
        "Api\Platform\Organizations\FundProviderController", [
        'only' => [
            'index'
        ],
        'parameters' => [
            'providers' => 'organization_fund'
        ]
    ]);

    $router->get(
        'organizations/{organization}/funds/{fund}/providers/{organization_fund}/finances',
        "Api\Platform\Organizations\Funds\FundProviderController@finances");

    $router->get(
        'organizations/{organization}/funds/{fund}/providers/{organization_fund}/transactions',
        "Api\Platform\Organizations\Funds\FundProviderController@transactions");

    $router->get(
        'organizations/{organization}/funds/{fund}/providers/{organization_fund}/transactions/{transaction_address}',
        "Api\Platform\Organizations\Funds\FundProviderController@transaction");

    $router->resource(
        'organizations.funds.providers',
        "Api\Platform\Organizations\Funds\FundProviderController", [
        'only' => [
            'index', 'show', 'update'
        ],
        'parameters' => [
            'providers' => 'organization_fund'
        ]
    ]);


    $router->resource(
        'organizations.products',
        "Api\Platform\Organizations\ProductsController", [
        'only' => [
            'index', 'show', 'store', 'update', 'destroy'
        ]
    ]);

    $router->resource(
        'organizations.offices',
        "Api\Platform\Organizations\OfficesController", [
        'only' => [
            'index', 'show', 'store', 'update', 'destroy'
        ]
    ]);

    $router->resource(
        'organizations.validators',
        "Api\Platform\Organizations\ValidatorController", [
        'only' => [
            'index', 'show', 'store', 'update', 'destroy'
        ]
    ]);

    $router->get(
        'organizations/{organization}/provider/funds-available',
        'Api\Platform\Organizations\Provider\FundProviderController@availableFunds'
    );

    $router->resource(
        'organizations/{organization}/provider/funds',
        "Api\Platform\Organizations\Provider\FundProviderController", [
        'only' => [
            'index', 'show', 'store', 'update'
        ],
        'parameters' => [
            'funds' => 'organization_fund'
        ]
    ]);

    $router->resource(
        'organizations/{organization}/provider/identities',
        "Api\Platform\Organizations\Provider\ProviderIdentitiesController", [
        'only' => [
            'index', 'show', 'store', 'destroy', 'update'
        ],
        'parameters' => [
            'identities' => 'provider_identity'
        ]
    ]);

    $router->resource(
        'organizations/{organization}/provider/transactions',
        "Api\Platform\Organizations\Provider\TransactionsController", [
            'parameters' => [
                'transactions' => 'transaction_address',
            ]
        ]
    );

    $router->resource(
        'organizations/{organization}/sponsor/transactions',
        "Api\Platform\Organizations\Sponsor\TransactionsController", [
            'parameters' => [
                'transactions' => 'transaction_address',
            ]
        ]
    );

    // Prevalidations endpoints
    $router->post(
        'prevalidations/{prevalidation_uid}/redeem',
        'Api\Platform\PrevalidationController@redeem');

    $router->resource(
        'prevalidations',
        'Api\Platform\PrevalidationController',[
            'only' => [
                'index', 'show', 'store'
            ],
            'parameters' => [
                'prevalidations' => 'prevalidation_uid'
            ]
        ]
    );

    $router->resource(
        'validators',
        "Api\Platform\ValidatorsController", [
        'only' => [
            'index'
        ]
    ]);

    $router->resource(
        'validator-requests',
        "Api\Platform\ValidatorRequestController", [
        'only' => [
            'index', 'show', 'store'
        ]
    ]);

    $router->resource(
        'validator/validator-requests',
        "Api\Platform\Validator\ValidatorRequestController", [
        'only' => [
            'index', 'show', 'update'
        ]
    ]);
});
