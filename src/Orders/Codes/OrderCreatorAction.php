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
use CodesWholesale\Client;
use CodesWholesaleFramework\Action;
use CodesWholesaleFramework\Errors\ErrorHandler;
use CodesWholesaleFramework\Orders\Utils\CodesProcessor;
use CodesWholesaleFramework\Orders\Utils\DataBaseExporter;
use CodesWholesaleFramework\Errors\Errors;
use CodesWholesaleFramework\Orders\Utils\StatusService;
use CodesWholesaleFramework\Postback\ReceivePreOrders\EventDispatcher;
use CodesWholesaleFramework\Postback\Retriever\ItemRetriever;
use \CodesWholesale\Resource\ResourceError;

class OrderCreatorAction implements Action
{
    /**
     * @var StatusService
     */
    private $statusService;
    /**
     * @var DataBaseExporter
     */
    private $databaseExporter;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var PurchaseCode
     */
    private $codesPurchaser;
    /**
     * @var ItemRetriever
     */
    private $itemRetriever;
    /**
     * @var ErrorHandler
     */
    private $sendErrorMail;
    /**
     * @var ErrorHandler
     */
    private $sendCwErrorMail;
    /**
     * @var CodesProcessor
     */
    private $codesProcessor;

    private $status;

    private $client;

    /**
     * @var Errors
     */
    private $errorHandler;

    public function __construct(StatusService $statusService, DataBaseExporter $dataBaseExporter, EventDispatcher $eventDispatcher,
                                ItemRetriever $itemRetriever, ErrorHandler $sendErrorMail, ErrorHandler $sendCwErrorMail,
                                CodesProcessor $codesProcessor, Client $client)
    {
        $this->statusService = $statusService;
        $this->databaseExporter = $dataBaseExporter;
        $this->eventDispatcher = $eventDispatcher;
        $this->codesPurchaser = new PurchaseCode();
        $this->itemRetriever = $itemRetriever;
        $this->sendErrorMail = $sendErrorMail;
        $this->sendCwErrorMail = $sendCwErrorMail;
        $this->codesProcessor = $codesProcessor;
        $this->errorHandler = new Errors($this->sendErrorMail, $this->sendCwErrorMail);
        $this->client = $client;
    }

    public function setCurrentStatus($status)
    {
        $this->status = $status;
    }

    public function process()
    {
        $error = null;
        $numberOfPreOrders = 0;

        $orderDetails = $this->statusService->checkStatus($this->status);

        foreach ($orderDetails['orderedItems'] as $itemKey => $item) {

            try {

                $retrievedItems = $this->itemRetriever->retrieveItem([
                    'item' => $item,
                    'order' => $orderDetails['order']
                ]);

                $orderedCodes = $this->codesPurchaser->purchase($retrievedItems['cwProductId'], $retrievedItems['qty']);

                if($orderedCodes['numberOfPreOrders'] > 0) {
                    $numberOfPreOrders++;
                }

                $this->databaseExporter->export($item, $orderedCodes, $itemKey, $orderDetails['orderId']);

            } catch (ResourceError $e) {
                $this->errorHandler->supportResourceError($e, $orderDetails['order']);
                $error = $e;
            } catch (\Exception $e) {
                $this->errorHandler->supportError($e, $orderDetails['order']);
                $error = $e;
            }
        }

        if($numberOfPreOrders > 0) {
            $this->codesProcessor->process($orderDetails['order']);
        }

        $this->eventDispatcher->dispatchEvent($orderDetails);

        return $error != null;
    }
}