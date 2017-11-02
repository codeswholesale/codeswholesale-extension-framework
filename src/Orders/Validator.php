<?php
/**
 * Created by PhpStorm.
 * User: maciejklowan
 * Date: 03/10/2016
 * Time: 15:42
 */

namespace CodesWholesaleFramework\Orders;

/**
 * Interface Validator
 */
interface Validator
{
    /**
     * @return mixed
     */
    public function validate();
}