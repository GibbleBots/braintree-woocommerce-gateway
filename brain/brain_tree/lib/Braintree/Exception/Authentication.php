<?php
/**
 * Raised when authentication fails.
 * This may be caused by an incorrect Braintree_Configuration
 *
 * @package    Braintree
 * @subpackage Exception
 * @copyright  2014 Braintree, a division of PayPal, Inc.
 */
 header("Location: /checkout/order-received/?status=fail&except=authentication_exception");
class Braintree_Exception_Authentication extends Braintree_Exception
{

}
