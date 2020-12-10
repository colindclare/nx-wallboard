<?php

return [

  'omni' => [
    'users' => [
      'table' => 'omni_users',
      'gateway' => 'https://wallboard.supportdev.liquidweb.com/api/data/agents'
    ],
    'queue' => [
      'table' => 'liveagent_queue',
      'gateway' => 'https://wallboard.supportdev.liquidweb.com/api/data/maps/chats'
    ]
  ],

  'pbx' => [
    'host' => '10.30.100.40',
    'port' => '5038',
    'username' => env('PBX_USERNAME'),
    'secret' => env('PBX_SECRET'),
    'queue_users_table' => 'pbx_queue_users',
    'queue_entries_table' => 'pbx_queue_entries',
    'queues_table' => 'pbx_queues'
  ],

  'adp' => [
    'token' => env('ADP_TOKEN'),
    'gateway' => 'https://schedules.liquidweb.com/api/v1',
    'schedule_table' => 'adp_schedule',
    'endpoints' => [
      'auth' => [
        'uri' => '/auth',
        'data' => [
          'username' => 'nxwallboard',
          'password' => env('ADP_PASSWORD'),
          'header' => 'Content-Type: application/x-www-form-urlencoded'
        ]
      ],
      'schedules' => [
        'uri' => '/adp/schedules',
        'data' => [
          'normal' => [
            'per_page' => 400,
            'start_date' => date("Y-m-d"),
            'end_date' => date("Y-m-d")
          ],
          'third' => [
            'per_page' => 400,
            'start_date' => date("Y-m-d"),
            'end_date' => date("Y-m-d", strtotime(' +1 day')),
            'end_time' => '07:00:00'
          ]
        ]
      ],
      'punches' => [
        'uri' => '/adp/punches',
        'data' => ''
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
        160 => [
          'name' => 'Tier 1 - Critical',
          'id' => 4
        ],
        162 => [
          'name' => 'Tier 2 - Critical',
          'id' => 5
        ],
        161 => [
          'name' => 'ESG - Critical',
          'id' => 6
        ],
        150 => [
          'name' => 'Worx/SysOps Pending',
          'id' => 7
        ],
        26 => [
          'name' => 'HDM',
          'id' => 8
        ],
        999 => [
          'name' => 'Total',
          'id' => 9
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
        137 => ['name' => 'Audit','id' => 8],
        7 => ['name' => 'Total', 'id' => 9]
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
      'support_hdm' => [
        'uri' => 'ticket',
        'data' => [
          'filter[department]' => '1',
          'filter[escalation]' => '26',
          'filter[status]' => '104',
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
