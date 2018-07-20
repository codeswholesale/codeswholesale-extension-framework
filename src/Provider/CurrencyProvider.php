<?php

namespace CodesWholesaleFramework\Provider;

/**
 * Class CurrencyProvider
 */
class CurrencyProvider
{ 
    const API = 'https://free.currencyconverterapi.com/api/v5';
    const MAX_REQUEST = 3;
    const REQUEST_SLEEP_TIME = 5;

    private static $lastUsedCurrency = '';
    private static $lastUsedRate = '';

    private static $requestNumber = 1;

    /**
     * @param string $default
     * @return array
     */
    public static function getAllCurrencies($default = 'EUR')
    {
        $content = @file_get_contents(self::API . "/currencies");

        if (!$content) {
            $currency = [
                'currencyName' => $default,
                'id'  => $default
            ];

            return  [ (object) $currency ];
        }

        $result  = json_decode($content);

        return $result->results;
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public static function setRate($id) {
        if('EUR' === $id) {
            self::$lastUsedCurrency = $id;
            self::$lastUsedRate = 1;
        } else {
            self::convert($id);
        }
    }

    /**
     * @param $id
     * @return string
     * @throws \Exception
     */
    public static function getRate($id)
    {
        if(!$id || self::$lastUsedCurrency !== $id) {
            self::setRate($id);
        }

        return self::$lastUsedRate;
    }

    /**
     *
     * @param type $result
     * @param type $convert
     * @return boolean
     */
    public static function issetRate($result, $convert) {
        return
            isset($result->results) &&
            isset($result->results->$convert->val) &&
            $result->results->$convert->val > 0;
    }

    /**
     * @param $id
     * @throws \Exception
     */
    private static function convert($id) {
        $convert = "EUR_" . $id;

        $content = @file_get_contents(self::API . "/convert?q=" . $convert);

        $result  = json_decode($content);

        if($id && self::$requestNumber <= 3) {
            if(self::issetRate($result, $convert)) {
                self::$lastUsedCurrency = $id;
                self::$lastUsedRate = $result->results->$convert->val;
            } else {
                self::$requestNumber++;
                sleep(self::REQUEST_SLEEP_TIME);
                self::convert($id);
            }
        } else {
            throw new \Exception("Currency provider is not responding.");
        }
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
