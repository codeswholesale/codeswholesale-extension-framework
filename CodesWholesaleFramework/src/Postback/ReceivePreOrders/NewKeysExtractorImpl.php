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
use CodesWholesaleFramework\Postback\Extractor\NewKeysExtractorInterface;

class NewKeysExtractorImpl implements NewKeysExtractorInterface
{
    /**
     * @param $observer
     * @return array
     */
    public function extract($observer){

        $item = $observer['item'];
        $codes = $observer['allCodesFromProduct'];
        $numberOfKeysSent = 0;

        $links = json_decode($item['links']);
        $numberOfPreOrders = $item['number_of_preorders'];

        file_put_contents('item.txt', print_r($item, true));
        $preOrdersToRemove = NewKeysExtractorImpl::getIndicesOfPreOrders($links, $codes);
        $newCodes = NewKeysExtractorImpl::getNewCodes($links, $codes);

        $attachments = array();
        $linksToAdd = array();

        foreach ($newCodes as $code) {

            if ($code->isImage()) {

                $attachments[] = \CodesWholesale\Util\CodeImageWriter::write($code, 'Cw_Attachments');
            }

            unset($links[$preOrdersToRemove[0]]);
            unset($preOrdersToRemove[0]);

            $preOrdersToRemove = array_values($preOrdersToRemove);
            $linksToAdd[] = $code->getHref();

            $numberOfKeysSent++;
        }

        $preOrdersLeft = ($numberOfPreOrders - $numberOfKeysSent);

        $total = (count($links) + 1);

        $keys[] = array(
            'item' => $item,
            'codes' => $newCodes,
            'preOrdersLeft' => $preOrdersLeft,
            'total' => $total,
            'linksToAdd' => $linksToAdd,
            'links' => $links,
            'attachments' => $attachments
        );

       return $keys;
    }

    /**
     * @param $links
     * @param $codes
     * @return array
     */
    private static function getIndicesOfPreOrders($links, $codes)
    {
        $indices = array();

        foreach ($links as $index => $link) {
            $isPreOrder = true;
            foreach ($codes as $code) {

                if ($link == $code->getHref()) {
                    $isPreOrder = false;
                }
            }

            if ($isPreOrder) {

                $indices[] = $index;
            }
        }
        return $indices;
    }

    /**
     * @param $links
     * @param $codes
     * @return array
     */
    private static function getNewCodes($links, $codes)
    {
        $newCodes = array();
        foreach ($codes as $code) {
            $isNew = true;
            foreach ($links as $link) {
                if ($link == $code->getHref()) {
                    $isNew = false;
                }
            }
            if ($isNew) {
                $newCodes[] = $code;
            }
        }
        return $newCodes;
    }
}