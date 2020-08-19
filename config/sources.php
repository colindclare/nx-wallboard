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
            'gateway' => 'https://driftapi.com/conversations/',
            'report_gateway' => 'https://driftapi.com/reports/conversations',
            'history_table' => 'drift_stats_history'
        ]
    ],

    'nocworx' => [
        'gateway' => 'https://nocworx.nexcess.net/',
        'public' => env('NOCWORX_PUBLIC'),
        'private' => env('NOCWORX_PRIVATE'),
        'user_table' => 'nocworx_users',
        'ticket_table' => 'nocworx_tickets',
	'endpoints' => [
	    'all_users' => [
		'uri' => 'agent',
		'data' => ['filter[status]' => 'active']
	    ],

	]
    ]

];
