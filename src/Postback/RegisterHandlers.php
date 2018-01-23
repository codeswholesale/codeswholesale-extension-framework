<?php
namespace CodesWholesaleFramework\Postback;

use CodesWholesale\Client;
use CodesWholesaleFramework\Postback\UpdateOrder\UpdateOrderInterface;
use CodesWholesaleFramework\Postback\UpdateProduct\UpdateProductInterface;
use CodesWholesale\Resource\AssignedPreOrder;
use CodesWholesale\Resource\Notification;
use CodesWholesale\Resource\StockAndPriceChange;

class RegisterHandlers {
    const SANDBOX_SIGNATURE = 'test_signature';
    
    private $client;
    private $signature;
    private $productUpdater;
    private $orderUpdater;
            
    public function __construct(Client $client, String $signature, $environment) 
    {  
        $this->client = $client;
        $this->signature= $environment == 0 ? self::SANDBOX_SIGNATURE : $signature;
    }
    
    public function setProductUpdater(UpdateProductInterface $productUpdater = null) 
    {
        $this->productUpdater = $productUpdater;
    }
    
    public function setOrderUpdater(UpdateOrderInterface $orderUpdater = null) 
    {
        $this->orderUpdater = $orderUpdater;
    }
    
    public function process() 
    {
        if ($this->productUpdater instanceof UpdateProductInterface) {
            $this->registerProductHandlers();
        }

        if ($this->orderUpdater instanceof UpdateOrderInterface) {
            $this->registerOrderHandlers();
        }

        $this->client->handle($this->signature);
    }
    
        /**
     * @param UpdateProductInterface $productUpdater
     */
    private function registerProductHandlers()
    {
         $this->client->registerStockAndPriceChangeHandler(function(array $stockAndPriceChanges) {

            /** @var StockAndPriceChange $stockAndPriceChange */
            foreach ($stockAndPriceChanges as $stockAndPriceChange) {
                $this->productUpdater->updateProduct(
                    $stockAndPriceChange->getProductId(),
                    $stockAndPriceChange->getQuantity(),
                    $this->getPrice($stockAndPriceChange)
                );
            }
        });

         $this->client->registerUpdateProductHandler(function (Notification $notification) {
            $this->productUpdater->updateProduct($notification->getProductId());
        });

         $this->client->registerHidingProductHandler(function(Notification $notification) {
            $this->productUpdater->hideProduct($notification->getProductId());
        });

         $this->client->registerNewProductHandler(function(Notification $notification) {
            $this->productUpdater->newProduct($notification->getProductId());
        });
    }
    
        /**
     * @param UpdateOrderInterface $orderUpdater
     */
    private function registerOrderHandlers()
    {
         $this->client->registerPreOrderAssignedHandler(function(AssignedPreOrder $notification) {
            $this->orderUpdater->preOrderAssigned($notification->getCodeId());
        });
    }
    
    private function getPrice($stockAndPriceChange) 
    {
        $productPrice = null;
        
        foreach ($stockAndPriceChange->getPrices() as $price) {
            if('100+' == $price->getRange()) {
                $productPrice = $price->getValue();
            }
        }

        if (null === $productPrice) {
            $productPrice = $stockAndPriceChange->getPrices()[0]->getValue();
        }
        
        return $productPrice;
    }
}

