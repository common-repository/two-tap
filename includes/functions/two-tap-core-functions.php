<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Generate the Two Tap needed fields input
 *
 * @param  [type] $cart_or_order   [description]
 * @param  [type] $shipping_info   [description]
 * @param  [type] $shipping_option [description]
 *
 * @todo   break into two separate functions (cart/order)
 *
 * @return [type]                  [description]
 */
function generate_fields_input($cart_or_order = null, $shipping_info = null, $shipping_option = TT_OPTION_DEFAULT_SHIPPING_OPTION)
{
	l('Started generating fields input.');

	if (is_null($cart_or_order)) {
		l()->error("Cart or order is null.");
		return;
	}

	$is_order = $cart_or_order instanceof WC_Order;

	if ($is_order) {
		$order = $cart_or_order;
		$order_id = $order->get_id();
		$db_cart = get_twotap_cart_by_order_id($order->get_id());
		$twotap_cart_id = get_post_meta($db_cart->ID, 'twotap_cart_id', true);
		$customer_data = $order->get_data();
	} else {
		$cart = $cart_or_order;
		$customer_data = WC()->customer->get_data();
		$db_cart = get_twotap_cart_for_cart();
		if (!$db_cart) {
			l()->error('Couldn\'t find a Two Tap cart for WC cart.');
			return;
		}
		$twotap_cart_id = get_post_meta($db_cart->ID, 'twotap_cart_id', true);
	}

	// overwrite shipping_info if we get it
	if (!is_null($shipping_info)) {
		$customer_info = $shipping_info;
	} else {
		$customer_info = order_customer_info_to_twotap($customer_data);
	}

	$cart_status = get_post_meta($db_cart->ID, TT_META_TWOTAP_LAST_STATUS, true);
	$cart_products = get_post_meta($db_cart->ID, 'twotap_cart_products', true);

	if (!$twotap_cart_id) {
		l()->error("Couldn't find Two Tap cart in generate_fields_input.", $twotap_cart_id);
		return null;
	}

	l("Two Tap cart_id: {$twotap_cart_id}");

	$fields_input = [];
	$products_info = [];

	if ($cart_status['message'] == 'done') {
		foreach ($cart_status['sites'] as $site_id => $site_response) {
			$fields_input[$site_id] = [];
			$fields_input[$site_id]['shipping_option'] = $shipping_option;
			if (in_array('noauthCheckout', $site_response['checkout_support'])) {
				$fields_input[$site_id]['noauthCheckout'] = $customer_info;
				$fields_input[$site_id]['addToCart'] = [];

				foreach ($site_response['add_to_cart'] as $product_md5 => $product) {
					$possible_product_md5 = md5($product['original_url']);
					$cart_product = $cart_products[$possible_product_md5];
					$fields_input[$site_id]['addToCart'][$possible_product_md5] = $cart_products[$possible_product_md5]['chosen_attributes'];
				}
			} else {
				l()->error('Store doesn\'t support noauthCheckout.', $site_id);
				return;
			}
		}
	}

	// l('Generated fields input.', $fields_input);

	return $fields_input;
}


/**
 * Check if the stored logistics settings are valid
 *
 * @return boolean
 */
function logistics_settings_valid()
{
	$international_logistics_enabled = get_option('twotap_international_logistics_enabled');
	$logistics_type = get_option('twotap_logistics_type');
	$logistics_settings = get_option('twotap_logistics_settings');
	$check_ok = true;

	if ($international_logistics_enabled != 'yes') {
		return true;
	}

	if ($logistics_type === false) {
		return false;
	}

	if ($logistics_type == 'own_logistics') {
		// we should have valid shipping info
		$fields = [ 'email', 'shipping_telephone', 'shipping_first_name', 'shipping_last_name', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country' ];
		foreach ($fields as $field) {
			if (!isset($logistics_settings[$field]) || $logistics_settings[$field] == '') {
				$check_ok = false;
			}
		}
		return $check_ok;
	}

	return $check_ok;
}

/**
 * Turn the logistics info to fields input
 *
 * @param  [type] $shipping_info [description]
 * @param  [type] $billing_info  [description]
 *
 * @return [type]                [description]
 */
function logistics_info_to_fields_input($shipping_info = null, $billing_info = null)
{
	$fields_input = [];
	$fields_input['email'] = $shipping_info['email'];
	$fields = ["first_name", "last_name", "address", "city", "state", "country", "zip", "telephone"];

	if ( !is_null( $shipping_info ) ) {
		foreach ($fields as $field) {
			$fields_input["shipping_{$field}"] = $shipping_info["shipping_{$field}"];
		}
	}
	if ( !is_null( $billing_info ) ) {
		foreach ($fields as $field) {
			$fields_input["billing_{$field}"] = $billing_info[$field];
		}
	}

	return apply_filters( 'twotap_logistics_info_to_fields_input', $fields_input );
}

/**
 * Transform WooCommerce customer data to Two Tap fields input data
 *
 * @param  array $data WC customer data.
 *
 * @return array       Two Tap data
 */
function order_customer_info_to_twotap($data)
{
	l()->warning('order_customer_info_to_twotap', $data);
	$response = [];
	$groups = ['billing', 'shipping'];
	$country = "United States of America";

	foreach ($groups as $group) {
		if (isset($data[$group]['country'])) {
			$country = convert_country_code( $data[$group]['country'] );
		}
		foreach ($data[$group] as $key => $value) {
			switch ($key) {
				case 'country':
					$country = convert_country_code( $value );
					$response["{$group}_{$key}"] = $country;
					break;
				case 'state':
					$response["{$group}_{$key}"] = convert_state_code( $value, $country );
					break;
				default:
					$response["{$group}_{$key}"] = $value;
					break;
			}
		}
	}
	$response['ship_to_different_address'] = isset($data['ship_to_different_address']) ? $data['ship_to_different_address'] : false;

	return apply_filters('twotap_translate_input_fields', $response);
}

/**
 * Converts the Two Tap common statusea to Wordpress post_status
 *
 * @param  [type] $message [description]
 *
 * @return [type]          [description]
 */
function twotap_status_to_wp($message){
	if ($message == 'bad_required_fields') {
		// this is needed because wordpress doesn't allow statuses longer than 20 characters
		$message = 'bad_fields';
	}
	return "tt-{$message}";
}

/**
 * Fetches the ID of meta by key & value
 *
 * @param  [type] $meta_key   [description]
 * @param  [type] $meta_value [description]
 *
 * @return [type]             [description]
 */
function twotap_get_meta_id($meta_key, $meta_value)
{
	global $wpdb;
	return $wpdb->get_var( "SELECT post_id from $wpdb->postmeta where meta_value = '{$meta_value}' and meta_key = '{$meta_key}'");
}

/**
 * Transforms a Two Tap status to a readable format
 *
 * @param  string $status [description]
 *
 * @return [type]         [description]
 */
function twotap_pretty_status($status = ''){
	return ucfirst(str_replace('_', ' ', str_replace('tt-', '', $status)));
}

/**
 * Array with all the possible DB cart stauses
 *
 * @return [type] [description]
 */
function twotap_db_cart_statuses(){
	return ['draft', 'tt-still_processing', 'tt-done', 'tt-has_failures', 'tt-no_products'];
}

/**
 * Array with all the possible DB purchase stauses
 *
 * @return [type] [description]
 */
function twotap_db_purchase_statuses(){
	return ['draft', 'tt-still_processing', 'tt-done', 'tt-has_failures', 'tt-bad_required_fields'];
}

/**
 * Array with all the possible product stauses
 *
 * @return [type] [description]
 */
function twotap_product_statuses(){
	return ['draft', 'publish', 'tt-processing', 'tt-unprocessed'];
}

if (! function_exists('dd')) {
	/**
	 * Dump the passed variables and end the script.
	 *
	 * @param  mixed
	 * @return void
	 */
	function dd(){
		$args = func_get_args();
		foreach ($args as $x) {
			dump($x);
			}
			die(1);
	}
}

if (! function_exists('d')) {
	function d(){
		$args = func_get_args();
		foreach ($args as $x) {
			dump($x);
		 }
	}
}

if (! function_exists('env')) {
	/**
	 * Gets the value of an environment variable.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function env($key, $default = null)
	{
		$value = getenv($key);

		if ($value === false) {
			return $default;
		}

		switch (strtolower($value)) {
			case 'true':
			case '(true)':
				return true;
			case 'false':
			case '(false)':
				return false;
			case 'empty':
			case '(empty)':
				return '';
			case 'null':
			case '(null)':
				return;
		}

		return $value;
	}
}

/**
 * Logger short method
 *
 * @param  [type] $message [description]
 * @param  array  $args    [description]
 * @param  string $method  [description]
 *
 * @return [type]          [description]
 */
function l( $message = null, $args = [] ) {
	$logger = new Two_Tap_Logger();
	if (!is_null($message)) {
		if (empty($args)) {
			if (gettype($message) == 'string') {
				$logger->info($message);
				return;
			}
			$logger->info(gettype($message), $message);
		} else {
			$logger->info($message, $args);
		}
		return;
	}
	return $logger;
}

/**
* Define constant if not already set.
*
* @param  string $name
* @param  string|bool $value
*/
function tt_define( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}


/**
 * Get the Two Tap templates
 */
function get_twotap_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args );
	}

	$located = twotap_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
		return;
	}

	  // Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'twotap_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'twotap_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'twotap_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Locate the Two Tap template file
 *
 * @param  [type] $template_name [description]
 * @param  string $template_path [description]
 * @param  string $default_path  [description]
 *
 * @return [type]                [description]
 */
function twotap_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = TT_ABSPATH;
	}

	if ( ! $default_path ) {
		$default_path = TT_ABSPATH . '/admin/partials/';
	}

	  // Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	  // Get default template/
	if ( ! $template || TT_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	  // Return what we found.
	return apply_filters( 'twotap_locate_template', $template, $template_name, $template_path );
}

/*
Sanitizes the price received from the Two Tap API
 */
function sanitize_price($value)
{
	return floatval(str_replace(['$', ' RON', ' SGD', ' JPY'], '', $value));
}

/**
 * Checks wether the Two Tap Plugin is enabled
 *
 * @return [type] [description]
 */
function tt_plugin_enabled(){
    return false;
}
