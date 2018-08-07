<?php

// SeciPaySettings
class SeciPaySettings{


    public function __construct(){
 
        add_action( 'admin_menu', array( $this, 'sp_add_admin_menu' ) );
        add_action( 'admin_init', array( $this,'sp_settings_init') );
 
    }

	public function sp_add_admin_menu(  ) { 

		add_menu_page( 'SeciPay', 'SeciPay', 'manage_options', 'woocommerce_secipay_gateway',  array( $this,'sp_options_page' ),  plugin_dir_url( __FILE__ ) . '../images/icon.png');
		//add_submenu_page('woocommerce_secipay_gateway', 'Coins', 'Coins', 'manage_options','edit.php?post_type=secipaycoin');

	}

	public function sp_settings_init(  ) { 

		register_setting( 'pluginPage', 'sp_settings' );

		add_settings_section(
			'sp_pluginPage_section', 
			__( 'Woocommerce Payment Gateway Settings', 'wc-gateway-secipay' ), 
			array( $this,'sp_settings_section_callback'), 
			'pluginPage'
		);
		add_settings_section(
			'sp_pluginPage_section_transactions', 
			__( 'General', 'wc-gateway-secipay' ), 
			array( $this,'sp_settings_section_transactions_callback'), 
			'pluginPage'
		);


		/*add_settings_field( 
			'sp_text_field_0', 
			__( 'Settings field description', 'wc-gateway-secipay' ), 
			array( $this,'sp_text_field_0_render'), 
			'pluginPage', 
			'sp_pluginPage_section' 
		);

*/

	}

	/*public function sp_text_field_0_render(  ) { 

		$options = get_option( 'sp_settings' );
		?>
		<input type='text' name='sp_settings[sp_text_field_0]' value='<?php echo $options['sp_text_field_0']; ?>'>
		<?php

	}*/

	public function sp_settings_section_callback(  ) { 

		echo __( 'To change settings related to the gateway, visit the SeciPay Gateway settings page <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=secipay_gateway">here</a>', 'wc-gateway-secipay' );

	}


	public function sp_settings_section_transactions_callback(  ) { 
       

       // Grab All Secipay Orders
		$args = array(
			 'limit' => -1,
		    'payment_method' => 'secipay_gateway',
		);
		$orders = wc_get_orders( $args );
    	$table = '<div id="sp-general" class="sp-tabs tablewrapper"><table id="sp-transactions" class="display" style="width:100%" ><thead><tr><th>ID</th><th>Created</th><th>Order ID</th><th>User ID</th><th>TX ID</th><th>Amount</th><th>Wallet Address</th><th>Order Status</th></tr></thead><tbody>';
    	$total_amount = 0;
    	$total_transactions = 0;
    	$count = 0;
    	foreach ( $orders as $order ){
    		 $count++;
    		 $seci_address = $order->get_meta('seci_address');
    		 $coin = $order->get_meta('coin');
    		 $block_explorer_url = get_post_meta( $coin, 'block_explorer_url', true );
    		 $coin_name = get_post_meta( $coin, 'coin_name', true );
    		 $seci_amount = floatval($order->get_meta('seci_amount'));
             //$total_amount = $total_amount + $seci_amount;
    		 $seci_txid = $order->get_meta('seci_txid');
    		 $order_id = $order->get_id();
             $table .= '<tr><td>' . $count . '</td>';
             $table .= '<td>' . $order->get_date_created() . '</td>';
             $table .= '<td><a href="/wp-admin/post.php?post=' . $order_id . '&action=edit">'. $order_id . '</a></td>';
             $table .= '<td>' . $order->get_customer_id() . '</td>';
             $table .= '<td><a target="_blank" href="' . $block_explorer_url . '/tx/' . $seci_txid . '">' . $seci_txid  . '</a></td>';
             $table .= '<td>' . $seci_amount . ' '. $coin_name .'</td>';
             $table .= '<td><a target="_blank" href="'. $block_explorer_url . '/address/' . $seci_address . '">'. $seci_address .'</a></td>';
             $table .= '<td>' . $order->get_status() . '</td></tr>';
    	}
    		$table .= '</tbody></table></div>';
    	 	echo __( '  <div id="sp-wrap" class="wrap"> <h2 class="nav-tab-wrapper"><a href="#sp-general" class="nav-tab sp-nav">Transactions</a><a href="#sp-coins" class="nav-tab sp-nav">Coins</a></h2><div class="sp-admin-total">', 'wc-gateway-secipay' );
    	 	echo __( $table , 'wc-gateway-secipay' );
    	 	echo __( '</div>' , 'wc-gateway-secipay' );
    	 	$query = new WP_Query( array( 'post_type' => 'secipaycoin','meta_key' => 'coin_name', 
    'orderby' => 'meta_value', 
    'order' => 'ASC' ) );
    	 	if ( $query->have_posts() ) : ?>
    	 	<?php 

    	 	?>
			<div id="sp-coins" class="sp-tabs"><div id="accordion">
    		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
    		<?php
    		    $coin_id = get_the_ID();
				$coin_name = get_post_meta( $coin_id, 'coin_name', true );
				$enabled = get_post_meta( $coin_id, 'enabled', true );
				$checked = '';
                if ($enabled == 'true'){
                	$checked = 'checked';
                } 
				$confirmations = get_post_meta( $coin_id, 'confirmations', true );
				$exchange_rate = get_post_meta( $coin_id, 'exchange_rate', true );
				$cold_storage = get_post_meta( $coin_id, 'cold_storage', true );
				$cs_checked = '';
                if ($cold_storage == 'true'){
                	$cs_checked = 'checked';
                } 
				$cold_storage_max_amount = get_post_meta( $coin_id, 'cold_storage_max_amount', true );
				$cold_stoage_wallet_address = get_post_meta( $coin_id, 'cold_stoage_wallet_address', true );
				$coin_rpc = get_post_meta($coin_id, "coin_rpc", true);
                $rpc_password = get_post_meta($coin_id, "rpc_password", true);
                $rpc_port = get_post_meta($coin_id, "rpc_port", true);
                $rpc_username = get_post_meta($coin_id, "rpc_username", true);
                $coin_name = get_post_meta($coin_id, "coin_name", true);
                $SeciRPC = new Bitcoin($rpc_username,$rpc_password,$coin_rpc,$rpc_port);
                $balance = $SeciRPC->getbalance();
				$daemon_info = $SeciRPC->getnetworkinfo();
				if (!$daemon_info){
					$rpc_error = $SeciRPC->error;
					$status = '<div class="sp-coind-status"><div class="sp-status-icon sp-coind-error"></div><strong><span>Error</span></strong> </div>';
				} else {
					$status = '<div class="sp-coind-status"><div class="sp-status-icon sp-coind-connected"></div><strong><span>Connected</span></strong></div>';
				
				}
    		 ?>
     	   	<?php
     	   	   $coin_image =  get_post_meta( $coin_id, 'coin_image', true );  
     	   	   $toggle = '<label class="switch" onclick="coin_enable_toggle(event);"><input class="sp-coin-enable" data-id="'. $coin_id .'" type="checkbox" '. $checked .'><span class="slider round"></span>';
     			echo '<div class="sp-coin-index" data-id="'. $coin_id .'"><h3><img src="'. $coin_image . '"/>' . get_the_title() .  $toggle . $status  .'</h3>';
				echo '<div><div class="sp-coin-data-cotainer"><div class="sp-coin-data col-md-2"><div class="sp-coin-heading"><label><strong>General Settings</strong></label></div><br>';
				echo '<div class="sp-label-container"><label>Wallet Balance: </label><strong class="balance">' . $balance . '</strong></div><br>';
				echo '<div class="sp-label-container"><label>Confirmations: </label><input type="text" coin-id="'. $coin_id .'" name="confirmations" value="' . esc_textarea( $confirmations )  . '" class="small-text"></div><br>';
				echo '<div class="sp-label-container"><label>Exchange Rate: </label><input type="text" coin-id="'. $coin_id .'"  name="exchange_rate" value="' . esc_textarea( $exchange_rate )  . '" class="small-text"></div></div><div class="sp-coin-data col-md-2"><div class="sp-coin-heading"><label><strong>Cold Storage</strong></label></div><br>';
				echo '<div class="sp-label-container"><label>Enabled</label><input type="checkbox" coin-id="'. $coin_id .'"  name="cold_storage" class="" '. $cs_checked .'></div><br>';
				echo '<div class="sp-label-container"><label>Wallet Limit:</label><input type="text" coin-id="'. $coin_id .'"  name="cold_storage_max_amount" value="' . esc_textarea( $cold_storage_max_amount )  . '" class="small-text"></div><br>';
				echo '<div class="sp-label-container"><label>Wallet Address:</label> <input class="cs-wallet-address" type="text" coin-id="'. $coin_id .'" name="cold_stoage_wallet_address" value="' . esc_textarea( $cold_stoage_wallet_address )  . '" class=""></div>';

				echo '</div></div></div>';
			?>
        	<?php endwhile; wp_reset_postdata(); ?>
    		<!-- show pagination here -->
			<?php else : ?>
		    <!-- show 404 error here -->
			<?php endif; ?>  
			</div></div>
  		    <?php
	}
	// Options Page
	public function sp_options_page(  ) { 
		?>
		<form action='options.php' method='post'>

			<h2>SeciPay</h2>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			
			?>

		</form>
		<a href="#" class="button button-primary sp-coin-update"> Save Changes </a>	<div class="coin-saved hide"><span>Coin Data Saved!</span></div></div>
	
		<?php
	}
}