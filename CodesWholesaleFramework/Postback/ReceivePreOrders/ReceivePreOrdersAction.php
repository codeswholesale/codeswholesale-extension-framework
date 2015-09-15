<?php

namespace CodesWholesaleFramework\Postback\ReceivePreOrders;
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
use CodesWholesaleFramework\Action;

class ReceivePreOrdersAction implements Action
{
    /**
     * @var ItemRetriever
     */
    private $itemRetriever;

    /**
     * @var NewKeysExtractorImpl
     */
    private $newKeysExtractor;

    private $eventDispatcher;

    private $connection;

    /**
     * @param $itemRetriever
     * @param $eventDispatcher
     */
    function __construct($itemRetriever, $eventDispatcher)
    {
        $this->itemRetriever = $itemRetriever;
        $this->eventDispatcher = $eventDispatcher;
        $this->newKeysExtractor = new NewKeysExtractorImpl();
    }

    public function process()
    {
        $request = file_get_contents('php://input');

        if (empty($request)) {

            die("No request data");
        }

        try {

            $productOrdered = $this->connection->receiveProductOrdered();

            $allCodesFromProduct = \CodesWholesale\Resource\Order::getCodes($productOrdered);

            $orderId = $productOrdered->getOrderId();

            $item = $this->itemRetriever->retrieveItem($orderId);

            $params = array('item' => $item, 'allCodesFromProduct' => $allCodesFromProduct);

            $newKeys = $this->newKeysExtractor->extract($params);

            $this->eventDispatcher->dispatchEvent($newKeys);

        } catch (\Exception $e) {

            die('We found error. Probably this is the result of sending test POSTBACK. If your response status is: 200 OK
             it means that you are successfully connected. Error: ' . $e->getMessage());

        }
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }
}