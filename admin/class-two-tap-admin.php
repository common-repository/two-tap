<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://twotap.com
 * @since      1.0.0
 *
 * @package    Two_Tap
 * @subpackage Two_Tap/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Two_Tap
 * @subpackage Two_Tap/admin
 * @author     Two Tap <support@twotap.com>
 */
class Two_Tap_Admin {

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
	 * @param    string $plugin_name The name of this plugin.
	 * @param    string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		global $tt_api;
		$this->api = $tt_api;

		global $wc_api;
		$this->wc_api = $wc_api;

		do_action( 'two_tap_admin_loaded' );

	}

	/**
	 * Enqueue global styles
	 *
	 * @param string $hook The hook.
	 * @return void
	 */
	public function enqueue_global_styles( $hook = null ) {
		do_action( 'two_tap_admin_enqueue_global_styles' );
		wp_enqueue_style( $this->plugin_name . '_global', TT_PLUGIN_URL . '/admin/css/two-tap-global.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @param string $hook The hook.
	 * @since    1.0.0
	 */
	public function enqueue_styles( $hook = null ) {
		do_action( 'two_tap_admin_enqueue_styles' );

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

		wp_enqueue_style( 'twotap_css_bootstrap', TT_PLUGIN_URL . '/admin/css/vendor/bootstrap.css', [], $this->version, 'all' );
		wp_enqueue_style( 'twotap_css_grid', TT_PLUGIN_URL . '/admin/css/vendor/grid.css', [] );
		wp_enqueue_style( 'jquery-ui', TT_PLUGIN_URL . '/admin/css/vendor/jquery-ui.min.css' );
		wp_enqueue_style( 'twotap-products-page', TT_PLUGIN_URL . '/admin/css/products-page.css' );
		wp_enqueue_style( $this->plugin_name, TT_PLUGIN_URL . '/admin/css/two-tap-admin.css', [], $this->version, 'all' );

	}

	/**
	 * We're enqueing styles here for the custom menu pages were we can't add the to the loader
	 *
	 * @return void
	 */
	public function force_enqueue_styles() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @param string $hook The hook.
	 * @since    1.0.0
	 */
	public function enqueue_scripts( $hook = null ) {

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

		if ( $this->should_enqueue( $hook ) ) {
			$this->two_tap_enqueue_admin_scripts();
		}
	}

	/**
	 * Decides if scripts or styles should be enqueued
	 *
	 * @param  string $hook The hook.
	 * @return void
	 */
	public function should_enqueue( $hook = null ) {
		global $wp_query, $post;
		$post_types = [ 'tt_cart', 'tt_purchase' ];
		$allowed_hooks = [ 'edit.php', 'post.php' ];
		if ( in_array( $hook, $allowed_hooks ) ) {
			if ( (isset( $wp_query ) && ! is_null( $wp_query ) && in_array( $wp_query->query['post_type'], $post_types )) || (isset($post) && in_array($post->post_type, $post_types)) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * We're enqueing scripts here for the custom menu pages were we can't add the to the loader
	 *
	 * @return void
	 */
	public function force_enqueue_scripts() {
		add_action( 'admin_enqueue_scripts', [ $this, 'two_tap_enqueue_admin_scripts' ] );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @return void
	 */
	public function two_tap_enqueue_admin_scripts() {
		do_action( 'two_tap_admin_enqueue_scripts' );
		wp_enqueue_script( $this->plugin_name, TT_PLUGIN_URL . '/admin/js/two-tap-admin.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( 'twotap_js_vue', TT_PLUGIN_URL . '/admin/js/vendor/vue.js', false );
		wp_enqueue_script( 'twotap_js_vue_resource', TT_PLUGIN_URL . '/admin/js/vendor/vue-resource.min.js', false );
		wp_enqueue_script( 'twotap_js_vue_lazy_load', TT_PLUGIN_URL . '/admin/js/vendor/vue-lazy-load.min.js', false );
		wp_enqueue_script( 'twotap_js_utils', TT_PLUGIN_URL . '/admin/js/utils.js', false );
		wp_enqueue_script( 'twotap_js_vue_components', TT_PLUGIN_URL . '/admin/js/vue-components.js', false, false, true );
		wp_enqueue_script( 'twotap_js_settings_page', TT_PLUGIN_URL . '/admin/js/settings-page.js', false );

		wp_localize_script( 'twotap_js_settings_page', 'tt_settings_vars', [
			'woocommerce_currency' => get_woocommerce_currency(),
			'woocommerce_currency_symbol' => get_woocommerce_currency_symbol(),
		]);

		wp_enqueue_script( 'twotap_js_carts_page', TT_PLUGIN_URL . '/admin/js/carts-page.js', false );
		wp_enqueue_script( 'twotap_js_purchases_page', TT_PLUGIN_URL . '/admin/js/purchases-page.js', false );
		wp_enqueue_script( 'twotap_js_products_page', TT_PLUGIN_URL . '/admin/js/products-page.js', true );
		wp_enqueue_script( 'twotap_js_treeview', TT_PLUGIN_URL . '/admin/js/vendor/treeview.js', false );
		wp_enqueue_script( 'twotap_js_pagination', TT_PLUGIN_URL . '/admin/js/vendor/jquery.pagination.js', false );
		wp_enqueue_script( 'twotap_js_bootstrap', TT_PLUGIN_URL . '/admin/js/vendor/bootstrap.js', false );
		wp_enqueue_script( 'jquery-ui', TT_PLUGIN_URL . '/admin/js/vendor/jquery-ui.min.js', [ 'jquery-core', 'underscore' ], false, false );
	}

	/**
	 * Add Two Tap cron schedules
	 *
	 * @param  array $schedules The schedules.
	 * @return array
	 */
	public function twotap_add_crons_schedules( $schedules ) {
		$schedules['everysecond'] = array(
			'interval' => 1,
			'display' => __( 'Every second' ),
		);
		$schedules['everytenminutes'] = array(
			'interval' => 600,
			'display' => __( 'Every 10 minutes' ),
		);
		return $schedules;
	}

	/**
	 * Cron that runs every second
	 *
	 * @return void
	 */
	public function cron_everysecond() {
		if ( tt_plugin_enabled() ) {
			$tt_product = new Two_Tap_Product( $this->plugin_name, $this->version );
			$tt_product->refresh_temporary_products();
			$tt_product->resync_products();
		}
	}

	/**
	 * Cron that runs every 10 minutes
	 *
	 * @return void
	 */
	public function cron_everytenminutes() {
		if ( tt_plugin_enabled() ) {
			$this->check_wc_api_enabled();
		}
	}

	/**
	 * Cron that runs every hour
	 *
	 * @return void
	 */
	public function cron_hourly() {
		if ( tt_plugin_enabled() ) {
			$this->check_wc_webhooks_ok();
		}
	}

	/**
	 * Cron that runs twice a day
	 *
	 * @return void
	 */
	public function cron_twicedaily() {
		if ( tt_plugin_enabled() ) {
			$this->ensure_supported_sites_synced();
		}
	}

	/**
	 * Cron that runs daily
	 *
	 * @return void
	 */
	public function cron_daily() {
		if ( tt_plugin_enabled() ) {
			$this->daily_cleanup();

			$tt_product = new Two_Tap_Product( $this->plugin_name, $this->version );
			$tt_product->reset_sync_products_option();
		}
	}

	/**
	 * Update the WooCommerce API enabled option
	 *
	 * @return boolean
	 */
	public function check_wc_api_enabled() {
		global $wc_api;
		if(is_null($wc_api)){
			return false;
		}
		try {
			$response = $wc_api->get( '' );
		} catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
			$response = null;
			l()->error( 'Bad WC api response in WC enabled test.', $e->getResponse() );
		}

		$enabled = !is_null( $response );
		update_option( TT_OPTION_WC_API_ENABLED, $enabled);

		return $enabled;
	}

	/**
	 * Checks to see if the WooCommerce hooks were created and not duplicated
	 *
	 * @return boolean
	 */
	public function check_wc_webhooks_ok() {
		$webhooks_ok = false;
		if ( wc_tokens_set() && wc_api_enabled() ) {
			try {
				$response = $this->wc_api->get( 'webhooks' );
			} catch ( Exception $e ) {
				$response = [];
				l( 'WC Webhooks weren\'t created.', $e->getResponse() );
			}

			$number_of_webhooks = 0;
			if(count($response) > 0){
				foreach ( $response as $hook ) {
					if ( site_url( 'wp-json/two_tap/wc_order_created' ) === $hook['delivery_url'] ) {
						// hook already created.
						$webhooks_ok = true;
						$number_of_webhooks++;
					}
				}
				if ($number_of_webhooks > 1) {
					$webhooks_ok = false;
				}
			}
		}

		update_option( TT_OPTION_WEBHOOKS_OK, $webhooks_ok );

		return $webhooks_ok;
	}

	/**
	 * Ensures the supported sites are imported & cached for 13 hours
	 *
	 * @param  boolean $force If it should be synced anyway.
	 * @return void
	 */
	public function ensure_supported_sites_synced( $force = false ) {
		do_action( 'two_tap_admin_ensure_supported_sites_synced' );
		$supported_sites = get_transient( TT_TRANSIENT_KEY_SUPPORTED_SITES );

		if ( tt_api_enabled() && ( !$supported_sites || $force ) ) {
			global $tt_api;
			try {
				$sites_response = $tt_api->utils()->supportedSites();
			} catch (Exception $e) {
				l()->error( 'Couldn\'t retrieve the supported sites.', $e->getResponse() );
				$sites_response = null;
			}

			if (is_null($sites_response)){
				return;
			}

			$sites = [];
			foreach ( $sites_response as $site ) {
				$sites[ $site['id'] ] = $site;
			}

			if ( is_array( $sites ) && ! empty( $sites ) ) {
				set_transient( TT_TRANSIENT_KEY_SUPPORTED_SITES, $sites, TT_THIRTEEN_HOURS_IN_SECONDS );
				l( 'Set supported sites with ' . count( $sites ) . ' entries.' );
			} else {
				set_transient( TT_TRANSIENT_KEY_SUPPORTED_SITES, [], TT_THIRTEEN_HOURS_IN_SECONDS );
				l()->warning( 'Set supported sites with no entries.' );
				global $twotap_notices;
				$twotap_notices->add_notice( 'supported_sites_error', 'Supported sites not updated properly.', 'error' );
			}
		}
	}

	/**
	 * Does the daily cleanup of carts & others
	 *
	 * @return void
	 */
	public function daily_cleanup() {
		$status = [];
		l( 'Running daily clean-up.' );

		// Removing carts older than two weeks that don't belong to a purchase.
		$args = [
			'post_type' => TT_POST_TYPE_CART,
			'post_status' => twotap_db_cart_statuses(),
			'posts_per_page' => 100,
			'date_query' => [
				'column'  => 'post_date',
				'before'   => '-2 weeks',
			],
			'meta_query' => [
				[
					'key' => 'order_id',
					'compare' => 'NOT EXISTS',
				],
			],
		];
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$carts = $query->get_posts();
			$status[] = 'Removing ' . count( $carts ) . ' old carts.';
			foreach ( $carts as $cart ) {
				wp_delete_post( $cart->ID );
			}
		} else {
			$status[] = 'No old carts to remove.';
		}
		l( 'Daily clean-up status.', $status );
	}

	/**
	 * Check if WooCommerce API is set-up
	 *
	 * @return void
	 */
	public function twotap_check_wc_api() {
		$api_ok = false;

		if($this->check_wc_api_enabled()){
			wp_send_json([
				'success' => true,
				'message' => '✅ WooCommerce API is accesible.',
			]);
		} else {
			wp_send_json([
				'success' => false,
				'message' => '❗️WooCommerce API is not accesible by Two Tap.<br/>Please check your WooCommerce keys.',
			]);
		}
		wp_die();
	}

	/**
	 * Check if WooCommerce Webhooks are set-up
	 *
	 * @return void
	 */
	public function twotap_check_wc_webhooks() {
		$api_ok = false;

		if($this->check_wc_webhooks_ok()){
			wp_send_json([
				'success' => true,
				'webhooks_installed' => true,
				'wc_api_ok' => true,
				'message' => '✅ WooCommerce Webhooks installed.',
			]);
			wp_die();
		}

		if($this->check_wc_api_enabled()){
			wp_send_json([
				'success' => false,
				'webhooks_installed' => false,
				'wc_api_ok' => true,
				'message' => '❗️WooCommerce Webhooks were not installed.<br/>Please try and install them again.',
			]);
		} else {
			wp_send_json([
				'success' => false,
				'webhooks_installed' => false,
				'wc_api_ok' => false,
				'message' => '❗️WooCommerce Webhooks were not installed.<br/>Check that the WooCommerce API is accesible.',
			]);
		}

		wp_die();
	}

	/**
	 * Sets the WooCommerce webhooks
	 *
	 * @return void
	 */
	public function twotap_setup_webhooks() {
		$webhooks_created = false;

		if ( $this->check_wc_webhooks_ok() ) {
			wp_send_json([
				'success' => true,
				'message' => 'Webhooks are set up ok.',
			]);
			wp_die();
		}

		$webhooks_ok = false;
		if ( wc_tokens_set() && wc_api_enabled() ) {

			try {
				$response = $this->wc_api->get( 'webhooks' );
			} catch ( Exception $e ) {
				$response = [];
				l( 'WC Webhooks weren\'t created.', $e->getResponse() );
			}

			$webhooks = [];
			if(count($response) > 0){
				foreach ( $response as $hook ) {
					if ( site_url( 'wp-json/two_tap/wc_order_created' ) == $hook['delivery_url'] ) {
						// hook already created.
						$webhooks_ok = true;
						$webhooks[] = $hook;
					}
				}
				l('Webhooks count', (string)count($webhooks));
				if (count($webhooks) > 1) {
					// eliminating the first one
					array_shift($webhooks);
					$to_delete = array_map(function($hook){
					    return $hook['id'];
					}, $webhooks);
					try {
						$response = $this->wc_api->post( 'webhooks/batch', ['delete' => $to_delete] );
					} catch ( Exception $e ) {
						$response = null;
						l( 'WC Webhooks weren\'t deleted.', $e->getResponse() );
					}
					if ($response) {
						$webhooks_ok = true;
					}
				}else{
					$should_create_hooks = true;
				}
			} else {
				$should_create_hooks = true;
			}

			if ($should_create_hooks) {
				l('Tokens are set. Trying to create webhooks.');
				try {
					// setup order.created webhook.
					$data = [
						'name' => 'Order created for Two Tap',
						'topic' => 'order.created',
						'status' => 'active',
						'secret' => 'twotap_wc_secret',
						'delivery_url' => site_url( 'wp-json/two_tap/wc_order_created' ),
					];
					$response = $this->wc_api->post( 'webhooks', $data );
					$webhooks_ok = true;
				} catch ( Exception $e ) {
					l( 'WC Webhooks weren\'t created.', $e->getResponse() );
					if($e->getCode() == 401){
						wp_send_json([
							'success' => false,
							'message' => 'Webhooks couldn\'t be set fixed. The API has only read permissions. You must create <a href="/wp-admin/admin.php?page=wc-settings&tab=api&section=keys">new API keys</a> with Read/Write permissions.',
						]);
						wp_die();
					}
				}
			}

		} else {
			l()->error('Tokens are not set. Can\'t create webhooks.');
		}
		update_option( TT_OPTION_WEBHOOKS_OK, $webhooks_ok );

		if ( $webhooks_ok ) {
			wp_send_json([
				'success' => true,
				'message' => 'Webhooks fixed.',
			]);
			wp_die();
		}

		wp_send_json([
			'success' => false,
			'message' => 'Webhooks were not fixed.',
		]);
		wp_die();
	}

	/**
	 * Adds Two Tap menu to the sidebar
	 *
	 * @return void
	 */
	public function twotap_menu() {
		$count = '1';
		$title = 'Notifications';
		$menu_label = sprintf( __( 'Two Tap %s', 'two-tap' ), "<span class='update-plugins count-{$count}' title='{$title}'><span class='update-count'>" . number_format_i18n( $count ) . "</span></span>" );
		$menu_label = 'Two Tap';

		add_menu_page( 'Two Tap Options', $menu_label, 'manage_options', 'twotap_products_page', null, null, '55.7' );

		add_submenu_page( 'twotap_products_page', 'Products', 'Products', 'manage_options','twotap_products_page', array( $this, 'twotap_products_page' ) );

		if ( ! tt_plugin_enabled() ) {
			return;
		}

		if ( TT_DEBUG ) {
			add_submenu_page( 'twotap_products_page', __( 'Two Tap Carts', 'two-tap' ), __( 'Carts', 'two-tap' ), 'manage_options', 'edit.php?post_type=' . TT_POST_TYPE_CART, null );
			$submenu = add_submenu_page( 'twotap_products_page', __( 'Two Tap Purchases', 'two-tap' ), __( 'Purchases', 'two-tap' ), 'manage_options', 'edit.php?post_type=' . TT_POST_TYPE_PURCHASE, null );
		}
		add_submenu_page( 'twotap_products_page', 'Two Tap Settings', 'Settings', 'manage_options', TT_SETTINGS_PAGE, [ $this, 'twotap_options_page' ] );

		// Setup/welcome.
		if ( ! empty( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) {
				case 'twotap-setup' :
				$plugin_name = $this->plugin_name;
				$version = $this->version;
					include_once( dirname( __FILE__ ) . '/install/class-two-tap-admin-setup-wizard.php' );
				break;
			}
		}

	}

	/**
	 * Highlight main TwoTap menu in sidebar
	 *
	 * @return void
	 */
	public function menu_highlight() {
		global $parent_file, $submenu_file, $post_type;

		switch ( $post_type ) {
			case TT_POST_TYPE_CART :
			case TT_POST_TYPE_PURCHASE :
				$parent_file = 'twotap_products_page';
			break;
		}
	}

	/**
	 * Displays the Two Tap Products page
	 *
	 * @return void
	 */
	public function twotap_products_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wordpress' ) );
		}

		if ( ! tt_plugin_enabled() ) {
			get_twotap_template( 'temp-disabled.php' );
			return;
		}

		if ( ! tt_api_enabled() ) {
			get_twotap_template( 'no-tokens-page.php' );
			return;
		}

		$this->ensure_supported_sites_synced();

		wp_localize_script( 'twotap_js_vue_components', 'ttSupportedSites', get_transient( TT_TRANSIENT_KEY_SUPPORTED_SITES ) );
		do_action( 'two_tap_products_page_loaded' );
		get_twotap_template( 'products-page.php' );
	}

	/**
	 * Displays the Two Tap install wizard
	 *
	 * @return void
	 */
	public function twotap_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		include_once( TT_ABSPATH . '/includes/install/class-tt-admin-setup-wizard.php' );
	}

	/**
	 * Builds the Two Tap Options Page
	 *
	 * @return void
	 */
	public function twotap_options_page() {
		$tab = isset( $_GET['tab'] ) ?  $_GET['tab'] : null;
		?>

		<div class="wrap">

		<?php
		$this->generate_settings_nav();

		switch ( $tab ) {
			case null:
				?>
					<form action="options.php" method="post" class="js-twotap-general-options-form">
						<?php
							settings_fields( 'twotap_general_settings_page' );
							do_settings_sections( 'twotap_general_settings_page' );
							?>
							<p>If you need to run the setup wizard again please follow <a href="<?=site_url( '/wp-admin/admin.php?page=twotap-setup' )?>">this</a> link.</p>
							<?php
							submit_button();
						?>
					</form>
				<?php
				break;
			case 'logistics':
				?>
					<form action="options.php" method="post" class="js-twotap-logistics-options-form">
						<?php
							settings_fields( 'twotap_logistics_settings_page' );
							do_settings_sections( 'twotap_logistics_settings_page' );
							submit_button();
						?>
					</form>
				<?php
				break;
			case 'billing':
				?>
					<form action="options.php" method="post" class="js-twotap-billing-options-form">
						<?php
							settings_fields( 'twotap_billing_settings_page' );
							do_settings_sections( 'twotap_billing_settings_page' );
							submit_button();
						?>
					</form>
				<?php
				break;
			case 'api':
				?>
					<form action="options.php" method="post" class="js-twotap-api-options-form">
						<?php
							settings_fields( 'twotap_api_settings_page' );
							do_settings_sections( 'twotap_api_settings_page' );
							?>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">WooCommerce Webhooks</th>
										<td>
											<check-wc-webhooks></check-wc-webhooks>
										</td>
									</tr>
									<tr>
										<th scope="row">WooCommerce API accessibility</th>
										<td>
											<check-wc-api ></check-wc-api>
										</td>
									</tr>
								</tbody>
							</table>
							<p>If you need to run the setup wizard again please follow <a href="<?=site_url( '/wp-admin/admin.php?page=twotap-setup' )?>">this</a> link.</p>
							<?php
							submit_button();
						?>
					</form>
				<?php
				break;
			case 'debug':
				?>
			<h1 class="wp-heading-inline">Plugin debug</h1>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<!-- main content -->
					<div id="post-body-content">
						<!-- .meta-box-sortables .ui-sortable -->
						<div class="meta-box-sortables ui-sortable">
							<div class="postbox">
								<scheduled-jobs inline-template>
									<div>
										<h2><span><?php esc_attr_e( 'Scheduled jobs' ); ?></span><span class="spinner clear-float" :class="{ 'is-active': isLoading }"></span>
											<small><a href="javascript:void(0)" class="pull-right" @click="resyncProducts()">Re-sync products</a></small></h2>
										<div class="inside js-current-category-contents">
											<table class="tt-status-table widefat">
												<thead>
													<tr>
														<th class="row-title">Name</th>
														<th class="row-title">Count</th>
													</tr>
												</thead>
												<tbody>
													<tr v-for="job in jobs">
														<td v-text="job.title"></td>
														<td v-text="job.count"></td>
													</tr>
												</tbody>
											</table>
											<small class="pull-right" @click="refreshQueuedJobs()">   <a href="javascript:void(0)" title="refresh now">refreshed every 10 seconds</a></small>
											<div class="clear"></div>
										</div>
										<!-- .inside -->

									</div>
								</scheduled-jobs>
							</div>
							<!-- .postbox -->
						</div>
						<!-- .meta-box-sortables .ui-sortable -->
					</div>
				</div>
			</div>
			<?php
				break;
		}
		?>
		</div> <!-- .wrap -->
		<?php
	}

	/**
	 * Generate the Two Tap Options Page tab
	 *
	 * @return void
	 */
	public function generate_settings_nav() {
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?=site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE)?>" class="nav-tab <?=$this->active_tab_class(TT_SETTINGS_PAGE)?>">General</a>
			<a href="<?=site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE)?>&tab=logistics" class="nav-tab <?=$this->active_tab_class(TT_SETTINGS_PAGE, 'logistics')?>">Logistics</a>
			<a href="<?=site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE)?>&tab=billing" class="nav-tab <?=$this->active_tab_class(TT_SETTINGS_PAGE, 'billing')?>">Billing</a>
			<a href="<?=site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE)?>&tab=api" class="nav-tab <?=$this->active_tab_class(TT_SETTINGS_PAGE, 'api')?>">API</a>
			<a href="<?=site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE)?>&tab=debug" class="nav-tab <?=$this->active_tab_class(TT_SETTINGS_PAGE, 'debug')?>">Debug</a>
		</h2>
		<?php
	}

	/**
	 * Highlights active tab
	 *
	 * @param  string $page Current page.
	 * @param  string $tab  Tab.
	 * @return string       Class active or not.
	 */
	public function active_tab_class( $page = null, $tab = '' ) {
		$active = false;
		if ( isset( $_GET['page'] ) ) {
			if ( $_GET['page'] === $page ) {
				if ( isset( $_GET['tab'] ) ) {
					if ( $_GET['tab'] === $tab ) {
						$active = true;
					}
				} else {
					if ( $tab === '' ) {
						$active = true;
					}
				}
			}
		}
		return $active ? 'nav-tab-active' : '';
	}

	/**
	 * Populates the Wordpress notice system with Two Tap Notices
	 *
	 * @return void
	 */
	public function admin_notices() {
		global $twotap_notices;

		/**
		 * Check if WooCommerce is active.
		 */
		if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

			$twotap_notices->add_notice('twotap_wc_api', 'Two Tap Product Catalog <strong>requires WooCommerce to be activated</strong>.', 'error', true);

		} else {

			if (!tt_tokens_set() ) {
				$twotap_notices->add_notice('twotap_tt_tokens_not_set', 'Two Tap tokens not set. Please head to the <a href="'.site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE.'&tab=api').'">settings page</a> and update them.', 'error', true);
			}
			if (!wc_tokens_set() ) {
				$twotap_notices->add_notice('twotap_wc_tokens_not_set', 'WooCommerce keys not set. Please head to the <a href="'.site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE.'&tab=api').'">settings page</a> and update them.', 'error', true);
			}

			if (!get_option(TT_OPTION_WEBHOOKS_OK, false) ) {
				$twotap_notices->add_notice(TT_OPTION_WEBHOOKS_OK, 'WooCommerce webhooks weren\'t created. Please create them <a href="/wp-admin/admin.php?page='.TT_SETTINGS_PAGE.'&tab=api">here</a>.', 'error', true);
			}

			if (!logistics_settings_valid() ) {
				$twotap_notices->add_notice('twotap_logistics_fields', 'Some logistics fields are not set properly. Please update them <a href="/wp-admin/admin.php?page='.TT_SETTINGS_PAGE.'&tab=logistics">here</a>.', 'error', true);
			}

			if (!$this->woocommerce_shipping_enabled() ) {
				$twotap_notices->add_notice('twotap_shipping_disabled', 'You have shipping disabled. Please <a href="/wp-admin/admin.php?page=wc-settings&tab=general">enable</a> <strong>shipping locations</strong> and set at least one shipping method.', 'error', true);
			} else {
				if (!$this->shipping_zones_check() ) {
					$twotap_notices->add_notice('twotap_shipping_zones', 'You don\'t have any shipping methods configured. Please <a href="/wp-admin/admin.php?page=wc-settings&tab=shipping">configure</a> <strong>at least one shipping method</strong>. Otherwise Two Tap shipping will not show up on the users order review.', 'error', true);
				}
			}


			if ( ! wc_api_enabled() ) {
				$twotap_notices->add_notice('twotap_wc_api', 'WooCommerce API is not accesible. Two Tap Product Catalog is not going to work properly. Please head to the <a href="'.site_url('/wp-admin/admin.php?page='.TT_SETTINGS_PAGE.'&tab=api').'">API settings page</a> to make sure that everything is working properly.', 'error', true);
			} else {
				$insufficient_funds_orders = get_insufficient_funds_orders();
				if ( count( $insufficient_funds_orders ) > 0) {
					if ( count( $insufficient_funds_orders ) == 1) {
						$order = $insufficient_funds_orders[0];
						$message = 'There is <a href="' . site_url( 'wp-admin/post.php?post=' . $order->ID . '&action=edit' ) . '">a pending Two Tap order</a> and there are insufficient funds in your account.';
					} else {
						$message = 'There are <a href="' . site_url( 'wp-admin/edit.php?post_type=shop_order&' . TT_TERM_TAXONOMY_ORDER . '=' . TT_TERM_SLUG_ORDER ) . '">a few pending Two Tap orders</a> and insufficient funds in your account.';
					}
					$message .= ' Please <a href="/wp-admin/admin.php?page='.TT_SETTINGS_PAGE.'&tab=billing">check your deposit</a>.';
					$twotap_notices->add_notice( 'twotap_insufficient_funds', $message, 'error', true );
				}
			}
		}

		if ( count( $twotap_notices->get_notices() ) ) {
			echo $twotap_notices->render();
		}
	}

	/**
	 * Checks to see if shipping zones are correctly set
	 *
	 * @return bool
	 */
	public function shipping_zones_check() {
		if ( ! wc_api_enabled() ) {
			return false;
		}

		if ( ! $this->woocommerce_shipping_enabled() ) {
			return false;
		}

		$shipping_methods = [];

		try {
			$shipping_zones = $this->wc_api->get( 'shipping/zones' );
		} catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
			$shipping_zones = [];
			l()->error( 'Couldn\'t retrieve the shipping zones.', $e->getResponse() );
		}

		if(count($shipping_zones) > 0){
			foreach ( $shipping_zones as $zone ) {
				try {
					$shipping_methods[] = $this->wc_api->get( "shipping/zones/{$zone['id']}/methods" );
				} catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
					l()->error( 'Couldn\'t retrieve the shipping methods for ' . $zone['id'] . ' zone.', $e->getResponse() );
				}
			}
		}


		// must filter empty values.
		$shipping_methods = array_filter( $shipping_methods );

		return count( $shipping_methods ) > 0;
	}

	/**
	 * Check if WooCommerce has shipping enabled.
	 *
	 * @return boolean
	 */
	public function woocommerce_shipping_enabled()
	{
		if ( ! wc_api_enabled() ) {
			return false;
		}

		$enabled = false;
		try {
			$general_settings = $this->wc_api->get( 'settings/general' );
		} catch ( Automattic\WooCommerce\HttpClient\HttpClientException $e ) {
			$general_settings = [];
			l()->error( 'Bad WC api response in test.', $e->getResponse() );
		}

		if(count($general_settings) > 0){
			foreach($general_settings as $value){
			    if($value['id'] == 'woocommerce_ship_to_countries'){
			    	$enabled = $value['value'] != 'disabled';
			    	break;
			    }
			};
		}


		return $enabled;
	}

	/**
	 * Translates input fields to Two Tap format
	 *
	 * @param  array $data The data.
	 * @return array
	 */
	public function translate_input_fields( $data ) {
		$fields_input = [];

		$fields_input['email'] = $data['billing_email'];
		$fields_input['billing_first_name'] = $data['billing_first_name'];
		$fields_input['billing_last_name'] = $data['billing_last_name'];
		$fields_input['billing_address'] = $data['billing_address_1'] . ', ' . $data['billing_address_2'];
		$fields_input['billing_city'] = $data['billing_city'];
		$fields_input['billing_country'] = convert_country_code( $data['billing_country'] );
		$fields_input['billing_state'] = convert_state_code( $data['billing_state'], $fields_input['billing_country'] );
		$fields_input['billing_zip'] = $data['billing_postcode'];
		$fields_input['billing_telephone'] = $data['billing_phone'];

		if ( isset( $data['ship_to_different_address'] ) && $data['ship_to_different_address'] ) {
			// different shipping address.
			$fields_input['shipping_first_name'] = $data['shipping_first_name'];
			$fields_input['shipping_last_name'] = $data['shipping_last_name'];
			$fields_input['shipping_address'] = $data['shipping_address_1'] . ', ' . $data['shipping_address_2'];
			$fields_input['shipping_city'] = $data['shipping_city'];
			$fields_input['shipping_country'] = convert_country_code( $data['shipping_country'] );
			$fields_input['shipping_state'] = convert_state_code( $data['shipping_state'], $fields_input['shipping_country'] );
			$fields_input['shipping_zip'] = $data['shipping_postcode'];
			$fields_input['shipping_telephone'] = $data['billing_phone'];
		} else {
			$fields_input['shipping_first_name'] = $fields_input['billing_first_name'];
			$fields_input['shipping_last_name'] = $fields_input['billing_last_name'];
			$fields_input['shipping_address'] = $fields_input['billing_address'];
			$fields_input['shipping_city'] = $fields_input['billing_city'];
			$fields_input['shipping_country'] = convert_country_code( $data['billing_country'] );
			$fields_input['shipping_state'] = convert_state_code( $fields_input['billing_state'], $fields_input['shipping_country'] );
			$fields_input['shipping_zip'] = $fields_input['billing_zip'];
			$fields_input['shipping_telephone'] = $fields_input['billing_telephone'];
		}

		return apply_filters('twotap_logistics_info_to_fields_input', $fields_input);
	}

	/**
	 * Translates input fields to Two Tap format
	 *
	 * @param  array $data The data.
	 * @return array
	 */
	public function customer_session_to_twotap_input_fields( $data ) {
		$fields_input = [];

		$fields_input['email'] = $data['email'];
		$fields_input['billing_first_name'] = $data['first_name'];
		$fields_input['billing_last_name'] = $data['last_name'];
		$fields_input['billing_address'] = $data['address_1'] . ', ' . $data['address_2'];
		$fields_input['billing_city'] = $data['city'];
		$fields_input['billing_country'] = convert_country_code( $data['country'] );
		$fields_input['billing_state'] = convert_state_code( $data['state'], $fields_input['billing_country'] );
		$fields_input['billing_zip'] = $data['postcode'];
		$fields_input['billing_telephone'] = $data['phone'];

		$fields_input['shipping_first_name'] = $data['shipping_first_name'];
		$fields_input['shipping_last_name'] = $data['shipping_last_name'];
		$fields_input['shipping_address'] = $data['shipping_address_1'] . ', ' . $data['shipping_address_2'];
		$fields_input['shipping_city'] = $data['shipping_city'];
		$fields_input['shipping_country'] = convert_country_code( $data['shipping_country'] );
		$fields_input['shipping_state'] = convert_state_code( $data['shipping_state'], $data['shipping_country'] );
		$fields_input['shipping_zip'] = $data['shipping_postcode'];
		$fields_input['shipping_telephone'] = $data['phone'];

		return $fields_input;
	}

	/**
	 * Checks the imput fields with the Two Tap API from the WooCommerce checkout page
	 *
	 * @return void
	 */
	public function check_input_fields_with_twotap()
	{

		if ( !tt_plugin_enabled() ){
				return;
		}

		$cart = WC()->cart;
		// if the cart contains Two Tap products we need to check the billing details.
		$twotap_products = get_twotap_products_in_cart( $cart );

    l('Checking input fileds with Two Tap.');
		if ( count( $twotap_products ) > 0 ) {
			if ( ! tt_tokens_set() ) {
        l()->error('Two Tap tokens not set.');
				return;
			}

			$db_cart = get_twotap_cart_for_cart();
			if(!$db_cart){
	            l()->error('Couldn\'t find a Two Tap cart for WC cart.');
	            return;
			}
			$twotap_cart_id = get_post_meta($db_cart->ID, 'twotap_cart_id', true);
			$fields_input = apply_filters( 'twotap_translate_input_fields', $_POST );

			$validation_response = $this->api->utils()->fieldsInputValidate( $twotap_cart_id, $fields_input );
			l('Two Tap fields input validate check.', [$fields_input, $validation_response]);
			if ( $validation_response['message'] ){
				switch ( $validation_response['message'] ) {
					case 'done':
						return;
						break;
					default:
					case 'bad_required_fields':
						$notices = array_filter( explode( '.', $validation_response['description'] ) );
						foreach ( $notices as $notice ) {
							wc_add_notice( $notice . '.' , 'error' );
						}
						break;
				}
			}
		}
	}

	/**
	 * Adds the Two Tap billing mandatory fields to WooCommerce checkout form
	 *
	 * @param array $fields The fields.
	 */
	public function add_mandatory_billing_fields( $fields ) {
		$twotap_required_fields = [
			'billing_first_name',
			'billing_last_name',
			'billing_country',
			'billing_address_1',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_phone',
			'billing_email',
		];
		foreach ( $fields as $field_name => $field_value ) {
			if ( in_array( $field_name, $twotap_required_fields ) ) {
				$fields[ $field_name ]['required'] = true;
			}
		}

		return $fields;
	}

	/**
	 * Adds the Two Tap shipping mandatory fields to WooCommerce checkout form
	 *
	 * @param array $fields The existing fields.
	 */
	public function add_mandatory_shipping_fields( $fields ) {

		$twotap_required_fields = [
			'shipping_first_name',
			'shipping_last_name',
			'shipping_country',
			'shipping_address_1',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'shipping_phone',
			'shipping_email',
		];
		foreach ( $fields as $field_name => $field_value ) {
			if ( in_array( $field_name, $twotap_required_fields ) ) {
				$fields[ $field_name ]['required'] = true;
			}
		}

		return $fields;
	}

	/**
	 * Adds plugin-two-tap class to the body
	 */
	public function add_body_class() {
		return 'plugin-two-tap';
	}

}
