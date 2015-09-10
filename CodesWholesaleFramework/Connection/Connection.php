<?php
namespace CodesWholesaleFramework\Connection;

/**
 *   This file is part of codeswholesale-plugin-framework.
 *
 *   codeswholesale-plugin-framework is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   codeswholesale-plugin-framework is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with codeswholesale-plugin-framework; if not, write to the Free Software
 *   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

use CodesWholesale\CodesWholesale;
use CodesWholesale\ClientBuilder;
use fkooman\OAuth\Client\SessionStorage;

class Connection {

    const SANDBOX_CLIENT_ID = 'ff72ce315d1259e822f47d87d02d261e';
    const SANDBOX_CLIENT_SECRET = '$2a$10$E2jVWDADFA5gh6zlRVcrlOOX01Q/HJoT6hXuDMJxek.YEo.lkO2T6';

    private static $connection;

    public static function getConnection($options)
    {
        if (self::$connection === null) {
            $builder = new ClientBuilder(array(
                'cw.endpoint_uri' => $options['environment'] == 0 ? CodesWholesale::SANDBOX_ENDPOINT : 'http://app.localhost.com:8083',
                'cw.client_id' => $options['environment'] == 0 ? self::SANDBOX_CLIENT_ID : $options['client_id'],
                'cw.client_secret' => $options['environment'] == 0 ? self::SANDBOX_CLIENT_SECRET : $options['client_secret'],
                'cw.token_storage' => new SessionStorage()
            ));

            self::$connection = $builder->build();
        }
        return self::$connection;
    }

    public static function hasConnection() {
        return self::$connection != null;
    }
}