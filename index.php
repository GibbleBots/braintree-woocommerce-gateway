<?php  
/*
Plugin Name: Brain Tree Plugin
Plugin URI: http://wordpress.org/plugins/wooCommerce-custom-payment-gateways/
Description: Add Custom Payment Gateways for WooCommerce.
Version: 1.0.0
Author: Object 90
Author URI: http://shamokaldarpon.com/
License: GPLv2
*/





add_action( 'plugins_loaded', 'your_gateway_class_name_init', 0 );
 
function your_gateway_class_name_init() {
if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
};

DEFINE ('PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );
class WC_techprocess extends WC_Payment_Gateway {
		/**
		 * Constructor for the gateway.
		 *
		 * @access public
		 * @return void
		 */
public function __construct() {
	
			global $woocommerce;
			$this->id			= 'techprocess';
			$this->method_title = __( 'BrainTree', 'woocommerce' );
			$this->has_fields	 = false;
			//$this->icon		 = apply_filters( 'woocommerce_techprocess_icon', $woocommerce->plugin_url() . '/assets/images/icons/techprocess.png' );
			
			
	// Load the form fields.
			$this->init_form_fields();
			
			// Load the settings.
			$this->init_settings();
			
// Define user set variables
			$this->title				 = $this->get_option('title');
			$this->description			 = $this->get_option('description');
			$this->gatewayurl			 = $this->get_option('gatewayurl');
			$this->responseurl			 = $this->get_option('responseurl');
			$this->responseurl			 = $this->get_option('merchantid');
			$this->responseurl			 = $this->get_option('publickey');
			$this->responseurl			 = $this->get_option('privatekey');
			
			

			add_action('woocommerce_api_wc_techprocess', array($this, 'check_response' ) );
			add_action('woocommerce_receipt_techprocess', array(&$this, 'receipt_page'));
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			if ( !$this->is_valid_for_use() ) $this->enabled = false;
		}
function check_response()
		{
			if (isset($_POST['customerReference']) && isset($_POST['responseCode'])) :
				@ob_clean();
				$_POST = stripslashes_deep($_POST);
				if (!empty($_POST['customerReference']) && !empty($_POST['responseCode'])) :
					header('HTTP/1.1 200 OK');
					$this->successful_request($_POST);
				else :
					wp_die("Request Failure");
				   endif;
			   endif;
		}
		
function successful_request( $posted ) {
	
	//print_r($posted);
			global $woocommerce;
			$order_id_key=$posted['customerReference'];
			$order_id_key=explode("-",$order_id_key);
			$order_id=$order_id_key[0];
			$order_key=$order_id_key[1];
			$responseCode=$posted['responseCode'];
			$responseText=$posted['responseText'];
			$txnreference=$posted['txnreference'];

			$order = new WC_Order( $order_id );
		   
			if ( $order->order_key !== $order_key ) :
				echo 'Error: Order Key does not match invoice.';
				exit;
			endif;

			if ( $order->get_total() != $posted['amount'] ) {
				echo 'Error: Amount not match.';
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: BrainTree amounts do not match (%s).', 'woocommerce' ), $posted['amount'] ) );
				exit;
			}

			// if TXN is approved
			if($responseCode=="00" || $responseCode=="08" || $responseCode=="77")
			{
				// Payment completed
				$order->add_order_note( __('payment completed', 'woocommerce') );

				// Mark order complete
				$order->payment_complete();

				  // Empty cart and clear session
				$woocommerce->cart->empty_cart();

				// Redirect to thank you URL
				wp_redirect( $this->get_return_url( $order ) );
				exit;
			}
			else // TXN has declined
			{	   
				// Change the status to pending / unpaid
				$order->update_status('pending', __('Payment declined', 'woothemes'));
			   
				// Add a note with the IPG details on it
				$order->add_order_note(__('Braintree payment Failed - TransactionReference: ' . $txnreference . " - ResponseCode: " .$responseCode, 'woocommerce')); // FAILURE NOTE
			   
				// Add error for the customer when we return back to the cart
				$woocommerce->add_error(__('TRANSACTION DECLINED: ', 'woothemes') . $posted['responseText'] . "<br/>Reference: " . $txnreference);
			   
				// Redirect back to the last step in the checkout process
				wp_redirect( $woocommerce->cart->get_checkout_url());
				exit;
			}

		}

		/**
		 * Check if this gateway is enabled and available in the user's country
		 *
		 * @access public
		 * @return bool
		 */
		function is_valid_for_use() {
			if (!in_array(get_woocommerce_currency(), array('USD'))) return false;
			return true;

		}

		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * @since 1.0.0
		 */
		public function admin_options() {
			?>
			<h3><?php _e('Braintree', 'woocommerce'); ?></h3>	   
			<table class="form-table">
			<?php
				if ( $this->is_valid_for_use() ) :
					// Generate the HTML For the settings form
					$this->generate_settings_html();
				else :
					?>
						<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'Braintree does not support your store currency.', 'woocommerce' ); ?></p></div>
					<?php
				endif;
			?>
			</table><!--/.form-table-->
			<?php
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields()
		{
			global $woocommerce;
			$order = new WC_Order( $order_id );
			$this->form_fields = array(
				'enabled' => array
							(
								'title' => __( 'Enable/Disable', 'woocommerce' ),
								'type' => 'checkbox',
								'label' => __( 'Enable Braintree', 'woocommerce' ),
								'default' => 'yes'
							),
				'title' => array
							(
								'title' => __( 'Title', 'woocommerce' ),
								'type' => 'text',
								'description' => __( 'This is the title the customer can see when checking out', 'woocommerce' ),
								'default' => __( 'Braintree', 'woocommerce' )
							),
				'description' => array
							(
								'title' => __( 'Description', 'woocommerce' ),
								'type' => 'text',
								'description' => __( 'This is the description the customer can see when checking out', 'woocommerce' ),
								'default' => __("Pay with Credit Card via Braintree", 'woocommerce')
							),
				'responseurl' => array
							(
								'title' => __( 'Response URL', 'woocommerce' ),
								'type' => 'textarea',
								'description' => __( 'This is the URL which needs to be configured into the Merchant Administration Console - Response URL', 'woocommerce' ),
								'default' => __(home_url() . "/?wc-api=WC_techprocess" , 'woocommerce')
							),
				'gatewayurl' => array
							(
								'title' => __( 'Gateway URL', 'woocommerce' ),
								'type' => 'textarea',
								'description' => __( 'This is obtained through the Merchant Administration Console - Gateway URL', 'woocommerce' ),
								'default' => ''
							),
							
							'merchantid' => array
							(
								'title' => __( 'Merchant ID', 'woocommerce' ),
								'type' => 'textarea',
								'description' => __( 'This can be obtained from braintree API panel', 'woocommerce' ),
								'default' => ''
							),

							'publickey' => array
							(
								'title' => __( 'Public Key', 'woocommerce' ),
								'type' => 'textarea',
								'description' => __( 'This can be obtained from braintree API panel', 'woocommerce' ),
								'default' => ''
							),
							
							'privatekey' => array
							(
								'title' => __( 'Private Key', 'woocommerce' ),
								'type' => 'textarea',
								'description' => __( 'This can be obtained from braintree API panel', 'woocommerce' ),
								'default' => ''
							)
							
				);
		}

		/**
		 * Get techprocess Args
		 *
		 * @access public
		 * @param mixed $order
		 * @return array
		 */
		function get_techprocess_args( $order )
		{
			global $woocommerce;

			$order_id = $order->id;
			$data = array();			
			$data['customerReference'] = $order_id.'-'.$order->order_key;
			$data['description'] = "Payment for order id ".$order_id;
			$data['email'] = $order->billing_email;
			$data['INVNUM'] = $order_id;
			$data['amount'] = number_format($order->get_total(), 2, '.', '');	
			$data['gatewayurl'] = $this->gatewayurl;
			return $data;
		}

		/**
		 * Process the payment and return the result
		 *
		 * @access public
		 * @param int $order_id
		 * @return array
		 */
		function process_payment( $order_id )
		{
			$order = new WC_Order( $order_id );
			$techprocess_args = $this->get_techprocess_args( $order );		   
			return array
			(
				'result'	 => 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);
		}

		function receipt_page( $order )
		{
			echo '<p>'.__('Thank you for your order. You will now enter your credit card information into our secure system.', 'woocommerce').'</p>';
			echo $this->generate_techprocess_form( $order );
		}

		function generate_techprocess_form( $order_id )
		{
			global $woocommerce;
			$order = new WC_Order( $order_id );
			$techprocess_args = $this->get_techprocess_args( $order );
			$woocommerce->add_inline_js('

			jQuery("body").block({

					message: "'.__('Thank you for your orderssss. You will now enter your credit card information into our secure system.', 'woocommerce').'",
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},

					css: {
						padding:		20,
						textAlign:	  "center",
						color:		  "#555",
						border:		 "3px solid #aaa",
						backgroundColor:"#fff",
						cursor:		 "wait",
						lineHeight:		"32px"
					}
				});

			jQuery("#submit_techprocess_payment_form").click();
		');

			$return='<form action="'.esc_url( $techprocess_args['gatewayurl'] ).'" method="post" id="techprocess_payment_form" target="_top">';
			foreach ($techprocess_args as $key => $value) {
				if($key!='gatewayurl')
					$return .= '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}
			$return .='<input type="submit" id="submit_techprocess_payment_form" value="submit"/></form>';
			return $return;
		}
	}

	function woocommerce_techprocess_add_gateway( $methods )
	{
		$methods[] = 'WC_techprocess';
		return $methods;

	}

	add_filter( 'woocommerce_payment_gateways', 'woocommerce_techprocess_add_gateway' );
}
	
		
			
			
