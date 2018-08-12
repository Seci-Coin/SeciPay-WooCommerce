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
			$this->has_fields         = true;
			$this->method_title       = __( 'Secipay', 'wc-gateway-secipay' );
			$this->method_description = __( 'Allows secipay payments. Orders are marked as "on-hold" when received and "completed" after X number of confirmations', 'wc-gateway-secipay' );
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
			// Define user set variables
			$this->description  = $this->get_option( 'description' );
			$this->title        = $this->get_option( 'title' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	  		add_action('woocommerce_checkout_process',array( $this, 'process_custom_payment'));
		    // Update the order meta with field value
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'custom_payment_update_order_meta'));
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
			// Filter
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
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-gateway-secipay' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-secipay' ),
					'default'     => '',
					'desc_tip'    => true,
				),
			) );
		}		
		public function payment_fields() {


			$query = new WP_Query( array( 'post_type' => 'secipaycoin', 'meta_key' => 'coin_name', 
    'orderby' => 'meta_value', 
    'order' => 'ASC', 'meta_query' => array( array('key' => 'enabled','value' => 'true'))));
			if ( $query->have_posts() ) : ?>
				<p> Choose your Currency:</p>
				<div id="secipay-currency">
	    		<?php while ( $query->have_posts() ) : $query->the_post(); ?>   
	    	    <?php $coin_image_url =  get_post_meta( get_the_ID(), 'coin_image', true );  ?>
	    	    <div class="secipay-currency"><label> <img src="<?php echo $coin_image_url; ?>"><br> <?php the_title(); ?></label><input type="radio" name="secipaycoin" value="<?php the_ID(); ?>"></div>     
			    <?php endwhile; wp_reset_postdata(); ?>
				</div> 
			<?php else : ?>
			<?php endif;  
		}
		public function change_title_order_received( $title, $id ) {
			if ( is_order_received_page() && get_the_ID() === $id ) {
                global $wp;
				$wc_order_key = wc_clean( $_GET['key']);
				if ($wc_order_key){
	                $order_id = wc_get_order_id_by_order_key($wc_order_key);
	                $order = new WC_Order($order_id);
		  			if ( isset ( $order ) ) {
						$payment_method = $order->get_payment_method();
						if($payment_method == 'secipay_gateway'){
							$title = "Awaiting Payment";
						}
					}
				}
			}
		return $title;
		}
	
		/**
		 * Output of wallet address and QR Code.
		 */
		public function order_received_output($text, $order) {
			    $order_id = $order->get_id();
			    $order_total = $order->get_total();
			    $coin = $order->get_meta('coin');
			    $coin_name = get_post_meta($coin, "coin_name", true);
			    $exchange_url = get_post_meta($coin, "exchange_url", true);
			    $exchange_rate = get_post_meta($coin, "exchange_rate", true);
			    $order_checked= get_post_meta( $order_id, 'order_checked', true );
			    if ($order_checked !== 'true'){
			    	write_log('not true');
			    if ($coin_name == 'Seci'){
			    	$bitcoin_exchange_url = 'https://api.coingecko.com/api/v3/coins/bitcoin?localization=en';
					$bitcoin_exchange_response = wp_remote_get( esc_url_raw( $bitcoin_exchange_url ) );
					$bitcoin_exchange_data = json_decode( wp_remote_retrieve_body( $bitcoin_exchange_response ), true );
					$bitcoin_exchange_rate_usd = $bitcoin_exchange_data['market_data']['current_price']['usd'];
					$seci_exchange_response = wp_remote_get( esc_url_raw( $exchange_url ) );
					$seci_exchange_data = json_decode( wp_remote_retrieve_body( $seci_exchange_response ), true );
					//write_log($seci_exchange_data);
					$seci_btc = $seci_exchange_data['secibtc']['ticker']['buy'];
					if($seci_btc){
						$seci_exchange = round($bitcoin_exchange_rate_usd * $seci_btc, 20 );
						$amount = round(  $order_total / $seci_exchange, 8 );
					} else {
						$amount = round(  $order_total / $exchange_rate, 8 );
					}
			    } else {
				    $exchange_response = wp_remote_get( esc_url_raw( $exchange_url ) );
					$exchange_data = json_decode( wp_remote_retrieve_body( $exchange_response ), true );
					$exchange_rate_usd = $exchange_data['market_data']['current_price']['usd'];
					if ($exchange_rate_usd){
						$amount = round(  $order_total / $exchange_data['market_data']['current_price']['usd'], 8 );
					} else {
						$amount = round(  $order_total / $exchange_rate, 8 );
					} 	
			    	
			    }
	     
			    update_post_meta( $order_id, 'seci_amount', $amount );
			    update_post_meta( $order_id, 'order_checked', 'true' );
			    } else {

			    	$amount = get_post_meta( $order_id, 'seci_amount', true);
			    }
			    //$order = wc_get_order($order_id);
			    $address = get_post_meta( $order_id, 'seci_address', true);
			    $coin_image_url = get_post_meta( $coin, 'coin_image', true);
			   // $coin_icon = get_the_post_thumbnail_url($coin);
			    echo '<div class="secipay-address-container"><div class="sp-address-container"><p class="sp-order-status"><span class="sp-order-status-update">Order has been created.</span><br><span class="pulse waiting">Awaiting Payment.</span></p><p class="sp-cost"><strong>'.  $amount .'</strong> '. '<br><img class="coin-icon" src="' . $coin_image_url . '">' . $coin_name  . '</p><input id="secipay-address" type="text" name="secipay-address" value="'.  $address . '"><p class="sp-order-text">Please send the exact amount owed to the address above. <br/>You can leave this page up to check the status of your payment</p></div><div class="sp-qrcode-container"><div id="qrcode"></div></div></div>';  
		}
		public function process_custom_payment(){

  		}

		public function custom_payment_update_order_meta( $order_id ) {

		    if($_POST['payment_method'] != 'secipay_gateway')
		        return;
		    update_post_meta( $order_id, 'coin', $_POST['secipaycoin'] );

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
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'pending' ) ) {
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
			$coin = $order->get_meta('coin');    
			$coin_rpc = get_post_meta($coin, "coin_rpc", true);
			$rpc_password = get_post_meta($coin, "rpc_password", true);
			$rpc_port = get_post_meta($coin, "rpc_port", true);
			$rpc_username = get_post_meta($coin, "rpc_username", true);
			$coin_name = get_post_meta($coin, "coin_name", true);
			// Mark as pending-payment (we're awaiting the payment)
			$order->update_status( 'pending', __( 'Awaiting secipay payment', 'wc-gateway-secipay' ) );
			$bitcoin = new Bitcoin($rpc_username,$rpc_password,$coin_rpc,$rpc_port);
			$address = $bitcoin->getnewaddress();                    
            $exchange_rate = $this->exchange_rate;
            $cart_total = $woocommerce->cart->cart_contents_total;
         
            update_post_meta( $order_id, 'seci_address', $address);
            $note = 'SeciPay <br/> Type: '. $coin_name . ' <br/>Address: ' . $address;
          
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