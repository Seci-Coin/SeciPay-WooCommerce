<?php 
/**
 * Plugin Name: Secipay WooCommerce Gateway
 * Plugin URI: https://www.seci.io
 * Description: SeciPay Woocommerce Gateway
 * Author: Seci Team
 * Author URI: http://www.seci.io
 * Version: 1
 * Text Domain: wc-gateway-secipay
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2018 Seci Team
 *
 *
 * @package   WC-Gateway-Secipay
 * @author    Seci Team
 * @category  Woocommerce
 * @copyright Copyright (c) 2018, Seci Team, and WooCommerce
 * @license  
 *
 *
 */


 /**
 * Do not let plugin be accessed directly
 **/
if ( ! defined( 'ABSPATH' ) ) {
    write_log( "Plugin should not be accessed directly!" );
    exit; // Exit if accessed directly
}


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}



include_once(plugin_dir_path( __FILE__ ) . 'includes/easybitcoin.php');
include_once(plugin_dir_path( __FILE__ ) . 'classes/sp-settings.php');
include_once(plugin_dir_path( __FILE__ ) . 'classes/sp-woo-gateway.php');
include_once(plugin_dir_path( __FILE__ ) . 'classes/sp-helper.php');




class SeciPayWooGateway{

  	 public function __construct(){
        register_activation_hook(__FILE__, array( $this,'cron_activation'));
        register_deactivation_hook(__FILE__, array( $this, 'sp_deactivation'));
        register_activation_hook( __FILE__ , array( $this,'secipay_create_coin_data'));
        add_action("admin_enqueue_scripts",array( $this, "sp_admin_enqueue"));
        add_action( 'wp_enqueue_scripts', array( $this, 'sp_enqueue_scripts') );
 		add_action( 'wp_ajax_order_checking',array( $this, 'order_checking'));
		add_action( 'wp_ajax_nopriv_order_checking', array( $this,'order_checking' ));
        add_filter( 'woocommerce_available_payment_gateways', array( $this,'woocommerce_available_payment_gateways'));
        add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this,'wallet_notify_callback' ) );
        add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this,'sp_custom_query_var'), 10, 2 );
        add_filter( 'cron_schedules', array( $this,'woo_sp_schedules' ));
        add_action( 'sp_cron_check_tx_confirmations', array( $this,'secipay_check_tx_confirmations' ));
        add_action( 'sp_cron_coldstorage',array( $this, 'secipay_cold_storage'));
        add_action('wp_ajax_sp_coin_enable',array( $this, 'coin_enable'));
        add_action('wp_ajax_sp_coin_meta_update',array( $this, 'coin_meta_update'));
    }
    public function coin_enable(){
            $coin_id = $_POST['coin_id'];
            $enabled = $_POST['enabled'];
            update_post_meta( $coin_id , 'enabled', $enabled);
            exit;
    }
        /**
     * Output the HTML for the metabox.
     */
    public function secipay_create_coin_data() {
            global $wpdb;
            $image_dir = plugin_dir_url( __FILE__ );
            $coins = [];
            $coins[] = array("title"=>'Bitcoin', "name"=>'bitcoin', "coin_rpc"=>' ', "rpc_port"=>' ',"rpc_username"=>' ',"rpc_password"=>' ',"confirmations"=>' ',"exchange_url"=>'https://api.coingecko.com/api/v3/coins/bitcoin?localization=en', "explorer_url"=>'https://blockexplorer.com',"enabled"=>'false',"exchange_rate"=>' ',"cold_storage"=>'false',"confirmations"=>'0',"cold_storage_max_amount"=>'0',"cold_stoage_wallet_address"=>' ', "cold_stoage_wallet_address"=>' ', 'coin_image' => $image_dir . 'assets/img/bitcoin_140x140.png');
            $coins[] = array("title"=>'Raven', "name"=>'raven', "coin_rpc"=>' ', "rpc_port"=>' ',"rpc_username"=>' ',"rpc_password"=>' ',"confirmations"=>' ',"exchange_url"=>'https://ravencoin.network/api/tx/', "explorer_url"=>'https://ravencoin.network',"enabled"=>'false', "exchange_rate"=>' ',"cold_storage"=>'false',"confirmations"=>'0',"cold_storage_max_amount"=>'0',"cold_stoage_wallet_address"=>' ', 'coin_image' => $image_dir . 'assets/img/ravencoin_140x140.png');
            $coins[] = array("title"=>'Seci', "name"=>'Seci', "coin_rpc"=>' ', "rpc_port"=>' ',"rpc_username"=>' ',"rpc_password"=>' ',"confirmations"=>' ',"exchange_url"=>'https://safe.trade/api/v2/tickers', "explorer_url"=>'http://159.203.126.66/api/getrawtransaction?txid=',"enabled"=>'false',"exchange_rate"=>' ',"cold_storage"=>'false',"confirmations"=>'0',"cold_storage_max_amount"=>'0',"cold_stoage_wallet_address"=>' ', 'coin_image' => $image_dir . 'assets/img/seci_140x140.png');            
             foreach ($coins as $coin){

           

                         // Create post object
                    $newCoin = array();
                    $newCoin['post_title'] = $coin['title'];
                    $newCoin['post_status'] = 'publish';
                    $newCoin['post_type'] = 'secipaycoin';
                    $newCoin['meta_input'] = array(
                                    'coin_name'=> $coin['name'],
                                    'coin_rpc'=> $coin['coin_rpc'],
                                    'confirmations'=> $coin['confirmations'],
                                    'exchange_rate'=> $coin['exchange_rate'],
                                    'rpc_port'=> $coin['rpc_port'],
                                    'rpc_username'=> $coin['rpc_username'],
                                    'rpc_password'=> $coin['rpc_password'],
                                    'explorer_url'=> $coin['explorer_url'],
                                    'cold_stoage_wallet_address'=> $coin['cold_stoage_wallet_address'],
                                    'cold_storage'=> $coin['cold_storage'],
                                    'cold_storage_max_amount'=> $coin['cold_storage_max_amount'],
                                    'exchange_url'=> $coin['exchange_url'],
                                    'coin_image' => $coin['coin_image']
                            );
                    $newCoinID = wp_insert_post( $newCoin );         
             }

    }
    public function coin_meta_update(){
           
            $data = $_POST['data'];
            foreach ($data as $coin_data){

               $coin = $coin_data['id'];
               update_post_meta($coin, 'confirmations', $coin_data['confirmations'] );
               update_post_meta($coin, 'exchange_rate', $coin_data['exchange_rate'] );
               update_post_meta($coin, 'cold_storage', $coin_data['cold_storage'] );
               update_post_meta($coin, 'cold_storage_max_amount', $coin_data['cold_storage_max_amount'] );
               update_post_meta($coin, 'cold_stoage_wallet_address', $coin_data['cold_stoage_wallet_address'] );

            }

           
               echo 'success';
               exit;
    }
    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    function wallet_notify_callback() {
        $SeciWooGateway = new WC_Gateway_Secipay();
      
        $txid = $_GET["tx"];
        $coin_type = $_GET["coin"];
        write_log($_REQUEST);
        write_log('get tx:' .  $txid );
        write_log('get coin_type:' .  $coin_type );
        $args = array(
            'posts_per_page' => 1,
                'numberposts' => 1,
           'post_type' => 'secipaycoin',
           'meta_query' => array(
               array(
                   'key' => 'coin_name',
                   'value' => $coin_type,
                   'compare' => '=',
               )
           )
        );
        $query = new WP_Query($args);
        if ( $query ->have_posts() ) : 
            while ( $query ->have_posts() ) : $query->the_post(); 
                    $coin = get_the_ID();
                    write_log('COIN ID:' .  $coin );
                    $coin_rpc = get_post_meta($coin, "coin_rpc", true);
                    $rpc_password = get_post_meta($coin, "rpc_password", true);
                    $rpc_port = get_post_meta($coin, "rpc_port", true);
                    $rpc_username = get_post_meta($coin, "rpc_username", true);
                    $coin_name = get_post_meta($coin, "coin_name", true);
            endwhile; 
             wp_reset_postdata();
        endif;      
        $SeciRPC = new Bitcoin($rpc_username,$rpc_password,$coin_rpc,$rpc_port);
        $txinfo = $SeciRPC->gettransaction($txid);
        if ($SeciRPC->error){
             echo 'Error' . $SeciRPC->error;
        }
        $details = count($txinfo["details"]);
        write_log('details:' .  $details );
        foreach($txinfo["details"] as $details){
            $wallet_address = $details["address"];
            $amount = $details["amount"];
            $args = array(
                'limit' => 1,
                'status' => 'pending',
                'type' => 'shop_order',
                'payment_method' => 'secipay_gateway',
                'seci_address' => $wallet_address,
                'seci_amount' => $amount
            );
            $query = new WC_Order_Query($args);
            $orders = $query->get_orders();        
            if ($orders){
                write_log('customer orders: ' . json_encode($orders));
                foreach ( $orders as $order ) {
                    $order_id = $order->get_id();
                    update_post_meta( $order_id , 'seci_txid', $txid);
                }     
            }   
        }
        die();
    }

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */

    public function sp_deactivation() {
        write_log("Plugin deactivating.");
        wp_clear_scheduled_hook('sp_cron_delivery');
        wp_clear_scheduled_hook('sp_cron_coldstorage');
    }

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
	public function sp_enqueue_scripts() {
        if (is_checkout()){
                    wp_enqueue_style( 'style-name', plugin_dir_url( __FILE__ ) . 'css/sp-checkout-style.css' ); 
        }
    	if (is_wc_endpoint_url( 'order-received' )){
            wp_enqueue_style( 'style-name', plugin_dir_url( __FILE__ ) . 'css/style.css' ); 
        	wp_enqueue_script( 'order-checker', plugin_dir_url( __FILE__ ) . 'js/order-checker.js', '','1.0', true);
            wp_enqueue_script( 'qrcodejs',  plugin_dir_url( __FILE__ ) . 'js/qrcode.min.js', '','1.0', true);
            wp_enqueue_script( 'qrcodegen',  plugin_dir_url( __FILE__ ) . 'js/qrcodegen.js', '','1.0', true);
        	wp_localize_script( 'order-checker', 'sp_data', 
        		array('ajax_url' => admin_url( 'admin-ajax.php' ),
                	  'order_id' => absint( get_query_var( 'order-received')))
        	);
   		}
	}

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function order_checking() {
    	$order_id = $_POST['order_id'];
        $order = wc_get_order( $order_id );
        $order_status = $order->get_status();
        echo $order_status;
     	die();
	}  

	/**
	 * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Order_Query.
	 * @return array modified $query
	 */

	public function sp_custom_query_var( $query, $query_vars ) {
		if ( ! empty( $query_vars['seci_address'] ) ) {
			$query['meta_query'][] = array(
				'key' => 'seci_address',
				'value' => esc_attr( $query_vars['seci_address'] ),
			);
		}
		return $query;
	}

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function cron_activation() {
        write_log("Plugin activating.");
        if ( ! wp_get_schedule( 'sp_cron_check_tx_confirmations' ) ) {
            wp_schedule_event( time(), '60s', 'sp_cron_check_tx_confirmations' );
        }
        if ( ! wp_get_schedule( 'sp_cron_coldstorage' ) ) {
            wp_schedule_event( time(), 'hourly', 'sp_cron_coldstorage' );
        }
        write_log("Plugin activated.");
    }

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function sp_admin_enqueue(){
        if (isset($_GET["page"])){   //replace with your page "id"
            if($_GET["page"] == "woocommerce_secipay_gateway")
            {
                wp_enqueue_script("datatables-js", plugin_dir_url(__FILE__) . "js/datatables.min.js");
                wp_enqueue_script("secipay-admin-js", plugin_dir_url(__FILE__) . "js/spadmin.js");
                wp_enqueue_script( 'jquery-ui-accordion' );
                wp_enqueue_script( 'postbox' );
                wp_enqueue_style("datatables-css", plugin_dir_url(__FILE__) . "css/datatables.min.css");
                wp_enqueue_style("secipay-admin-css", plugin_dir_url(__FILE__) . "css/spadmin.css");
            }
        }
    }

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function secipay_check_tx_confirmations() {
        $SeciWooGateway = new WC_Gateway_Secipay();
        $args = array(
                'limit' => -1,
                'payment_method' => 'secipay_gateway',
                'status' => array('pending', 'on-hold'),
        );

        $orders = wc_get_orders( $args );
        foreach ( $orders as $order ){ 
            $seci_txid = $order->get_meta('seci_txid', true);
            $seci_address = $order->get_meta('seci_address', true);
            $seci_amount = $order->get_meta('seci_amount', true);
            $coin = $order->get_meta('coin', true);
            $confirmations = get_post_meta($coin, "confirmations", true);
            $explorer_type = get_post_meta($coin, "explorer_type", true);
			$explorer_url = get_post_meta($coin, "explorer_url", true);
            $order_id = $order->get_id();
            if(isset($seci_txid)){
                write_log( "TXID Found: " . $seci_txid );     
                $coin_rpc = get_post_meta($coin, "coin_rpc", true);
                $rpc_password = get_post_meta($coin, "rpc_password", true);
                $rpc_port = get_post_meta($coin, "rpc_port", true);
                $rpc_username = get_post_meta($coin, "rpc_username", true);
                $SeciRPC = new Bitcoin($rpc_username,$rpc_password,$coin_rpc,$rpc_port);
                $txinfo = $SeciRPC->gettransaction($seci_txid);
                $address_amount = $SeciRPC->getreceivedbyaddress($seci_address, 0);
                write_log('Address amount');
                write_log($seci_address);
                write_log($address_amount);
                if ($SeciRPC->error){
                    write_log('Error: ' . $SeciRPC->error);
                }                     
                $tx_confirmations = $txinfo["confirmations"];
            //    write_log('details:' .  $details );

    			$order_amount_owed =  round($seci_amount, 8);
                $order_amount_received = round($address_amount, 8);
                write_log('seci_address amounts after rounded');
                write_log('seci_address' . $seci_address);
                write_log('order_amount_received' .  $order_amount_received);
                write_log('order_amount_owed' .  $order_amount_owed);
          
                /*if ($order_amount_received !== $order_amount_owed && $order_amount_received !== 0 && $order_amount_received !== '0' ){

                   $amount_owed =  $order_amount_owed - $order_amount_received;
                   update_post_meta($order_id, "amount_received", $order_amount_received );
                      $order->update_status( 'on-hold', __( 'Marked order as On Hold. Cost Mismatch ' . $coin_name . 'Amount Received: '. $order_amount_received .' Amount Owed: ' . $amount_owed, 'wc-gateway-secipay' ) );
                } */
                if ($tx_confirmations >= $confirmations && $address_amount >= $seci_amount ){
                        $order->update_status( 'processing', __( 'Marked order as processing - ' . $coin_name . ' received: ' . $seci_amount, 'wc-gateway-secipay' ) );
                        update_post_meta($order_id, "amount_received", $order_amount_received );
                }
                if ($tx_confirmations >= $confirmations && $address_amount < $seci_amount && $order_amount_received > '0'){
                        $order->update_status( 'on-hold', __( 'Marked order as on hold - ' . $coin_name . ' received: ' . $address_amount, 'wc-gateway-secipay' ) );
                        update_post_meta($order_id, "amount_received", $order_amount_received );
                }
                update_post_meta($order_id, "amount_received", $order_amount_received );
                update_post_meta($order_id, "tx_confirmations", $tx_confirmations  );
              //$result = json_decode($response, true);
              //  write_log('TXID: ' . $seci_txid . ' Confirmation: ' . $result["confirmations"] );
               // write_log('TXID: ' . $seci_txid . ' Settings Confirmation: ' .  $confirmations );
      
            }
        
        }
    
    }

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function secipay_cold_storage() { 
    	global $post;
    	$sp_cs_args = array(
    			'post_type' => 'secipaycoin',
				'meta_query' => array(
						array(
							'key' => 'enabled',
							'value' => 'true',
							'compare' => '='
						),
						array(
							'key' => 'cold_storage',
							'value' => 'true',
							'compare' => '='
						)
					)
						);
 
		$query = new WP_Query( $sp_cs_args );

 		if ( $query->have_posts() ) : 
	    		while ( $query->have_posts() ) : $query->the_post(); 
	 				$coin = $post->ID;
		 			$cold_storage_max_amount = get_post_meta( $coin, 'cold_storage_max_amount', true );
					$cold_stoage_wallet_address = get_post_meta( $coin, 'cold_stoage_wallet_address', true );
					$coin_rpc = get_post_meta($coin, "coin_rpc", true);
	                $rpc_password = get_post_meta($coin, "rpc_password", true);
	                $rpc_port = get_post_meta($coin, "rpc_port", true);
	                $rpc_username = get_post_meta($coin, "rpc_username", true);
	                $coin_name = get_post_meta($coin, "coin_name", true);
  					$cold_storage_address = preg_replace('/\s+/', '', $cold_stoage_wallet_address);
                	$SeciWooGateway = new WC_Gateway_Secipay();
               		$SeciRPC = new Bitcoin($rpc_username,$rpc_password,$coin_rpc,$rpc_port);
                	$entire_balance = $SeciRPC->getbalance();
                	if($entire_balance > $cold_storage_max_amount){
	                    $SeciRPC->sendtoaddress($cold_storage_address, $entire_balance , "Cold Storage - SeciPay", "Cold Storage - SeciPay", true );
	                    write_log('Cold Storage');
	                    write_log($entire_balance);
	                    write_log( $cold_storage_address );
                	}              
			    endwhile; wp_reset_postdata(); 
			endif;
    }
    /**
     * Add a custom schedule to wp.
     * @param $schedules array The  existing schedules
     *
     * @return mixed The existing + new schedules.
     */
    //Add cron schedules filter with upper defined schedule.

    public function woo_sp_schedules( $schedules ) {
        if ( ! isset( $schedules["60s"] ) ) {
            $schedules["60s"] = array(
                'interval' => 60,
                'display'  => __( 'Once every 60 seconds' )
            );
        } 
        return $schedules;
    }

    public function woocommerce_available_payment_gateways( $available_gateways ) {
        if (! is_checkout() ) return $available_gateways;  // stop doing anything if we're not on checkout page.
        if (array_key_exists('secipay_gateway',$available_gateways)) {
             $available_gateways['secipay_gateway']->order_button_text = __( 'Generate Address ', 'woocommerce' );
        }
        return $available_gateways;
    }
}// End SeciPay Class

$SeciPaySettingsPage = new SeciPaySettings();
$SeciPay = new SeciPayWooGateway();
$SeciPayCoins = new SeciPayCoins();

//Add a utility function to handle logs more nicely.
if ( ! function_exists('write_log')) {
    function write_log ( $log )  {
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( print_r( $log, true ) );
        } else {
            error_log( $log );
        }
    }
}