<?php

return [

    'drift' => [
        'token' => env('DRIFT_TOKEN'),
        'users' => [
            'gateway' => 'https://driftapi.com/users/list',
            'table' => 'drift_users',
            'history_table' => 'drift_users_history'
        ],
        'conversations' => [
            'gateway' => 'https://driftapi.com/conversations/list',
            'report_gateway' => 'https://driftapi.com/reports/conversations',
            'chunk_size' => '50',
        ],
        'stats' => [
            'gateway' => 'https://driftapi.com/conversations/stats',
            'history_table' => 'drift_stats_history'
        ]
    ],

    'nocworx_oauth' => [
        'gateway' => 'https://nocworx.nexcess.net/',
        'public' => env('NOCWORX_PUBLIC'),
        'private' => env('NOWORX_PRIVATE'),
        'name' => 'wallboartapi-oauth',
        'agent_id' => '1140',
        'user_table' => 'nocworx_users',
        'ticket_table' => 'nocworx_tickets'
    ]

];
