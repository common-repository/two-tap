<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://twotap.com
 * @since      1.0.0
 *
 * @package    Two_Tap
 * @subpackage Two_Tap/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Two_Tap
 * @subpackage Two_Tap/public
 * @author     Two Tap <support@twotap.com>
 */
class Two_Tap_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Two_Tap_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Two_Tap_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/two-tap-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Two_Tap_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Two_Tap_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/two-tap-public.js', array( 'jquery', 'underscore' ), $this->version, false );

	}

	/**
	 * Filtering through the order item meta data.
	 *
	 * @param  array $meta Meta data.
	 *
	 * @return array
	 */
	public function woocommerce_order_item_get_formatted_meta_data($meta)
	{
		array_walk($meta, function($item){
			if( $item->key == TT_META_TWOTAP_STATUS ){
				$item->display_key = 'Two Tap status';
			}
			if( $item->key == TT_META_TWOTAP_SITE_ID ){
				$item->display_key = 'Two Tap site ID';
			}
			if( $item->key == TT_META_TWOTAP_PRODUCT_MD5 ){
				$item->display_key = 'Two Tap product MD5';
			}
			$item->display_value = '<p>' . twotap_pretty_status(strip_tags($item->display_value)) . '</p>';
		    return $item;
		});
		if( is_admin() ){
			return $meta;
		}
		$hidden_fields = [TT_META_TWOTAP_STATUS, TT_META_TWOTAP_SITE_ID, TT_META_TWOTAP_PRODUCT_MD5];
		return array_filter($meta, function($item) use($hidden_fields){
		    return !in_array($item->key, $hidden_fields);
		});
	}

}
