<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Push Notification Driver
    |--------------------------------------------------------------------------
    |
    | log        = safe development mode; writes simulated pushes to Laravel log.
    | fcm_v1     = production Firebase Cloud Messaging HTTP v1 sender.
    | fcm_legacy = old legacy sender kept only for backward compatibility.
    |
    */

    'driver' => env('PUSH_DRIVER', env('PUSH_SENDER', 'log')),

    'default_timezone' => env('PUSH_DEFAULT_TIMEZONE', env('APP_TIMEZONE', 'Africa/Lagos')),

    'topic_prefix' => env('PUSH_TOPIC_PREFIX', 'app-'),

    'default_click_action' => env('PUSH_CLICK_ACTION', 'FLUTTER_NOTIFICATION_CLICK'),

    'android_channel_id' => env('PUSH_ANDROID_CHANNEL_ID', 'high_importance_channel'),

    /*
    |--------------------------------------------------------------------------
    | FCM HTTP v1
    |--------------------------------------------------------------------------
    |
    | Preferred production configuration:
    | FIREBASE_CREDENTIALS_FILE=/var/www/appshub/storage/app/firebase/dunamis-tv-service-account.json
    | FIREBASE_PROJECT_ID=your-firebase-project-id
    | PUSH_DRIVER=fcm_v1
    |
    | FIREBASE_CREDENTIALS may also contain raw JSON or base64 encoded JSON.
    |
    */

    'fcm_v1_project_id' => env('FIREBASE_PROJECT_ID'),

    'fcm_v1_service_account_path' => env(
        'FCM_V1_SERVICE_ACCOUNT_PATH',
        env('FIREBASE_CREDENTIALS_FILE', env('GOOGLE_APPLICATION_CREDENTIALS'))
    ),

    'fcm_v1_service_account_json' => env(
        'FCM_V1_SERVICE_ACCOUNT_JSON',
        env('FIREBASE_CREDENTIALS')
    ),

    /*
    |--------------------------------------------------------------------------
    | Legacy FCM
    |--------------------------------------------------------------------------
    */

    'fcm_legacy_server_key' => env(
        'FCM_LEGACY_SERVER_KEY',
        env('FCM_SERVER_KEY', env('FIREBASE_SERVER_KEY'))
    ),
];
