<?php

/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + secipay gateway
 */
function wc_secipay_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Gateway_Secipay';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_secipay_add_to_gateways' );
/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_secipay_gateway_plugin_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=secipay_gateway' ) . '">' . __( 'Configure', 'wc-gateway-secipay' ) . '</a>'
	);
	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_secipay_gateway_plugin_links' );
/**
 * Secipay Payment Gateway
 *
 * Provides an Secipay Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Gateway_Secipay
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Seci Team
 */
add_action( 'plugins_loaded', 'wc_secipay_gateway_init', 11 );

function wc_secipay_gateway_init() {

	class WC_Gateway_Secipay extends WC_Payment_Gateway {
		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'secipay_gateway';
			$this->icon               = apply_filters('woocommerce_secipay_icon', '');
			$this->has_fields         = false;
			$this->method_title       = __( 'Secipay', 'wc-gateway-secipay' );
			$this->method_description = __( 'Allows secipay payments. Orders are marked as "on-hold" when received and "completed" after X number of confirmations', 'wc-gateway-secipay' );
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->description  = $this->get_option( 'description' );
			$this->exchange_rate  = $this->get_option( 'exchange_rate' );
			$this->txid_confirmations  = $this->get_option( 'txid_confirmations' );
			$this->rpc_server  = $this->get_option( 'rpc_server' );
			$this->rpc_username  = $this->get_option( 'rpc_username' );
			$this->rpc_password  = $this->get_option( 'rpc_password' );
			$this->rpc_port  = $this->get_option( 'rpc_port' );
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

			//Filter
			add_filter('woocommerce_thankyou_order_received_text', array( $this,'order_received_output'), 10, 2 );
			add_filter( 'the_title', array( $this,'change_title_order_received'), 10, 2 );
		}
	
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wc_secipay_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'wc-gateway-secipay' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Secipay Payment', 'wc-gateway-secipay' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'wc-gateway-secipay' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-secipay' ),
					'default'     => __( 'Secipay Payment', 'wc-gateway-secipay' ),
					'desc_tip'    => true,
				),
				'description'  => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => __( 'Pay with SECI', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'exchange_rate' => array(
					'title'       => __( 'Exchange Rate', 'wc-gateway-secipay' ),
					'type'        => 'text',
					'description' => __( 'Manual Exchange rate Example (1000)', 'wc-gateway-secipay' ),
					'default'     => __( '1', 'wc-gateway-secipay' ),
					'desc_tip'    => true,
				),
				'txid_confirmations' => array(
					'title'       => __( 'Confirmations', 'wc-gateway-secipay' ),
					'type'        => 'number',
					'description' => __( 'This is how many confirmations a transaction needs before being marked as complete', 'wc-gateway-secipay' ),
					'default'     => __( '6', 'wc-gateway-secipay' ),
					'desc_tip'    => true,
				),
				'rpc_server' => array(
					'title'       => __( 'Coin RPC Server', 'wc-gateway-secipay' ),
					'type'        => 'text',
					'description' => __( 'Server Address of Coind rpc server', 'wc-gateway-secipay' ),
					'default'     => __( '127.0.0.1', 'wc-gateway-secipay' ),
					'desc_tip'    => true,
				),
				'rpc_username' => array(
					'title'       => __( 'RPC Username', 'wc-gateway-secipay' ),
					'type'        => 'text',
					'description' => __( 'RPC Username', 'wc-gateway-secipay' ),
					'default'     => __( 'Your RPC Username', 'wc-gateway-secipay' ),
					'desc_tip'    => true,
				),
				'rpc_password' => array(
					'title'       => __( 'RPC Password', 'wc-gateway-secipay' ),
					'type'        => 'password',
					'description' => __( 'Password', 'wc-gateway-secipay' ),
					'default'     => __( '', 'wc-gateway-secipay' ),
					'desc_tip'    => true,
				),
				'rpc_port' => array(
					'title'       => __( 'RPC Port', 'wc-gateway-secipay' ),
					'type'        => 'text',
					'description' => __( 'RPC Port Number', 'wc-gateway-secipay' ),
					'default'     => __( '9818', 'wc-gateway-secipay' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-gateway-secipay' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-secipay' ),
					'default'     => '',
					'desc_tip'    => true,
				),
			) );
		}		

		public function change_title_order_received( $title, $id ) {
			if ( is_order_received_page() && get_the_ID() === $id ) {
				$title = "Awaiting Payment";
			}
			return $title;
		}
	
		/**
		 * Output of wallet address and QR Code.
		 */
		public function order_received_output($text, $order) {
			    $exchange_rate = $this->exchange_rate;
			    $order_id = $order->get_id();
			    $order_total = $order->get_total();
			    $amount = round(  $order_total / $exchange_rate, 10 );
			    update_post_meta( $order_id, 'seci_amount', $amount );
			    $order = wc_get_order($order_id);
			    $address = get_post_meta( $order_id, 'seci_address', true);
			    echo '<div class="secipay-address-container"><div class="sp-address-container"><p class="sp-order-status">Order has been created. Awaiting Payment.</p><p class="sp-cost"><strong>'.  $amount .'</strong> SECI</p><input id="secipay-address" type="text" name="secipay-address" value="'.  $address . '"><p>Please send the exact amount owed to the address above. <br/>You can leave this page up to check the status of your payment</p></div><div class="sp-qrcode-container"><div id="qrcode"></div></div></div>';
			    
		}
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page($order_id) {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		
		}
	
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'pending-payment' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	        global $woocommerce;   
			$order = wc_get_order( $order_id );       
			// Mark as pending-payment (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting secipay payment', 'wc-gateway-secipay' ) );
			$bitcoin = new Bitcoin($this->rpc_username,$this->rpc_password,$this->rpc_server,$this->rpc_port);
			$address = $bitcoin->getnewaddress();                    
            $exchange_rate = $this->exchange_rate;
            $cart_total = $woocommerce->cart->cart_contents_total;
            $amount = round(  $cart_total / $exchange_rate, 10 );
            update_post_meta( $order_id, 'seci_amount', $amount );
            update_post_meta( $order_id, 'seci_address', $address);
            $note = 'SeciPay <br/> Type: Seci <br/>Address: ' . $address;
            $note .= '<br/>Pending Amount: ' . $amount;
            $order->add_order_note( $note );
            $user_id = $order->get_user_id(); // or $order->get_customer_id();
			// Remove cart
			WC()->cart->empty_cart();
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}
  } // end \WC_Gateway_Secipay class
}
