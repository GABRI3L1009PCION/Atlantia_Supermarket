<?php

return [
    'scheduler' => [
        'heartbeat_key' => env('ATLANTIA_SCHEDULER_HEARTBEAT_KEY', 'atlantia:ops:scheduler-heartbeat'),
        'stale_after_minutes' => (int) env('ATLANTIA_SCHEDULER_STALE_AFTER_MINUTES', 3),
    ],

    'alerts' => [
        'channel' => env('ATLANTIA_ALERTS_CHANNEL', 'incidents'),
        'cooldown_minutes' => (int) env('ATLANTIA_ALERTS_COOLDOWN_MINUTES', 15),
    ],

    'queue' => [
        'warning_failed_jobs' => (int) env('ATLANTIA_QUEUE_FAILED_JOBS_WARNING', 1),
        'error_failed_jobs' => (int) env('ATLANTIA_QUEUE_FAILED_JOBS_ERROR', 10),
    ],

    'backups' => [
        'warning_hours' => (int) env('ATLANTIA_BACKUP_WARNING_HOURS', 26),
        'error_hours' => (int) env('ATLANTIA_BACKUP_ERROR_HOURS', 50),
    ],

    'release' => [
        'staging_url' => env('ATLANTIA_STAGING_URL'),
        'status_page_url' => env('ATLANTIA_STATUS_PAGE_URL'),
        'runbook_path' => env('ATLANTIA_RUNBOOK_PATH', 'docs/observabilidad-incidentes.md'),
    ],
];
