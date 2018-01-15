<?php

namespace CodesWholesaleFramework\Postback\UpdateProduct;

/**
 * Interface UpdateProductInterface
 */
interface UpdateProductInterface
{
    public function updateProduct($cwProductId, $quantity , $priceSpread);

    public function hideProduct($cwProductId);

    public function newProduct($cwProductHref);
}