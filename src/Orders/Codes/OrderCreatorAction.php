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
use CodesWholesaleFramework\Model\InternalOrder;
use CodesWholesaleFramework\Postback\ReceivePreOrders\EventDispatcherInternalOrder;
use CodesWholesaleFramework\Visitor\VisitorInterface;
use CodesWholesaleFramework\Action;
use CodesWholesaleFramework\Errors\ErrorHandler;
use CodesWholesaleFramework\Orders\Utils\CodesProcessor;
use CodesWholesaleFramework\Orders\Utils\DataBaseExporter;
use CodesWholesaleFramework\Errors\Errors;
use CodesWholesaleFramework\Postback\Retriever\ItemRetriever;
use \CodesWholesale\Resource\ResourceError;

/**
 * Class OrderCreatorAction
 */
class OrderCreatorAction implements Action
{
    /**
     * @var DataBaseExporter
     */
    private $databaseExporter;

    /**
     * @var EventDispatcherInternalOrder
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

    /**
     * @var Errors
     */
    private $errorHandler;

    /**
     * @var VisitorInterface
     */
    private $internalOrderVisitor;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var InternalOrder
     */
    protected $internalOrder;

    /**
     * OrderCreatorAction constructor.
     *
     * @param VisitorInterface             $internalOrderVisitor
     * @param DataBaseExporter             $dataBaseExporter
     * @param EventDispatcherInternalOrder $eventDispatcher
     * @param ItemRetriever                $itemRetriever
     * @param ErrorHandler                 $sendErrorMail
     * @param ErrorHandler                 $sendCwErrorMail
     * @param CodesProcessor               $codesProcessor
     * @param Client                       $client
     */
    public function __construct(
        VisitorInterface $internalOrderVisitor,
        DataBaseExporter $dataBaseExporter,
        EventDispatcherInternalOrder $eventDispatcher,
        ItemRetriever $itemRetriever,
        ErrorHandler $sendErrorMail,
        ErrorHandler $sendCwErrorMail,
        CodesProcessor $codesProcessor,
        Client $client
    )
    {
        $this->internalOrderVisitor = $internalOrderVisitor;
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

    /**
     * @param InternalOrder $internalOrder
     */
    public function setInternalOrder(InternalOrder $internalOrder)
    {
        $this->internalOrder = $internalOrder;
    }

    /**
     * @return InternalOrder
     */
    public function getInternalOrder(): InternalOrder
    {
        return $this->internalOrder;
    }

    /**
     * @return bool
     */
    public function process()
    {
        $error = null;
        $item = null;
        $numberOfPreOrders = 0;

        $this->getInternalOrder()->accept($this->internalOrderVisitor);

        foreach ($this->getInternalOrder()->getItems() as $itemKey => $item) {

            try {

                $retrievedItems = $this->itemRetriever->retrieveItem([
                    'item' => $item,
                    'order' => $this->getInternalOrder()->getOrder()
                ]);

                $orderedCodes = $this->codesPurchaser->purchase($retrievedItems['cwProductId'], $retrievedItems['qty']);

                if($orderedCodes['numberOfPreOrders'] > 0) {
                    $numberOfPreOrders++;
                }

                $this->databaseExporter->export($item, $orderedCodes, $itemKey, $this->getInternalOrder()->getId());

            } catch (ResourceError $e) {
                $this->errorHandler->supportResourceError($this->getInternalOrder()->getOrder(), $e);
                $error = $e;
            } catch (\Exception $e) {
                $this->errorHandler->supportError($this->getInternalOrder()->getOrder(), $e);
                $error = $e;
            }
        }

        if($numberOfPreOrders > 0) {
            $this->codesProcessor->process($this->getInternalOrder(), $numberOfPreOrders, $error, $item);
        }

        $this->eventDispatcher->dispatch($this->getInternalOrder());

        return $error != null;
    }
}