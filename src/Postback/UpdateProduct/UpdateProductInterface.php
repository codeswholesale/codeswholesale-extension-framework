<?php

namespace CodesWholesaleFramework\Postback\UpdateProduct;

/**
 * Interface UpdateProductInterface
 */
interface UpdateProductInterface
{
    public function updateProduct($cwProductId, $quantity = null, $priceSpread = null);

    public function hideProduct($cwProductId);

    public function newProduct($cwProductId);
}