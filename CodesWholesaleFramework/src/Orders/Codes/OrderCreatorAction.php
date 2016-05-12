<?php
namespace CodesWholesaleFramework\Orders\Codes;

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
use CodesWholesaleFramework\Orders\Utils\OrderCreatorInterface;
use CodesWholesaleFramework\Orders\Codes\PurchaseCode;
use CodesWholesaleFramework\Errors\Errors;

class OrderCreatorAction implements Action
{
    /**
     * @var StatusChange
     */
    private $statusChange;
    /**
     * @var ExportToDataBase
     */
    private $exportToDataBase;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var PurchaseCode
     */
    private $purchaseCode;
    /**
     * @var ItemRetriever
     */
    private $itemRetriever;
    /**
     * @var ErrorHandler
     */
    private $sendErrorMail;
    /**
     * @var CwErrorHandler
     */
    private $sendCwErrorMail;
    /**
     * @var OrderValidation
     */
    private $orderValidation;
    /**
     * @var Connection
     */
    private $connection;

    private $observer;

    private $error;

    public function __construct($statusChange, $exportOrderToDataBase, $eventDispatcher, $itemRetriever, $sendErrorMail, $sendCwErrorMail, $orderValidation)
    {
        $this->statusChange = $statusChange;
        $this->exportToDataBase = $exportOrderToDataBase;
        $this->eventDispatcher = $eventDispatcher;
        $this->purchaseCode = new PurchaseCode();
        $this->itemRetriever = $itemRetriever;
        $this->sendErrorMail = $sendErrorMail;
        $this->sendCwErrorMail = $sendCwErrorMail;
        $this->orderValidation = $orderValidation;
        $this->error = new Errors($this->sendErrorMail, $this->sendCwErrorMail);
    }

    public function setCurrentStatus($observer)
    {
        $this->observer = $observer;
    }

    public function getCurrentStatus()
    {
        return $this->statusChange->checkStatus($this->observer);
    }

    public function retrieveItem($orderData)
    {
        return $this->itemRetriever->retrieveItem($orderData);
    }

    public function purchase($cwProductId, $qty)
    {
        $orderedCodes = $this->purchaseCode->purchase($cwProductId, $qty);
        return $orderedCodes;
    }

    public function exportToDB($item, $orderedCodes, $item_key)
    {
        $this->exportToDataBase->export($item, $orderedCodes, $item_key);
    }

    public function dispatchEvent($eventDataArray)
    {
        $this->eventDispatcher->dispatchEvent($eventDataArray);
    }

    public function validatePurchase($orderedCodes, $item, $orderDetails, $connection, $error)
    {
        return $this->orderValidation->validatePurchase($orderedCodes, $item, $orderDetails, $connection, $error);
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function process()
    {
        $error = null;

        $orderDetails = $this->getCurrentStatus();

        foreach ($orderDetails['orderedItems'] as $item_key => $item) {

            try {

                $mergedValues = array('item' => $item, 'order' => $orderDetails['order']);

                $retrievedItems = $this->retrieveItem($mergedValues);

                $orderedCodes = $this->purchase($retrievedItems['cwProductId'], $retrievedItems['qty']);

                $this->exportToDB($item, $orderedCodes, $item_key);

            } catch (\CodesWholesale\Resource\ResourceError $e) {

                $this->error->supportResourceError($e, $orderDetails['order']);
                $error = $e;

            } catch (\Exception $e) {

                $this->error->supportError($e, $orderDetails['order']);
                $error = $e;
            }
        }

        $eventDataArray = $this->validatePurchase($orderedCodes, $item, $orderDetails, $this->connection, $error);

        if (!$eventDataArray == null) {
            $this->dispatchEvent($eventDataArray);
        }
    }
}