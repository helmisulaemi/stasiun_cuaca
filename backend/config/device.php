<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Device Statuses
    |--------------------------------------------------------------------------
    */

    'statuses' => ['provisioned', 'active', 'maintenance', 'decommissioned'],

    /*
    |--------------------------------------------------------------------------
    | State Machine Transitions
    |--------------------------------------------------------------------------
    |
    | provisioned → active ↔ maintenance → decommissioned (terminal)
    |
    */

    'transitions' => [
        'provisioned'    => ['active'],
        'active'         => ['maintenance'],
        'maintenance'    => ['active', 'decommissioned'],
        'decommissioned' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline Threshold (minutes)
    |--------------------------------------------------------------------------
    |
    | Device dianggap OFFLINE jika last_seen_at > threshold.
    | Default: 15 menit.
    |
    */

    'offline_threshold_minutes' => (int) env('OFFLINE_THRESHOLD_MINUTES', 15),

];
