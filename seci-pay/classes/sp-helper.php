<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
class SeciPayCoins {

	public function __construct( ) {
		// Initialise settings
		// add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'cpt_secipaycoin') );
		add_action( 'add_meta_boxes',array( $this,  'secipay_coin_metaboxes') );
		 register_activation_hook( __FILE__ , array( $this,'secipay_create_coin_data'));

	}
	/*** Initialise settings 
	 * @return void
	 */
	public function cpt_secipaycoin() {
			/**
			 * Post Type: Coins.
			 */
			$labels = array(
				"name" => __( "Coins", "twentyseventeen" ),
				"singular_name" => __( "Coin", "twentyseventeen" ),
			);
			$args = array(
				"label" => __( "Coins", "twentyseventeen" ),
				"labels" => $labels,
				"description" => "",
				"public" => false,
				"publicly_queryable" => false,
				"show_ui" => true,
				"show_in_rest" => false,
				"rest_base" => "",
				"has_archive" => false,
				"show_in_menu" => false,
				"show_in_nav_menus" => false,
				"exclude_from_search" => true,
				"capability_type" => "post",
				"map_meta_cap" => true,
				"hierarchical" => false,
				"rewrite" => array( "slug" => "secipaycoin", "with_front" => true ),
				"query_var" => true,
				"supports" => array( "title", "custom-fields", "thumbnail" ),
			);
			register_post_type( "secipaycoin", $args );
	}
	


	/**
	 * Output the HTML for the metabox.
	 */
	public function secipay_create_coin_data() {
			  global $wpdb;
			  $coins = [];
			  $coins[] = array("title"=>'Bitcoin', "name"=>'bitcoin', "coin_rpc"=>'', "rpc_port"=>'',"rpc_username"=>'',"rpc_password"=>'',"confirmations"=>'',"exchange_url"=>'https://api.coingecko.com/api/v3/coins/bitcoin?localization=en', "explorer_url"=>'https://blockexplorer.com',"enabled"=>'false',"cold_storage"=>'false',"confirmations"=>'0',"cold_storage_max_amount"=>'0',"cold_stoage_wallet_address"=>'');
			  $coins[] = array("title"=>'Bitcoin', "name"=>'bitcoin', "coin_rpc"=>'', "rpc_port"=>'',"rpc_username"=>'',"rpc_password"=>'',"confirmations"=>'',"exchange_url"=>'https://api.coingecko.com/api/v3/coins/bitcoin?localization=en', "explorer_url"=>'https://blockexplorer.com',"enabled"=>'false',"cold_storage"=>'false',"confirmations"=>'0',"cold_storage_max_amount"=>'0',"cold_stoage_wallet_address"=>'');
			  $coins[] = array("title"=>'Bitcoin', "name"=>'bitcoin', "coin_rpc"=>'', "rpc_port"=>'',"rpc_username"=>'',"rpc_password"=>'',"confirmations"=>'',"exchange_url"=>'https://api.coingecko.com/api/v3/coins/bitcoin?localization=en', "explorer_url"=>'https://blockexplorer.com',"enabled"=>'false',"cold_storage"=>'false',"confirmations"=>'0',"cold_storage_max_amount"=>'0',"cold_stoage_wallet_address"=>'');
			 foreach ($coins as $coin){

				$existing_coin = get_post_ID_by_title($coin->title, OBJECT, 'secipaycoin');
				if ( ! $existing_coin ) {

					     // Create post object
		            $newCoin = array();
		            $newCoin['post_title'] = $coin->title;
		            $newCoin['post_status'] = 'publish';
		            $newCoin['post_type'] = 'secipaycoin';
					$newCoin['meta_input'] = array(
									'coin_name'=> $coin->coin_name,
									'coin_rpc'=> $coin->coin_rpc,
									'confirmations'=> $coin->confirmations,
									'exchange_rate'=> $coin->exchange_rate,
									'rpc_port'=> $coin->rpc_port,
									'rpc_username'=> $coin->rpc_username,
									'rpc_password'=> $coin->rpc_password,
									'explorer_url'=> $coin->explorer_url,
									'cold_stoage_wallet_address'=> $coin->cold_stoage_wallet_address,
									'cold_storage'=> $coin->cold_storage,
									'cold_storage_max_amount'=> $coin->cold_storage_max_amount,
									'exchange_url'=> $coin->exchange_url,
							);
		            
         				$newCoinID = wp_insert_post( $newCoin );

				}

			 }


       	




	}
		/**
	 * Adds a metabox to the right side of the screen under the â€œPublishâ€ box
	 */
	function secipay_coin_metaboxes() {
		add_meta_box(
			'secipay_coin_data',
			'Settings',
			array( $this, 'secipay_coin_data'),
			'secipaycoin',
			'normal',
			'default'
		);
	}

		/**
	 * Output the HTML for the metabox.
	 */
	public function secipay_coin_data() {
		global $post;
		// Nonce field to validate form request came from current site
		wp_nonce_field( basename( __FILE__ ), 'secipay_coin_data_fields' );
		$coin_name = get_post_meta( $post->ID, 'coin_name', true );
		$coin_rpc = get_post_meta( $post->ID, 'coin_rpc', true );
		$confirmations = get_post_meta( $post->ID, 'confirmations', true );
		$exchange_rate = get_post_meta( $post->ID, 'exchange_rate', true );
		$rpc_password = get_post_meta( $post->ID, 'rpc_password', true );
		$rpc_port = get_post_meta( $post->ID, 'rpc_port', true );
		$rpc_username = get_post_meta( $post->ID, 'rpc_username', true );
		$explorer_url = get_post_meta( $post->ID, 'explorer_url', true );
		$explorer_type = get_post_meta( $post->ID, 'explorer_type', true );
		$block_explorer_url = get_post_meta( $post->ID, 'block_explorer_url', true );

		// Output the field
		echo '<div class="wrap"><div id="icon-options-general" class="icon32"></div>';
		echo '<label>coin_name<input type="text" name="coin_name" value="' . esc_textarea( $coin_name )  . '" class="widefat"></label>';
		echo '<label>coin_rpc<input type="text" name="coin_rpc" value="' . esc_textarea( $coin_rpc )  . '" class="widefat"></label>';
		echo '<label>confirmations<input type="text" name="confirmations" value="' . esc_textarea( $confirmations )  . '" class="widefat"></label>';
		echo '<label>exchange_rate<input type="text" name="exchange_rate" value="' . esc_textarea( $exchange_rate )  . '" class="widefat"></label>';
		echo '<label>rpc_password<input type="text" name="rpc_password" value="' . esc_textarea( $rpc_password )  . '" class="widefat"></label>';
		echo '<label>rpc_port<input type="text" name="rpc_port" value="' . esc_textarea( $rpc_port )  . '" class="widefat"></label>';
		echo '<label>rpc_username<input type="text" name="rpc_username" value="' . esc_textarea( $rpc_username )  . '" class="widefat"></label>';
		echo '<label>explorer_url<input type="text" name="explorer_url" value="' . esc_textarea( $explorer_url )  . '" class="widefat"></label>';
		echo '<label>explorer_type<input type="text" name="explorer_type" value="' . esc_textarea( $explorer_type )  . '" class="widefat"></label>';
		echo '<label>block_explorer_url<input type="text" name="block_explorer_type" value="' . esc_textarea( $block_explorer_url )  . '" class="widefat"></label>';
		echo '</div>';

	}



}