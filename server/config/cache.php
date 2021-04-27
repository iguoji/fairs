<?php

return [
    'host'      =>  env('cache.redis.host', '192.168.2.12'),
    'port'      =>  env('cache.redis.port', 6379),
    'timeout'   =>  2.5,
    'auth'      =>  ['123456'],
];