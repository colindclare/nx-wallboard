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
        'site_down_table' => 'nocworx_site_down',
	'endpoints' => [
	    'all_users' => [
		'uri' => 'agent',
		'data' => [
		    'filter[status]' => 'active',
		    'pageIndex' => 0
		]
	    ],
	    'roles' => [
		'uri' => 'agent-role',
		'data' => [
			'filter[id]' => '16'
		]
	    ],
	    'support_tickets' => [
		'uri' => 'ticket',
		'data' => [
		    'filter[department]' => '1',
		    'filter[status]' => '1',
		    'pageIndex' => 0
		]
	    ],
	    'site_down_tickets' => [
		'uri' => 'ticket',
		'data' => [
                      'filter[department]' => '1',
                      'filter[status]' => '1',
                      'filter[category-filter]' => '10',
                      'filter[num_posts_staff]' => 'no',
                      'pageIndex' => 0
                  ]
	    ]

	]
    ]

];
