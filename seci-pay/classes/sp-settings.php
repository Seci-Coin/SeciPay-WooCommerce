<?php

// SeciPaySettings
class SeciPaySettings{


    public function __construct(){
 
        add_action( 'admin_menu', array( $this, 'sp_add_admin_menu' ) );
        add_action( 'admin_init', array( $this,'sp_settings_init') );
 
    }

	public function sp_add_admin_menu(  ) { 

		add_menu_page( 'SeciPay', 'SeciPay', 'manage_options', 'woocommerce_secipay_gateway',  array( $this,'sp_options_page' ),  plugin_dir_url( __FILE__ ) . '../images/icon.png');

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
			'sp_pluginPage_section_coldstorage', 
			__( 'Cold Storage', 'wc-gateway-secipay' ), 
			array( $this,'sp_settings_section_coldstorage_callback'), 
			'pluginPage'
		);
    	add_settings_field( 
		'sp_cold_storage_enabled', 
		__( 'Enable Cold Storage?', 'wc-gateway-secipay' ), 
		'sp_cold_storage_enabled_render', 
		'pluginPage', 
		'sp_pluginPage_section_coldstorage' 
		);

		add_settings_field( 
			'sp_cold_storage_address', 
			__( 'Wallet Address', 'wc-gateway-secipay' ), 
			'sp_cold_storage_address_render', 
			'pluginPage', 
			'sp_pluginPage_section_coldstorage' 
		);

		add_settings_field( 
			'sp_cold_storage_max_amount', 
			__( 'Amount Threshold', 'wc-gateway-secipay' ), 
			'sp_cold_storage_max_amount_render', 
			'pluginPage', 
			'sp_pluginPage_section_coldstorage'
		); 
		add_settings_section(
			'sp_pluginPage_section_transactions', 
			__( 'Transactions', 'wc-gateway-secipay' ), 
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

function sp_cold_storage_enabled_render(  ) { 

	$options = get_option( 'sp_settings' );
    if (array_key_exists("sp_cold_storage_enabled",$options))
  { } else { 
    $options['sp_cold_storage_enabled'] = false;
  }
	?>
	
	<input type='checkbox' name='sp_settings[sp_cold_storage_enabled]' <?php checked( $options['sp_cold_storage_enabled'], 1 ); ?> value='1'>

	<?php

}


function sp_cold_storage_address_render(  ) { 

	$options = get_option( 'sp_settings' );
	?>
	<input class="cs-offline-wallet" type='text' name='sp_settings[sp_cold_storage_address]' value='<?php echo $options['sp_cold_storage_address']; ?>'>
	<label class="cs-label">Address to send SECI to for safe storage</label>
	<?php

}


function sp_cold_storage_max_amount_render(  ) { 

	$options = get_option( 'sp_settings' );
	?>
	<input class="cs-max-amount" type='text' name='sp_settings[sp_cold_storage_max_amount]' value='<?php echo $options['sp_cold_storage_max_amount']; ?>'><span> SECI</span>
	<label class="cs-label">Maximum Amount of SECI to keep in RPC Wallet</label>
	<?php

}
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

	public function sp_settings_section_coldstorage_callback(  ) { 

		echo __( 'Cold Storage allows you the ability to send your wallet balance to another wallet address after a certain balance is reached on your payment gateway wallet.<br/> <strong>IMPORTANT:</strong> Please be sure your offline wallet address is correct before enabling.', 'wc-gateway-secipay' );

	}
	public function sp_settings_section_transactions_callback(  ) { 
       

       // Grab All Secipay Orders
		$args = array(
			 'limit' => -1,
		    'payment_method' => 'secipay_gateway',
		);
		$orders = wc_get_orders( $args );
    	$table = '<div class="tablewrapper"><table id="sp-transactions" class="display" style="width:100%" ><thead><tr><th>ID</th><th>Created</th><th>Order ID</th><th>User ID</th><th>TX ID</th><th>Amount</th><th>Wallet Address</th><th>Order Status</th></tr></thead><tbody>';
    	$total_amount = 0;
    	$total_transactions = 0;
    	$count = 0;
    	foreach ( $orders as $order ){
    		 $count++;
    		 $seci_address = $order->get_meta('seci_address');
    		 $seci_amount = floatval($order->get_meta('seci_amount'));
             $total_amount = $total_amount + $seci_amount;
    		 $seci_txid = $order->get_meta('seci_txid');
    		 $order_id = $order->get_id();
             $table .= '<tr><td>' . $count . '</td>';
             $table .= '<td>' . $order->get_date_created() . '</td>';
             $table .= '<td><a href="/wp-admin/post.php?post=' . $order_id . '&action=edit">'. $order_id . '</a></td>';
             $table .= '<td>' . $order->get_customer_id() . '</td>';
             $table .= '<td><a target="_blank" href="http://explorer.seci.io/tx/' . $seci_txid . '">' . $seci_txid  . '</a></td>';
             $table .= '<td>' . $seci_amount . '</td>';
             $table .= '<td><a target="_blank" href="http://explorer.seci.io/address/' . $seci_address . '">'. $seci_address .'</a></td>';
             $table .= '<td>' . $order->get_status() . '</td></tr>';
    	}
    		$table .= '</tbody></table></div>';
    	 	echo __( '<div class="sp-admin-total"><strong>Total Received: ' . $total_amount . ' SECI</strong></div>', 'wc-gateway-secipay' );
    	 	echo __( $table , 'wc-gateway-secipay' );

	}

	public function sp_options_page(  ) { 
		?>
		<form action='options.php' method='post'>

			<h2>SeciPay</h2>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );

			submit_button();
			?>

		</form>
		<?php
	}
}