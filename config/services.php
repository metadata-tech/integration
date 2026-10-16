<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'lpm' => [
        'api_key' => env('lpm_key'),
        'url' => env('lpm_url'),
    ],
    'lpm_svk' => [
        'api_key' => env('lpm_svk_key'),
        'url' => env('lpm_svk_url'),
        'email' => env('lpm_svk_email'),
        'password' => env('lpm_svk_password'),
    ],
    'lpm_apkv' => [
        'url' => env('lpm_apkv_url'),
        'bearer' => env('lpm_apkv_bearer'),
    ],
    'kpm' => [
        'api_key' => env('kpm_key'),
        'url' => env('kpm_url'),
    ],

    'kpt_esis' => [
        'url' => env('kpt_esis_url'),
        'username' => env('kpt_esis_username'),
        'password' => env('kpt_esis_password'),
    ],

    'kpt_skpg' => [
        'url' => env('kpt_skpg_url'),
        'username' => env('kpt_skpg_username'),
        'password' => env('kpt_skpg_password'),
    ],

    'mygdx' => [
        'subsystem_code' => env('MYGDX_SUBSYSTEM_CODE', 'ESPP'),
    ],
    
    'mqr' => [
        'url' => env('MQR_URL'),
        'mapping_connection' => env(
            'MQR_MAPPING_CONNECTION',
            'mqr_mapping'
        ),
        'mapping_schema' => env('MQR_MAPPING_DB_SCHEMA'),
    ],
    
    'jpn' => [
        'api_key' => env('jpn_key'),
        'url' => env('jpn_url'),
    ],

    'iscaf' => [
        'url' => env('ISCAF_URL', 'http://10.29.59.123/api/v1'),
        'api_key' => env('ISCAF_API_KEY'),
        'status_method' => env('ISCAF_STATUS_METHOD', 'POST'),
    ],

    'myspp' => [
        'url' => env('MYSPP_DOKUMEN_URL', 'http://10.29.58.50/myspp/api/espp_dokumen.php'),
        'api_key' => env('MYSPP_DOKUMEN_TOKEN'),
    ],

];
