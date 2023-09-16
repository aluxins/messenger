<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

return [
    'default' => 'mysql',

    'connections' => [
        'mysql' => array(
            'driver'      => 'mysql',
            'host'        => empty($_ENV['DB_HOST'])?'localhost':$_ENV['DB_HOST'],
            'port'        => 3306,
            'database'    => empty($_ENV['DB_DATABASE'])?'test':$_ENV['DB_DATABASE'],
            'username'    => empty($_ENV['DB_USERNAME'])?'root':$_ENV['DB_USERNAME'],
            'password'    => empty($_ENV['DB_PASSWORD'])?'root':$_ENV['DB_PASSWORD'],
            'unix_socket' => '',
            'charset'     => 'utf8',
            'collation'   => 'utf8_unicode_ci',
            'prefix'      => '',
            'strict'      => true,
            'engine'      => null,
            'options' => array(
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
    ],
];
