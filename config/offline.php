<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Offline Mode Configuration
    |--------------------------------------------------------------------------
    |
    | This controls whether the POS system should enable offline functionality.
    | When enabled, the system can work without internet connection and sync
    | data when connection is restored.
    |
    | Set to true for production environments
    | Set to false for local development to avoid connection issues
    |
    */
    
    'enabled' => env('OFFLINE_MODE_ENABLED', false),
    
    /*
    |--------------------------------------------------------------------------
    | Offline Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the offline database storage
    |
    */
    
    'database' => [
        'name' => 'eldogas_offline_db',
        'version' => 1,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for data synchronization
    |
    */
    
    'sync' => [
        'auto_sync_interval' => 30000, // 30 seconds
        'max_retry_attempts' => 3,
        'retry_delay' => 5000, // 5 seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | How long to wait before considering the connection lost (in milliseconds)
    |
    */
    
    'connection_timeout' => 10000, // 10 seconds
];
