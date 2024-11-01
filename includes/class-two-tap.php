<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://twotap.com
 * @since      1.0.0
 *
 * @package    Two_Tap
 * @subpackage Two_Tap/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Two_Tap
 * @subpackage Two_Tap/includes
 * @author     Two Tap <support@twotap.com>
 */
class Two_Tap {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Two_Tap_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public $shipping_logistics = [
		'own_logistics' => [
			'name' => 'own_logistics',
			'title' => 'Your own logistics',
			'description' => '',
			'available' => true,
			'pros' => ['+ You control the international shipping prices and delivery times.', '+ Two Tap cashback.'],
			'cons' => ['- Difficult to manage on your end.']
		],
		'twotap_logistics_ship_to_office' => [
			'name' => 'twotap_logistics_ship_to_office',
			'title' => 'Two Tap logistics - shipping to your international office',
			'description' => '',
			'available' => false,
			'pros' => ['+ Cheaper international shipping prices due to consolidation.', '+ In some countries bulk customs clearence is easier.'],
			'cons' => ['- You have to handle last leg delivery.']
		],
		'twotap_logistics_ship_to_customer' => [
			'name' => 'twotap_logistics_ship_to_customer',
			'title' => 'Two Tap logistics - shipping to end customer',
			'description' => '',
			'available' => false,
			'pros' => ['+ No need to do anything from your end'],
			'cons' => ['- More expensive shipping prices']
		],
	];


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'two-tap';
		$this->version = '0.2.13';

		$this->load_dependencies();

		global $twotap_notices;
		$twotap_notices = new Two_Tap_Notices();

		$this->init_apis();

		$this->set_locale();

		$this->define_admin_hooks();

		/**
		 * Check if WooCommerce is active
		 **/
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

			$this->define_post_types_hooks();
			$this->init_admin_classes();

			$this->define_public_hooks();
		}

		do_action( 'two_tap_loaded' );
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Two_Tap_Loader. Orchestrates the hooks of the plugin.
	 * - Two_Tap_i18n. Defines internationalization functionality.
	 * - Two_Tap_Admin. Defines all hooks for the admin area.
	 * - Two_Tap_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The file responsible for autoloading the other classes
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/vendor/autoload.php';

		$this->loader = new Two_Tap_Loader();

	}

	public function init_apis(){
		global $tt_api;
		$this->api = $tt_api;

		global $wc_api;
		$this->wc_api = $wc_api;
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Two_Tap_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Two_Tap_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Two_Tap_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );

		/**
		 * Check if WooCommerce is active
		 **/
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

			$this->loader->add_filter( 'cron_schedules', $plugin_admin, 'twotap_add_crons_schedules', 1);

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_global_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
			$this->loader->add_action( 'load-toplevel_page_twotap_purchase_page', $plugin_admin, 'force_enqueue_scripts' );
			$this->loader->add_action( 'load-toplevel_page_twotap_products_page', $plugin_admin, 'force_enqueue_scripts' );
			$this->loader->add_action( 'load-two-tap_page_twotap_settings_page', $plugin_admin, 'force_enqueue_scripts' );
			$this->loader->add_action( 'load-toplevel_page_twotap_products_page', $plugin_admin, 'force_enqueue_styles' );
			$this->loader->add_action( 'load-two-tap_page_twotap_settings_page', $plugin_admin, 'force_enqueue_styles' );

			/**
			 * Process the checkout
			 */
			$this->loader->add_filter( 'twotap_translate_input_fields', $plugin_admin, 'translate_input_fields' );
			$this->loader->add_filter( 'twotap_customer_session_to_twotap_input_fields', $plugin_admin, 'customer_session_to_twotap_input_fields' );
			$this->loader->add_action( 'woocommerce_checkout_process', $plugin_admin, 'check_input_fields_with_twotap' );
			$this->loader->add_action( 'woocommerce_billing_fields', $plugin_admin, 'add_mandatory_billing_fields' );
			$this->loader->add_action( 'woocommerce_shipping_fields', $plugin_admin, 'add_mandatory_shipping_fields' );
			/**
			 * @todo check if woocommerce is installed
			 */
			$this->loader->add_filter( 'admin_body_class', $plugin_admin, 'add_body_class' );
			$this->loader->add_action('twotap_cron_everysecond', $plugin_admin, 'cron_everysecond');
			$this->loader->add_action( 'twotap_cron_hourly', $plugin_admin, 'cron_hourly' );
			$this->loader->add_action( 'twotap_cron_daily', $plugin_admin, 'cron_daily' );
			$this->loader->add_action('twotap_cron_twicedaily', $plugin_admin, 'cron_twicedaily');

			$this->loader->add_action( 'admin_menu', $plugin_admin, 'twotap_menu' );
			$this->loader->add_action( 'admin_head', $plugin_admin, 'menu_highlight' );
			$this->loader->add_action( 'wp_ajax_twotap_check_wc_api', $plugin_admin, 'twotap_check_wc_api' );
			$this->loader->add_action( 'wp_ajax_twotap_check_wc_webhooks', $plugin_admin, 'twotap_check_wc_webhooks' );
			$this->loader->add_action( 'wp_ajax_twotap_setup_webhooks', $plugin_admin, 'twotap_setup_webhooks' );

			if ( tt_plugin_enabled() ) {
				$settings_page = new Two_Tap_Settings_Page( $this->get_plugin_name(), $this->get_version() );
				$this->loader->add_action( 'admin_init', $settings_page, 'twotap_general_settings_init' );
				$this->loader->add_action( 'admin_init', $settings_page, 'twotap_logistics_settings_init' );
				$this->loader->add_action( 'admin_init', $settings_page, 'twotap_billing_settings_init' );
				$this->loader->add_action( 'admin_init', $settings_page, 'twotap_api_settings_init' );
				$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
			}

			$setup_helper = new Two_Tap_Admin_Setup_Helper( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'wp_ajax_twotap_setup_create_account', $setup_helper, 'twotap_setup_create_account' );
			$this->loader->add_action( 'wp_ajax_twotap_setup_save_tokens', $setup_helper, 'twotap_setup_save_tokens' );
			$this->loader->add_action( 'wp_ajax_twotap_setup_save_wc_tokens', $setup_helper, 'twotap_setup_save_wc_tokens' );
			$this->loader->add_action( 'wp_ajax_twotap_setup_plugin_settings', $setup_helper, 'twotap_setup_plugin_settings' );
			$this->loader->add_action( 'wp_ajax_twotap_get_plans', $setup_helper, 'twotap_get_plans' );

			// rest api
			$this->loader->add_action( 'rest_api_init', $setup_helper, 'add_woocommerce_keys_route' );
		}

	}


	private function define_post_types_hooks() {
		if ( !tt_plugin_enabled() ) {
			return;
		}

		$cart_post_type = new Two_Tap_Cart_Post_Type( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'init', $cart_post_type, 'cart_post_type' );
		$this->loader->add_action( 'init', $cart_post_type, 'cart_statuses' );
		$this->loader->add_filter('manage_tt_cart_posts_columns', $cart_post_type, 'tt_cart_posts_columns');
		$this->loader->add_action('manage_tt_cart_posts_custom_column', $cart_post_type, 'render_tt_cart_posts_columns', 10, 2);

		$product_post_type = new Two_Tap_Product_Post_Type( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $product_post_type, 'product_statuses' );
		$this->loader->add_action( 'init', $product_post_type, 'taxonomies' );
		$this->loader->add_filter( 'views_edit-' . TT_POST_TYPE_PRODUCT, $product_post_type, 'subsubsub_edit' );
		$this->loader->add_filter( 'manage_product_posts_columns', $product_post_type, 'product_columns', 11 );

		$this->loader->add_action( 'manage_product_posts_custom_column', $product_post_type, 'render_product_columns', 2 );

		$this->loader->add_action('add_meta_boxes', $product_post_type, 'meta_boxes', 10, 2);
		$this->loader->add_action('save_post', $product_post_type, 'save_twotap_options', 1, 2); // save the custom fields

		$purchase_post_type = new Two_Tap_Purchase_Post_Type( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $purchase_post_type, 'purchase_post_type' );
		$this->loader->add_action( 'init', $purchase_post_type, 'purchase_statuses' );
		$this->loader->add_filter('manage_tt_purchase_posts_columns', $purchase_post_type, 'tt_purchase_posts_columns');
		$this->loader->add_action('manage_tt_purchase_posts_custom_column', $purchase_post_type, 'render_tt_purchase_posts_columns', 10, 2);

		$shop_order_post_type = new Two_Tap_Shop_Order_Post_Type( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $shop_order_post_type, 'render_shop_order_columns', 2 );

		$this->loader->add_filter( 'manage_shop_order_posts_columns', $shop_order_post_type, 'shop_order_columns', 11 );
		$this->loader->add_action('add_meta_boxes', $shop_order_post_type, 'meta_boxes', 10, 2 );
		$this->loader->add_action( 'init', $shop_order_post_type, 'taxonomies' );
		$this->loader->add_filter( 'views_edit-' . TT_POST_TYPE_ORDER, $shop_order_post_type, 'subsubsub_edit' );
	}

	public function init_admin_classes()
	{
		$product = new Two_Tap_Product( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_ajax_twotap_products_perform_search', $product, 'twotap_products_perform_search' );
		$this->loader->add_action( 'wp_ajax_twotap_sync_products', $product, 'sync_products' );
		$this->loader->add_action( 'wp_ajax_twotap_import_filtered_products', $product, 'twotap_import_filtered_products' );
		$this->loader->add_action( 'wp_ajax_twotap_product_scroll', $product, 'ajax_product_scroll' );
		$this->loader->add_action( 'wp_ajax_twotap_add_product_to_shop', $product, 'add_product' );
		$this->loader->add_action( 'wp_ajax_twotap_jobs_remaining', $product, 'twotap_jobs_remaining_json' );
		$this->loader->add_action( 'wp_ajax_twotap_refresh_product', $product, 'twotap_refresh_product' );

		$this->loader->add_action( 'get_scroll_products', $product, 'get_scroll_products', 1, 4 );
		$this->loader->add_action('twotap_cron_daily_resync', $product, 'sync_products');

		// cron action
		$this->loader->add_action( 'refresh_product_info', $product, 'refresh_product_info', 1, 3 );
		$this->loader->add_action( 'add_product_to_db_delayed', $product, 'add_product_to_db' );

		$cart = new Two_Tap_Cart( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'woocommerce_add_cart_item_data', $cart, 'add_cart_item', 10, 3 );

		$this->loader->add_filter( 'woocommerce_add_to_cart', $cart, 'add_to_cart' );
		$this->loader->add_action( 'woocommerce_cart_item_removed', $cart, 'remove_cart_item', 10, 2 );
		$this->loader->add_filter( 'woocommerce_update_cart_action_cart_updated', $cart, 'cart_updated' );
		$this->loader->add_action( 'wp_ajax_twotap_refresh_cart_status', $cart, 'refresh_cart_status_ajax' );

		// rest api
		$this->loader->add_action( 'rest_api_init', $cart, 'add_cart_finished_url' );

		$purchase = new Two_Tap_Purchase( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_ajax_twotap_refresh_purchase_status', $purchase, 'ajax_refresh_purchase_status' );
		$this->loader->add_action( 'wp_ajax_twotap_send_purchase', $purchase, 'ajax_send_purchase' );

		// rest api
		$this->loader->add_action( 'rest_api_init', $purchase, 'add_purchase_confirm_url' );
		$this->loader->add_action( 'rest_api_init', $purchase, 'add_purchase_update_url' );
		$this->loader->add_action( 'rest_api_init', $purchase, 'add_wc_order_created_url' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Two_Tap_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_order_item_get_formatted_meta_data', $plugin_public, 'woocommerce_order_item_get_formatted_meta_data' );

		$shipping_estimates = new Two_Tap_Shipping_Estimates( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_ajax_twotap_check_estimates', $shipping_estimates, 'ajax_check_estimates' );
		$this->loader->add_action( 'wp_enqueue_scripts', $shipping_estimates, 'enqueue_scripts' );

		$this->loader->add_action( 'woocommerce_cart_totals_before_order_total', $shipping_estimates, 'woocommerce_cart_totals_before_order_total' );
		$this->loader->add_action( 'woocommerce_review_order_before_order_total', $shipping_estimates, 'woocommerce_review_order_before_order_total' );

		$this->loader->add_action( 'wc_package_rates', $shipping_estimates, 'wc_package_rates', 10, 2 );
		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $shipping_estimates, 'woocommerce_cart_calculate_fees' );
		$this->loader->add_filter( 'woocommerce_shipping_packages', $shipping_estimates, 'woocommerce_shipping_packages' );
		$this->loader->add_filter( 'woocommerce_thankyou', $shipping_estimates, 'woocommerce_thankyou' );

		// disable shipping info cache
		$this->loader->add_filter('woocommerce_checkout_update_order_review', $shipping_estimates, 'clear_wc_shipping_rates_cache' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
		$this->ensure_commands_run();
		if(TT_DEBUG && file_exists(TT_ABSPATH . '/dev/dev.php')){ include(TT_ABSPATH . '/dev/dev.php'); }
	}

	public function ensure_commands_run()
	{
		if ( ! wp_next_scheduled( 'twotap_cron_everysecond' ) ) {
			wp_schedule_event(time(), 'everysecond', 'twotap_cron_everysecond');
		}

		if ( ! wp_next_scheduled( 'twotap_cron_everytenminutes' ) ) {
			wp_schedule_event(time(), 'everytenminutes', 'twotap_cron_everytenminutes');
		}

		if ( ! wp_next_scheduled( 'twotap_cron_hourly' ) ) {
			wp_schedule_event(time(), 'hourly', 'twotap_cron_hourly');
		}

		if ( ! wp_next_scheduled( 'twotap_cron_twicedaily' ) ) {
			wp_schedule_event(strtotime('today midnight') + rand(0, 3600), 'twicedaily', 'twotap_cron_twicedaily');
			wp_schedule_single_event( time(), 'twotap_cron_twicedaily' );
		}

		if ( ! wp_next_scheduled( 'twotap_cron_daily' ) ) {
			wp_schedule_event(strtotime('today midnight') + rand(0, 3600), 'daily', 'twotap_cron_daily');
			wp_schedule_single_event( time(), 'twotap_cron_daily' );
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Two_Tap_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}