<?php

use Imi\App;
use function Imi\env;

$mode = App::isInited() ? App::getApp()->getType() : null;

return [
    // 项目根命名空间
    'namespace' => 'ImiApp',

    // 配置文件
    'configs' => [
        'beans' => __DIR__ . '/beans.php',
        'website' => __DIR__ . '/website.php',
    ],

    'caches' => [
        'redis' => [
            // 缓存驱动类
            'handlerClass' => \Imi\Cache\Handler\Redis::class,
            // 驱动实例配置
            'option' => [
                'poolName' => '',
                'prefix' => '', // 缓存键前缀
                'formatHandlerClass' => \Imi\Util\Format\Json::class, // 数据读写修改器
                'replaceDot' => false,
            ],
        ],
    ],

    'components' => [
        // 引入本组件
        'jwt' => 'Imi\JWT',
    ],

    'ignoreNamespace' => [
    ],

    'ignorePaths' => [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'imi-ui',
    ],

    // Swoole 主服务器配置
    'mainServer' => 'swoole' === $mode ? [
        'namespace' => 'ImiApp\ApiServer',
        'type' => Imi\Swoole\Server\Type::HTTP,
        'host' => '0.0.0.0',
        'port' => 8080,
        'configs' => [
            // 'worker_num'        =>  8,
            // 'task_worker_num'   =>  16,
        ],
    ] : [],

    // Swoole 子服务器（端口监听）配置
    'subServers' => 'swoole' === $mode ? [
        // 'SubServerName'   =>  [
        //     'namespace'    =>    'ImiApp\XXXServer',
        //     'type'        =>    Imi\Server\Type::HTTP,
        //     'host'        =>    '0.0.0.0',
        //     'port'        =>    13005,
        // ]
    ] : [],

    // Workerman 服务器配置
    'workermanServer' => 'workerman' === $mode ? [
        'http' => [
            'namespace' => 'ImiApp\ApiServer',
            'type' => Imi\Workerman\Server\Type::HTTP,
            'host' => '0.0.0.0',
            'port' => 8080,
            'configs' => [
            ],
        ],
    ] : [],

    // fpm 服务器配置
    'fpm' => 'fpm' === $mode ? [
        'serverPath' => dirname(__DIR__) . '/ApiServer',
    ] : [],

    // 连接池配置
    'pools' => 'swoole' === $mode ? [
        // 主数据库
        'maindb' => [
            'pool' => [
                'class' => \Imi\Swoole\Db\Pool\CoroutineDbPool::class,
                'config' => [
                    'maxResources' => 10,
                    'minResources' => 1,
                ],
            ],
            'resource' => [
                'host' => env("MYSQL_HOST"),
                'port' => env("MYSQL_PORT"),
                'username' => env("MYSQL_USERNAME"),
                'password' => (string)env("MYSQL_PASSWORD"),
                'database' => env("MYSQL_DATABASE"),
                'charset' => 'utf8mb4',
            ],
        ],
        'redis' => [
            'pool' => [
                'class' => \Imi\Swoole\Redis\Pool\CoroutineRedisPool::class,
                'config' => [
                    'maxResources' => 10,
                    'minResources' => 1,
                ],
            ],
            'resource' => [
                'host' => env("REDIS_HOST"),
                'port' => (int)env("REDIS_PORT"),
                'password' => env("REDIS_PASS") ?: null,
            ],
        ],
    ] : [],

    // 数据库配置
    'db' => [
        // 数默认连接池名
        'defaultPool' => 'maindb',
        // FPM、Workerman 下用
        'connections' => [
            'maindb' => [
                'host' => '127.0.0.1',
                'port' => 3306,
                'username' => 'root',
                'password' => 'root',
                'database' => 'mysql',
                'charset' => 'utf8mb4',
                // 'port'    => '3306',
                // 'timeout' => '建立连接超时时间',
                // 'charset' => '',
                // 使用 hook pdo 驱动（缺省默认）
                // 'dbClass' => \Imi\Db\Drivers\PdoMysql\Driver::class,
                // 使用 hook mysqli 驱动
                // 'dbClass' => \Imi\Db\Drivers\Mysqli\Driver::class,
                // 使用 Swoole MySQL 驱动
                // 'dbClass' => \Imi\Swoole\Db\Drivers\Swoole\Driver::class,
                // 数据库连接后，执行初始化的 SQL
                // 'sqls' => [
                //     'select 1',
                //     'select 2',
                // ],
            ],
        ],
    ],

    // redis 配置
    'redis' => [
        // 数默认连接池名
        'defaultPool' => 'redis',
        // FPM、Workerman 下用
        'connections' => [
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                // 是否自动序列化变量
                'serialize' => true,
                // 密码
                'password' => null,
                // 第几个库
                'db' => 0,
            ],
        ],
    ],

    // 内存表配置
    'memoryTable' => [
        // 't1'    =>  [
        //     'columns'   =>  [
        //         ['name' => 'name', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 16],
        //         ['name' => 'quantity', 'type' => \Swoole\Table::TYPE_INT],
        //     ],
        //     'lockId'    =>  'atomic',
        // ],
    ],

    // 锁
    'lock' => [
        // 'list'  =>  [
        //     'atomic' =>  [
        //         'class' =>  'AtomicLock',
        //         'options'   =>  [
        //             'atomicName'    =>  'atomicLock',
        //         ],
        //     ],
        // ],
    ],

    // atmoic 配置
    'atomics' => [
        // 'atomicLock'   =>  1,
    ],

    // 日志配置
    'logger' => [
        'channels' => [
            'imi' => [
                'handlers' => 'cli' === PHP_SAPI ? [
                    [
                        'class' => \Imi\Log\Handler\ConsoleHandler::class,
                        'formatter' => [
                            'class' => \Imi\Log\Formatter\ConsoleLineFormatter::class,
                            'construct' => [
                                'format' => null,
                                'dateFormat' => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks' => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                    [
                        'class' => \Monolog\Handler\RotatingFileHandler::class,
                        'construct' => [
                            'filename' => dirname(__DIR__) . '/.runtime/logs/log.log',
                        ],
                        'formatter' => [
                            'class' => \Monolog\Formatter\LineFormatter::class,
                            'construct' => [
                                'dateFormat' => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks' => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                ] : [
                    [
                        'class' => \Monolog\Handler\RotatingFileHandler::class,
                        'construct' => [
                            'filename' => dirname(__DIR__) . '/.runtime/logs/log.log',
                        ],
                        'formatter' => [
                            'class' => \Monolog\Formatter\LineFormatter::class,
                            'construct' => [
                                'dateFormat' => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks' => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

];
