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

class SendCodesAction implements Action
{
    /**
     * @var
     */
    private $orderDetails;
    /**
     * @var
     */
    private $sendCodeMail;
    /**
     * @var
     */
    private $setStatus;
    /**
     * @var
     */
    private $observerDispatcher;

    private $getLinks;


    public function __construct($observerDispatcher, $sendCodeMail, $setStatus, $getLinks)
    {
        $this->observerDispatcher = $observerDispatcher;
        $this->sendCodeMail = $sendCodeMail;
        $this->setStatus = $setStatus;
        $this->getLinks = $getLinks;
    }

    public function setOrderDetails($observer)
    {

        $this->orderDetails = $this->observerDispatcher->dispatchObserver($observer);
    }

    private function setCompleteStatus($orderId, $totalNumberOfKeys)
    {

        $this->setStatus->setStatus($orderId, $totalNumberOfKeys);
    }

    private function sendMail($order, $attachments, $keys, $totalPreOrders)
    {

        $this->sendCodeMail->sendCodeMail($order, $attachments, $keys, $totalPreOrders);
    }


    public function process()
    {
        $attachments = array();
        $totalNumberOfKeys = 0;
        $totalPreOrders = 0;
        $keys = array();

        $orderDetails = $this->orderDetails;

        foreach ($orderDetails['orderedItems'] as $item_key => $item) {

            $links = $this->getLinks->links($item, $item_key);

            $codes = array();

            if (!empty($links)) {

                foreach ($links as $link) {

                    $code = \CodesWholesale\Resource\Code::get($link);

                    if ($code->isImage()) {

                        $attachments[] = \CodesWholesale\Util\CodeImageWriter::write($code, 'Cw_Attachments');
                    }

                    if ($code->isPreOrder()) {

                        $totalPreOrders++;
                    }

                    $codes[] = $code;
                    $totalNumberOfKeys++;
                }

                $keys[] = array(
                    'item' => $item,
                    'codes' => $codes
                );
            }
        }

        $this->sendMail($orderDetails['order'], $attachments, $keys, $totalPreOrders);

        $this->setCompleteStatus($orderDetails['order'], $totalNumberOfKeys);

        $this->cleanAttach($attachments);
    }

    /**
     * @param $attachments
     */
    private function cleanAttach($attachments)
    {
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {

                unlink($attachment);
            }
        }
    }
}