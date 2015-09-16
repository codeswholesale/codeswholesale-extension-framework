<?php

namespace CodesWholesaleFramework\Postback\UpdatePriceAndStock;
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
use CodesWholesaleFramework\PostBack\UpdatePriceAndStock\Utils\UpdatePriceAndStockInterface;
use CodesWholesaleFramework\Postback\UpdatePriceAndStock\SpreadCalculator;

class UpdatePriceAndStockAction implements Action
{
    /**
     * @var ProductUpdater
     */
    private $productUpdater;

    private $connection;

    private $cwProductId;

    private $spreadParams;

    private $spreadCalculator;


    public function __construct($productUpdater, $spreadParams)
    {
        $this->productUpdater = $productUpdater;

        $this->spreadParams = $spreadParams;

        $this->spreadCalculator = new SpreadCalculator();
    }

    public function process()
    {
        if ($this->cwProductId == null) {

            $request = file_get_contents('php://input');

            if (empty($request)) {

                die("No request data");
            }

            $cwProductId = $this->connection->receiveUpdatedProductId();

        }

        try {

            $product = \CodesWholesale\Resource\Product::get($cwProductId);

        } catch (\CodesWholesale\Resource\ResourceError $e) {

            die("Received product id: " . $cwProductId . " Error: " . $e->getMessage());
        }

        $quantity = $product->getStockQuantity();
        $price = $product->getLowestPrice();

        $priceSpread = $this->spreadCalculator->calculateSpread($this->spreadParams->getSpreadParams(), $price);

        $this->productUpdater->updateProduct($cwProductId, $quantity , $priceSpread);
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function setProductId($cwProductId)
    {
        $this->cwProductId = $cwProductId;
    }
}