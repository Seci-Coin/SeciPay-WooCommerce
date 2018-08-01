<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
class SeciPayCoins {

	public function __construct( ) {
		// Initialise settings
		// add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'cpt_secipaycoin') );
		add_action( 'add_meta_boxes',array( $this,  'secipay_coin_metaboxes') );

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