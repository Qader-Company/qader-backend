<?php

return [
    // Queue used for notification jobs; null uses the connection's default queue.
    'queue' => env('NOTIFICATIONS_QUEUE'),

    // Queues inspected by `notifications:health`.
    'queues' => array_values(array_filter([env('NOTIFICATIONS_QUEUE', 'default')])),

    'reminders' => [
        'before_minutes' => 60,
        // How late an "at start" reminder may still be sent if the scheduler was delayed.
        'start_grace_minutes' => 10,
    ],

    'health' => [
        'max_pending_per_queue' => (int) env('NOTIFICATIONS_MAX_PENDING', 500),
        'reverb_timeout_seconds' => 2,
    ],
];
