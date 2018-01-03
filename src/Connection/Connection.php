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
use CodesWholesale\Client;
use CodesWholesale\Resource\AssignedPreOrder;
use CodesWholesale\Resource\Notification;
use CodesWholesale\Resource\StockAndPriceChange;
use CodesWholesale\Resource\ProductNotification;
use CodesWholesale\Storage\TokenDatabaseStorage;
use CodesWholesale\Storage\TokenSessionStorage;
use CodesWholesaleFramework\Postback\UpdateOrder\UpdateOrderInterface;
use CodesWholesaleFramework\Postback\UpdateProduct\UpdateProductInterface;

/**
 * Class Connection
 */
class Connection
{
    const SANDBOX_CLIENT_ID = 'ff72ce315d1259e822f47d87d02d261e';
    const SANDBOX_CLIENT_SECRET = '$2a$10$E2jVWDADFA5gh6zlRVcrlOOX01Q/HJoT6hXuDMJxek.YEo.lkO2T6';
    const SANDBOX_SIGNATURE = 'test_signature';

    /**
     * @var Client|null
     */
    private static $connection;

    /**
     * @param array                       $options
     * @param UpdateProductInterface|null $productUpdater
     * @param UpdateOrderInterface|null   $orderUpdater
     *
     * @return Client
     */
    public static function getConnection(
        array $options,
        UpdateProductInterface $productUpdater = null,
        UpdateOrderInterface $orderUpdater = null
    ): Client
    {
        if (self::$connection === null) {
            $builder = new ClientBuilder([
                'cw.endpoint_uri' => $options['environment'] == 0 ? CodesWholesale::SANDBOX_ENDPOINT : CodesWholesale::LIVE_ENDPOINT,
                'cw.client_id' => $options['environment'] == 0 ? self::SANDBOX_CLIENT_ID : $options['client_id'],
                'cw.client_secret' => $options['environment'] == 0 ? self::SANDBOX_CLIENT_SECRET : $options['client_secret'],
                'cw.token_storage' => isset($options['db']) && $options['db'] instanceof \PDO ? new TokenDatabaseStorage($options['db'], $options['prefix']) : new TokenSessionStorage(),
                'cw.client.headers' => [
                    'User-Agent' => $options['client_headers'],
                ]
            ]);

            self::$connection = $builder->build();

            if ($productUpdater instanceof UpdateProductInterface) {
                self::updateProduct($productUpdater);
            }

            if ($orderUpdater instanceof UpdateOrderInterface) {
                self::updateOrder($orderUpdater);
            }

            self::$connection->handle(array_key_exists('signature', $options) && $options['signature'] != null ? $options['signature'] : self::SANDBOX_SIGNATURE);
        }
        return self::$connection;
    }

    /**
     * @param UpdateProductInterface $productUpdater
     */
    private static function updateProduct(UpdateProductInterface $productUpdater)
    {
        self::$connection->registerStockAndPriceChangeHandler(function(array $stockAndPriceChanges) use($productUpdater) {

            /** @var StockAndPriceChange $stockAndPriceChange */
            foreach ($stockAndPriceChanges as $stockAndPriceChange) {

                $productUpdater->updateProduct(
                    $stockAndPriceChange->getProductId(),
                    $stockAndPriceChange->getQuantity(),
                    $stockAndPriceChange->getPrice()
                );
            }
        });

        self::$connection->registerHidingProductHandler(function(Notification $notification) use($productUpdater) {
            $productUpdater->hideProduct($notification->getProductId());
        });

        self::$connection->registerNewProductHandler(function(ProductNotification $notification) use($productUpdater) {
            $productUpdater->newProduct($notification->getProductHref());
        });
    }

    /**
     * @param UpdateOrderInterface $orderUpdater
     */
    private static function updateOrder(UpdateOrderInterface $orderUpdater)
    {
        self::$connection->registerPreOrderAssignedHandler(function(AssignedPreOrder $notification) use($orderUpdater) {
            $orderUpdater->preOrderAssigned($notification->getCodeId());
        });
    }

    /**
     * @return bool
     */
    public static function hasConnection(): bool{
        return self::$connection != null;
    }
}