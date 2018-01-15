<?php

namespace CodesWholesaleFramework\Provider;

/**
 * Class CurrencyProvider
 */
class CurrencyProvider
{
    /**
     * @return mixed
     */
    public static function import()
    {
        $xml = self::getXML();

        return $xml->Cube->Cube->children();
    }

    /**
     * @param $currency
     *
     * @return array|null
     */
    public static function getRateByCurrencyName($currency)
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