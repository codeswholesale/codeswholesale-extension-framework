<?php

namespace CodesWholesaleFramework\Model;

use CodesWholesale\Resource\Product;

/**
 * Class ExternalProduct
 */
class ExternalProduct
{
    const DEFAULT_LANGUAGE = 'uk';

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var string
     */
    protected $description;

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return ExternalProduct
     */
    public function setProduct(Product $product): ExternalProduct
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return ExternalProduct
     */
    public function setDescription(string $description): ExternalProduct
    {
        $this->description = $description;

        return $this;
    }

    public function updateDescription(string $preferredLanguage): ExternalProduct
    {
        try{
            $product_description = $this->getProduct()->getProductDescription();

            $factSheets = $product_description->getFactSheets();

            $description        = "";
            $defaultDescription = "";

            /** @var \CodesWholesale\Resource\FactSheet $factSheet */
            foreach ($factSheets as $factSheet) {
                if ($preferredLanguage === $factSheet->getTerritory()) {
                    $description = $factSheet->getDescription();
                }
                if (self::DEFAULT_LANGUAGE === $factSheet->getTerritory()) {
                    $defaultDescription = $factSheet->getDescription();
                }
            }

            if ("" === $description) {
                $description = $defaultDescription;
            }

        } catch (\Exception $ex) {
            $description = "";
        }

        $this->setDescription($description);

        return $this;
    }
}