<?php 




require_once 'brain_tree/lib/Braintree.php';

include_once('../../../../wp-config.php');
include_once('../../../../wp-load.php');
include_once('../../../../wp-includes/wp-db.php');


$apis =  get_option('woocommerce_techprocess_settings');



global $woocommerce;



/***
 * 
 *   SAND BOX CODES
 */

Braintree_Configuration::environment('sandbox');
Braintree_Configuration::merchantId('2khgt8xv8t8hr995');
Braintree_Configuration::publicKey('rysztxtrr2bm2rt2');
Braintree_Configuration::privateKey('5c7ed602628f3b4203ff932bf47f8589');

$result = Braintree_Transaction::sale(array(
		'amount' => '1000.00',
		'creditCard' => array(
				'number' => '4111111111111111',
				'expirationMonth' => '05',
				'expirationYear' => '12'
		)
));


/*
 *
 */

/*

Braintree_Configuration::environment('production');
Braintree_Configuration::merchantId($apis['merchantid']);
Braintree_Configuration::publicKey($apis['publickey']);
Braintree_Configuration::privateKey($apis['privatekey']);

$result = Braintree_Transaction::sale(array(
    'amount' => $_POST['amount'],
	 'orderId' =>$_POST['order_id'],
   
    'creditCard' => array(
	'cardholderName' => $_POST['cardholderName'],
        'number' => $_POST['number'],
        'expirationMonth' => $_POST['month'],
        'expirationYear' => $_POST['year'],
        'cvv' => $_POST['cvv']
    ),
	'options' => array(
	    'submitForSettlement' => true
				  )
));
*/

if ($result->success) {
//	print_r($result);
	
    //print_r("success!: " . $result->transaction->id);
    $cust_ref = $_POST['customerReference'];
    
    $cust_ref = explode('-', $cust_ref);
    
    
 header("Location: /checkout/order-received/".$result->transaction->orderId."?key=".$cust_ref[1]."");
} else if ($result->transaction) {

	echo '<div style="text-align:center;font-size:30px; color:#000"> 
	 A Validation Error Has Occurred, Please Try Again <br>
	 <button onclick=" window.history.back()">Go Back</button>
	 </div>';
    print_r("Error processing transaction:");
    print_r("\n  message: " . $result->message);
    print_r("\n  code: " . $result->transaction->processorResponseCode);
    print_r("\n  text: " . $result->transaction->processorResponseText);

} else {
	
header("Location: /checkout/order-received/?status=fail");
	echo '<div style="text-align:center;font-size:30px; color:#000"> 
	 A Validation Error Has Occurred, Please Try Again <br>
	 <button onclick=" window.history.back()">TRY AGAIN</button>
	 </div>';
    print_r("Message: " . $result->message);
    print_r("\nValidation errors: \n");
    print_r($result->errors->deepAll());
}
