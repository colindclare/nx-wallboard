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

    'pbx' => [
        'host' => '10.30.100.40',
        'port' => '5038',
        'username' => env('PBX_USERNAME'),
        'secret' => env('PBX_SECRET'),
        'queue_users_table' => 'queue_users',
        'queues_table' => 'queues'
    ],

    'adp' => [
	'token' => env('ADP_TOKEN'),
	'gateway' => 'https://schedules.liquidweb.com/api/v1',
	'endpoints' => [
	    'schedules' => [
		'uri' => '/adp/schedules',
		'params' => ''
	    ],
	    'punches' => [
		'uri' => '/adp/punches',
		'params' => ''
	    ]
	]

    ],

    'nocworx' => [
        'gateway' => 'https://nocworx.nexcess.net/',
        'public' => env('NOCWORX_PUBLIC'),
        'private' => env('NOCWORX_PRIVATE'),
        'user_table' => 'nocworx_users',
        'ticket_table' => 'nocworx_tickets',
        'migrations_table' => 'nocworx_migration_tickets',
        'site_down_table' => 'nocworx_site_down',
	'escalations' => [
	    'support' => [
		1 => [
		        'name' => 'Unescalated',
			'id' => 1
		],
		57 => [
			'name' => 'Tier 2',
			'id' => 2
		],
		40 => [
			'name' => 'ESG',
			'id' => 3
		],
		150 => [
			'name' => 'Worx/SysOps Pending',
			'id' => 4
		],
		114 => [
			'name' => 'Overages',
			'id' => 5
		],
		26 => [
			'name' => 'HDM',
			'id' => 6
		],
		999 => [
			'name' => 'Total',
			'id' => 7
		]
            ],
	    'migrations' => [
		1 => ['name' => 'Unescalated', 'id' => 1],
		140 => ['name' => 'Phase 1: Initial Sync', 'id' => 2],
		106 => ['name' => 'Phase 2: Client Site Check', 'id' => 3],
		119 => ['name' => 'Phase 3: Pending Re-Sync', 'id' => 4],
		136 => ['name' => 'Phase 3: Re-Sync In Progress', 'id' => 5],
		107 => ['name' => 'Phase 4: DNS Update', 'id' => 6],
		108 => ['name' => 'Complete','id' => 7],
		7 => ['name' => 'Total', 'id' => 8]
	    ]
	],
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
	    'support' => [
		'uri' => 'ticket',
		'data' => [
		    'filter[department]' => '1',
		    'filter[status]' => '1',
		    'pageIndex' => 0
		]
	    ],
	    'migrations' => [
		'uri' => 'ticket',
                'data' => [
                    'filter[department]' => '8',
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
