<?php 
/**
 * Plugin Name: Secipay WooCommerce Gateway
 * Plugin URI: https://www.seci.io
 * Description: SeciPay Woocommerce Gateway
 * Author: Seci Team
 * Author URI: http://www.seci.io
 * Version: 0.0.2
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




class SeciPayWooGateway{

  	 public function __construct(){
        register_activation_hook(__FILE__, array( $this,'cron_activation'));
        register_deactivation_hook(__FILE__, array( $this, 'sp_deactivation'));
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
    }

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    function wallet_notify_callback() {
        $SeciWooGateway = new WC_Gateway_Secipay();
        $SeciRPC = new Bitcoin($SeciWooGateway->rpc_username,$SeciWooGateway->rpc_password,$SeciWooGateway->rpc_server,$SeciWooGateway->rpc_port);
        $txid = $_GET["tx"];
        $txinfo = $SeciRPC->gettransaction($txid);
        $details = count($txinfo["details"]);
        for($i=0;$i<$details;$i++){
            $check = $txinfo["details"][$i]["address"];
            $amount = $txinfo["details"][$i]["amount"];
            $customer_orders = wc_get_orders( array(
                'limit'    => 1,
                'status'   => array( 'on-hold' ),
                'seci_address' => $check,
                'seci_amount' => $amount
             ) );
            if ($customer_orders){
                foreach ( $customer_orders as $order ) {
                    $orderid = $order->get_id();
                    update_post_meta( $orderid , 'seci_txid', $txid);
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
        write_log("Plugin deactivated.");
    }

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
	public function sp_enqueue_scripts() {
   
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
        if( $order->get_status() == 'on-hold' ){
            echo 'on-hold';
        } else {
            echo 'confirmed';
        }
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
        $confirmations = (int)$SeciWooGateway->get_option('txid_confirmations');
        $args = array(
                'limit' => -1,
                'payment_method' => 'secipay_gateway',
                'status' => 'on-hold',
        );
        $orders = wc_get_orders( $args );
        foreach ( $orders as $order ){ 
            $seci_txid = $order->get_meta('seci_txid');
            $seci_address = $order->get_meta('seci_address');
            $seci_amount = floatval($order->get_meta('seci_amount'));
            $order_id = $order->get_id();
            write_log( "TXID Found: " . $seci_txid );
            // Get cURL resource
            $curl = curl_init();
            // Set some options - we are passing in a useragent too here
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'http://explorer.seci.io/api/getrawtransaction?txid='. $seci_txid . "&decrypt=1",
            ));
            // Send the request & save response to $resp
            $response = curl_exec($curl);
            // Close request to clear up some resources
            curl_close($curl);
                if ($response){
                    $result = json_decode($response, true);
                        write_log('TXID: ' . $seci_txid . ' Confirmation: ' . $result["confirmations"] );
                        write_log('TXID: ' . $seci_txid . ' Settings Confirmation: ' .  $confirmations );
                    if ($result["confirmations"] >  $confirmations){
                        $order->update_status( 'completed', __( 'Completed - seci received: ' . $seci_amount, 'wc-gateway-secipay' ) );         
                    }
            write_log('confirmation not over 5, not updated' );
                }
        }
            write_log( "triggered");
    }

    /**
     * Handle a custom 'seci_address' query var to get orders with the 'seci_address' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function secipay_cold_storage() {       
        $options = get_option( 'sp_settings' );
        if (array_key_exists("sp_cold_storage_enabled",$options)){

        } else { 
            $options['sp_cold_storage_enabled'] = false;
        }
        if (checked( $options['sp_cold_storage_enabled'], 1 ) !== ''){
            $cold_storage_address = $options['sp_cold_storage_address'];
            $cold_storage_max_amount = floatval($options['sp_cold_storage_max_amount']);
            $cold_storage_address = preg_replace('/\s+/', '', $cold_storage_address);
            $SeciWooGateway = new WC_Gateway_Secipay();
            $SeciRPC = new Bitcoin($SeciWooGateway->rpc_username,$SeciWooGateway->rpc_password,$SeciWooGateway->rpc_server,$SeciWooGateway->rpc_port);
            $entire_balance = $SeciRPC->getbalance();
            if($entire_balance > $cold_storage_max_amount){
                $SeciRPC->sendtoaddress($cold_storage_address, $entire_balance , "Cold Storage - SeciPay", "Cold Storage - SeciPay", true );
                write_log('Cold Storage');
                write_log($entire_balance);
                write_log( $cold_storage_address );
            }
        }   
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
        // Gateway ID for Paypal is 'paypal'. 
         $available_gateways['secipay_gateway']->order_button_text = __( 'Generate Address ', 'woocommerce' );
    }
    return $available_gateways;
}
}// End SeciPay Class

$SeciPaySettingsPage = new SeciPaySettings();
$SeciPay = new SeciPayWooGateway();

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


