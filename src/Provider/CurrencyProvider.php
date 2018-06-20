<?php

namespace CodesWholesaleFramework\Provider;

/**
 * Class CurrencyProvider
 */
class CurrencyProvider
{ 
    const API = 'https://free.currencyconverterapi.com/api/v5';
       
    private static $lastUsedCurrency = '';
    private static $lastUsedRate = '';
    
    public static function getAllCurrencies() 
    {    
        $content = file_get_contents(self::API . "/currencies");

        $result  = json_decode($content);
        
        return $result->results;         
    }
    
    public static function getRate($id) 
    {
        if(self::$lastUsedCurrency !== $id) {
            $convert = "EUR_" . $id;

            $content = file_get_contents(self::API . "/convert?q=" . $convert);

            $result  = json_decode($content);  
            
            self::$lastUsedCurrency = $id;
            self::$lastUsedRate = $result->results->$convert->val;  
        } 
       
        return self::$lastUsedRate;      
    }
    
    /**
     * @return mixed
     */
    public static function importXml()
    {
        $xml = self::getXML();

        return $xml->Cube->Cube->children();
    }

    /**
     * @param $currency
     *
     * @return array|null
     */
    public static function getXmlRateByCurrencyName($currency)
    {
        if ('EUR' === $currency) {

            return [1];

        } else {

            $rates = self::import();

            foreach ($rates as $rate) {
                if ($currency == $rate->attributes()->currency) {
                    return (array) floatval($rate->attributes()->rate);
                }
            }
        }
    }

    public static function getXML()
    {
        return simplexml_load_file('http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
    }
}
