<?php

return [
    'base_url' => env('RESTOSUITE_BASE_URL', 'https://openapi.sea.restosuite.ai'),

    'app_key' => env('RESTOSUITE_APP_KEY'),

    // You only have SECRET KEY from portal (raw).
    // We will hash it in RestoSuiteAuth to produce secretCode for getToken.
    'secret_key' => env('RESTOSUITE_SECRET_KEY'),

    'corporation_id' => (int) env('RESTOSUITE_CORP_ID', 0),

    'auth' => [
        'get_token'     => env('RESTOSUITE_AUTH_GET_TOKEN', '/oauth/getToken'),
        'refresh_token' => env('RESTOSUITE_AUTH_REFRESH_TOKEN', '/oauth/refreshToken'),
    ],

    'endpoints' => [
        'shop_list' => env('RESTOSUITE_ENDPOINT_SHOP_LIST', '/api/v1/bo/shop/queryShopList'),
        'item_list' => env('RESTOSUITE_ENDPOINT_ITEM_LIST', '/api/v1/bo/menu/queryItemList'),
    ],

    // optional header overrides
    'headers' => [
        'corporation' => env('RESTOSUITE_HEADER_CORP', 'Corporation-Id'),
        'app_key'     => env('RESTOSUITE_HEADER_APPKEY', 'RS-OpenAPI-AppKey'),
        'grant_type'  => env('RESTOSUITE_HEADER_GRANT', 'RS-OpenAPI-GrantType'),
        'token'       => env('RESTOSUITE_HEADER_TOKEN', 'RS-OpenAPI-Token'),
        'timestamp'   => env('RESTOSUITE_HEADER_TS', 'RS-OpenAPI-Timestamp'),
        'trace_id'    => env('RESTOSUITE_HEADER_TRACE', 'RS-OpenAPI-TraceId'),
    ],

    'cache' => [
        'token_key'   => env('RESTOSUITE_CACHE_TOKEN_KEY', 'restosuite.token'),
        'refresh_key' => env('RESTOSUITE_CACHE_REFRESH_KEY', 'restosuite.refresh_token'),
        'lock_key'    => env('RESTOSUITE_CACHE_LOCK_KEY', 'restosuite.token.lock'),
    ],

    // refresh a bit earlier than expiry
    'token_safety_seconds' => (int) env('RESTOSUITE_TOKEN_SAFETY_SECONDS', 120),
];
