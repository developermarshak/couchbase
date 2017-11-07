<?php

return [
    'connections' => [
        'couchbase' => [
            'name'       => 'couchbase',
            'driver'     => 'couchbase',
            'port'       => '8091',
            'host'       => 'couchbase',
            'bucket'     => 'testbucket',
            'user'       => 'conci',
            'password'   => 'devpass',
            'n1ql_hosts' => ['http://couchbase:8093']
        ]
    ]
];
