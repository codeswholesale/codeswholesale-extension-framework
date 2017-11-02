<?php
/**
 * Created by PhpStorm.
 * User: maciejklowan
 * Date: 12/05/16
 * Time: 13:40
 */

namespace CodesWholesaleFramework\Exceptions;

/**
 * Class ForceUpdateException
 */
class ForceUpdateException extends \Exception
{
    /**
     * @var string
     */
   protected $message = 'Issue in ForceProductUpdater';
}