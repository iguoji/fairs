<?php

return [
    'default'           =>  'redis',
    'pool'              =>  swoole_cpu_num() * 10,

    'file'              =>  [
        'path'          =>  '/tmp',
        'prefix'        =>  '',
        'expire'        =>  0,
        'serialize'     =>  'serialize',
        'data_compress' =>  false,
    ],

    'redis'             =>  [
        'host'          =>  env('cache.redis.host', '192.168.2.12'),
        'port'          =>  env('cache.redis.port', 6379),
        'expire'        =>  0,
        'select'        =>  0,
        'auth'          =>  ['123456'],
        'timeout'       =>  2.5,
        'options'       =>  [],
    ],
];