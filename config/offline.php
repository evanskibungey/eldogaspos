<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Offline POS Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the offline POS functionality.
    | You can adjust these settings to customize how offline operations work.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Offline Mode Enabled
    |--------------------------------------------------------------------------
    |
    | Determines whether offline functionality is enabled. When disabled,
    | the POS will always require an internet connection to process sales.
    |
    */
    'enabled' => env('OFFLINE_SYNC_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how offline data synchronization works.
    |
    */
    'sync' => [
        // Maximum number of sync attempts before marking as failed
        'max_attempts' => env('OFFLINE_SYNC_MAX_ATTEMPTS', 3),
        
        // Delay between sync attempts (in seconds)
        'retry_delay' => env('OFFLINE_SYNC_RETRY_DELAY', 30),
        
        // How often to check for pending syncs (in seconds)
        'check_interval' => env('OFFLINE_SYNC_CHECK_INTERVAL', 30),
        
        // Batch size for bulk sync operations
        'batch_size' => env('OFFLINE_SYNC_BATCH_SIZE', 10),
        
        // Timeout for sync requests (in seconds)
        'timeout' => env('OFFLINE_SYNC_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for offline data caching and storage.
    |
    */
    'cache' => [
        // How long to cache product data offline (in hours)
        'product_cache_duration' => env('OFFLINE_CACHE_DURATION', 24),
        
        // Maximum number of offline sales to store locally
        'max_offline_sales' => env('OFFLINE_MAX_SALES', 1000),
        
        // How long to keep synced offline sales (in days)
        'cleanup_after_days' => env('OFFLINE_CLEANUP_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipt Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for offline receipt generation.
    |
    */
    'receipts' => [
        // Prefix for offline receipt numbers
        'offline_prefix' => env('OFFLINE_RECEIPT_PREFIX', 'OFF'),
        
        // Include offline indicator on receipts
        'show_offline_indicator' => env('OFFLINE_RECEIPT_INDICATOR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Interface Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for offline UI features and notifications.
    |
    */
    'ui' => [
        // Show connection status indicator
        'show_connection_status' => env('OFFLINE_SHOW_CONNECTION_STATUS', true),
        
        // Show sync progress notifications
        'show_sync_notifications' => env('OFFLINE_SHOW_SYNC_NOTIFICATIONS', true),
        
        // Auto-hide notifications after X seconds (0 = manual dismiss)
        'notification_timeout' => env('OFFLINE_NOTIFICATION_TIMEOUT', 3),
        
        // Show offline stock warnings
        'show_stock_warnings' => env('OFFLINE_SHOW_STOCK_WARNINGS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for offline operations.
    |
    */
    'security' => [
        // Encrypt offline data in browser storage
        'encrypt_offline_data' => env('OFFLINE_ENCRYPT_DATA', false),
        
        // Require authentication for sync operations
        'require_auth_for_sync' => env('OFFLINE_REQUIRE_AUTH', true),
        
        // Log all sync operations for audit
        'log_sync_operations' => env('OFFLINE_LOG_SYNC', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the service worker that handles offline functionality.
    |
    */
    'service_worker' => [
        // Service worker file path
        'file_path' => '/sw.js',
        
        // Cache name for versioning
        'cache_name' => env('OFFLINE_CACHE_NAME', 'eldogas-pos-v1'),
        
        // URLs to pre-cache for offline use
        'precache_urls' => [
            '/',
            '/pos/dashboard',
            '/css/app.css',
            '/js/app.js',
        ],
        
        // Enable background sync
        'background_sync' => env('OFFLINE_BACKGROUND_SYNC', true),
        
        // Enable push notifications
        'push_notifications' => env('OFFLINE_PUSH_NOTIFICATIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Debug settings for development and troubleshooting.
    |
    */
    'debug' => [
        // Enable debug logging
        'enabled' => env('OFFLINE_DEBUG', false),
        
        // Log level for offline operations
        'log_level' => env('OFFLINE_LOG_LEVEL', 'info'),
        
        // Show debug information in UI
        'show_debug_info' => env('OFFLINE_SHOW_DEBUG', false),
    ],

];