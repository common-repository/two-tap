<?php

/**
 * Main TwoTap Class.
 *
 * @class TwoTap
 * @version 1.0.0
 */
final class Two_Tap_Shipping_Estimates {

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

        global $tt_api;
        $this->api = $tt_api;

        global $wc_api;
        $this->wc_api = $wc_api;

        do_action( 'two_tap_shipping_estimates_loaded' );
    }

    public function woocommerce_thankyou($order_id)
    {
        l('Storing cart meta to order');


        $meta_updated = get_post_meta($order_id, 'twotap_meta_updated', true);

        if (!$meta_updated) {
            // marking order as Two Tap order
            $this->add_to_twotap_category($order_id);

            // storing cart meta to order
            $meta_keys = ['twotap_chosen_shipping_option', TT_META_TWOTAP_ESTIMATE, TT_META_TWOTAP_AVAILABLE_ESTIMATES, 'twotap_shipping_info'];

            foreach ($meta_keys as $meta_key) {
                $meta_value = WC()->session->get($meta_key);
                if ($meta_value) {
                    $existing_meta = get_post_meta($order_id, $meta_key, true);
                    if (!$existing_meta) {
                        update_post_meta($order_id, $meta_key, $meta_value);
                    }
                }
            }
            update_post_meta($order_id, 'twotap_meta_updated', true);
        }
    }

    public function wc_package_rates($rates, $package)
    {
        WC()->session->set('twotap_estimate', null);
        $this->clear_wc_shipping_rates_cache();
        $cart = WC()->cart;
        $session = WC()->session;

        $tt_products = get_twotap_products_in_cart($cart);
        $regular_products = get_regular_products_in_cart($cart);

        // if the cart has only regular products skip this step
        if (count($tt_products) == 0) {
            return $rates;
        }
        if (isset($package)) {
            $shipping_info = $package['destination'];
        }

        $db_cart = get_twotap_cart_for_cart();
        if (!$db_cart) {
            l()->error('Couldn\'t find a Two Tap cart for WC cart.');
            return;
        }
        $db_cart_id = $db_cart->ID;
        $twotap_cart_id = get_post_meta($db_cart_id, TT_META_TWOTAP_CART_ID, true);

        if (isset($twotap_cart_status['message'])) {
            if ( $twotap_cart_status['message'] == TT_STATUS_STILL_PROCESSING ) {
                ensure_cart_status_is_fresh($twotap_cart_id);
                return $rates;
            }
        }

        $twotap_cart_status = get_post_meta($db_cart_id, TT_META_TWOTAP_LAST_STATUS, true);

        l("Calculating package rates for Two Tap cart ID {$twotap_cart_id}.");

        $shipping_info = $this->get_shipping_info();

        WC()->session->set('twotap_shipping_info', $shipping_info);
        // WC()->session->set('twotap_shipping_info', $shipping_info);

        $shipping_options = get_shipping_options_from_cart_status($twotap_cart_status);
        $fields_input = [];
        foreach ($shipping_options as $option) {
            $fields_input[$option] = generate_fields_input($cart, $shipping_info, $option);
        }
        // l('Generated fields input based on all of the possible shipping options.', [$shipping_options, $fields_input]);

        $estimates = [];
        $estimates_done = false;
        foreach ($fields_input as $input_name => $input) {
            $estimate = $this->check_cart_estimates($input);
            if ($estimate['message'] == 'done') {
                $estimates[$input_name] = $estimate;
                $estimates_done = true;
            }
        }

        if (!$estimates_done) {
            l('Two Tap estimates not finished yet.');
            return $rates;
        }

        WC()->session->set(TT_META_TWOTAP_AVAILABLE_ESTIMATES, $estimates);
        WC()->session->set('regular_products', $regular_products);
        WC()->session->set('tt_products', $tt_products);

        $country = 'US';
        if (isset($session)) {
            $customer_info = $session->get('customer');
            if (isset($customer_info['country'])) {
                $country = $customer_info['country'];
            }
        }

        $first_estimate = null;
        if (count($regular_products) == 0) {
            // only two tap shipping
            $rates = [];
            $twotap_fulfilment_markup = get_option('twotap_fulfilment_markup', true);

            foreach ($shipping_options as $option) {
                $cost = sanitize_price($estimates[$option]['estimated_total_prices']['shipping_price']);
                if ( $country != 'US' && $twotap_fulfilment_markup ) {
                    $cost = $cost + $twotap_fulfilment_markup;
                }
                $name = ucfirst($option);
                if ($cost == 0) {
                    $name .= ' - ' . __( 'Free shipping' );
                }
                $key = "twotap_{$option}";


                $rates[$key] = new WC_Shipping_Rate($key, $name, $cost);
                if ($key == WC()->session->get('chosen_shipping_methods')[0]) {
                    $first_estimate = $estimates[$option];
                    $twotap_chosen_shipping_option = $option;
                }
            }

            if (!$first_estimate) {
                $estimate_options = array_keys($estimates);
                $twotap_chosen_shipping_option = $estimate_options[0];
                $first_estimate = $estimates[$twotap_chosen_shipping_option];
            }

        } else {
            // mixed products
            // getting the first tt shipping option
            if (isset($estimates[TT_PURCHASE_DEFAULT_SHIPPING_OPTION])) {
                // trying to get 'cheapest'
                $twotap_chosen_shipping_option = TT_PURCHASE_DEFAULT_SHIPPING_OPTION;
                $first_estimate = $estimates[TT_PURCHASE_DEFAULT_SHIPPING_OPTION];
            } else {
                $estimate_options = array_keys($estimates);
                $twotap_chosen_shipping_option = $estimate_options[0];
                $first_estimate = $estimates[$twotap_chosen_shipping_option];
            }

            if (!$first_estimate) {
                return $rates;
            }
            $twotap_shipping_price = sanitize_price($first_estimate['estimated_total_prices']['shipping_price']);
            foreach ($rates as $key => $rate) {
                $cost = $rate->cost;
                $twotap_fulfilment_markup = get_option('twotap_fulfilment_markup', true);

                // if the user set a fulfilment charge
                if ( $country != 'US' && $twotap_fulfilment_markup ) {
                    $cost = (string)(floatval($cost) + $twotap_fulfilment_markup);
                }

                $rate->cost = (string)(floatval($cost) + $twotap_shipping_price);
            }
        }

        WC()->session->set('twotap_chosen_shipping_option', $twotap_chosen_shipping_option);

        // setting the selected Two Tap Estimate
        if ($first_estimate) {
            WC()->session->set('twotap_estimate', $first_estimate);
        } else {
            // we're returning null and waiting for the cart to finish and the estimates to arrive
            WC()->session->set('twotap_estimate', null);
            return [];
        }
        return $rates;
    }

    public function after_shipping_estimates($context = 'cart')
    {
        $twotap_estimate = WC()->session->get('twotap_estimate');
        $status = TT_STATUS_STILL_PROCESSING;
        if (is_null($twotap_estimate)) {
            $status = TT_STATUS_STILL_PROCESSING;
        } else {
            if (!isset($twotap_estimate['message'])) {
                $status = TT_STATUS_STILL_PROCESSING;
            } else {
                if ($twotap_estimate['message'] == TT_STATUS_DONE) {
                    $status = TT_STATUS_DONE;
                }
            }
        }

        wp_localize_script( 'two-tap-estimates', 'Two_Tap_Estimates_Vars', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'context' => $context,
            'status' => $status,
        ]);
    }

    private function get_shipping_info() {
        $session = WC()->session;

        $country = 'US';
        if (isset($session)) {
            $customer_info = $session->get('customer');
            if (isset($customer_info['country'])) {
                $country = $customer_info['country'];
            }
        }

        $shipping_info = [];
        if ($country == 'US'){
            // we give the customer's info
            $shipping_info = apply_filters('twotap_customer_session_to_twotap_input_fields', $customer_info);
        }else{
            // we give the store's info
            $logistics_type = get_option( 'twotap_logistics_type' );
            switch ($logistics_type) {
                case 'own_logistics':
                $logistics_settings = get_option( 'twotap_logistics_settings' );
                $shipping_info = $logistics_settings;
                break;
            }
        }

        // l('returned shipping_info', [$shipping_info, $country]);
        return $shipping_info;
    }

    public function woocommerce_cart_totals_before_order_total()
    {
        $this->after_shipping_estimates('cart');
    }

    public function woocommerce_review_order_before_order_total()
    {

        $this->after_shipping_estimates('checkout');
    }

    public function ajax_check_estimates()
    {
        $cart = WC()->cart;
        $tt_products = get_twotap_products_in_cart($cart);
        l('Ajax check estimates with ' . count($tt_products) . ' Two Tap products in cart.');

        // if the cart has only regular products skip this step
        if (count($tt_products) == 0) {
            $response = [
                'status' => 'done'
            ];
        } else {
            $response = $this->check_cart_estimates();
        }

        wp_send_json($response);
        wp_die();
    }

    public function check_cart_estimates($fields_input = null)
    {
        $response = [];
        $cart = WC()->cart;
        $db_cart = get_twotap_cart_for_cart();

        if (!$db_cart) {
            l()->error('Couldn\'t find a Two Tap cart for WC cart.');
            return;
        }

        $twotap_cart_id = get_post_meta($db_cart->ID, TT_META_TWOTAP_CART_ID, true);
        $fields_input = [];

        $shipping_info = $this->get_shipping_info();
        if (is_null($fields_input)) {
            $fields_input = generate_fields_input($cart, $shipping_info);
        }

        $twotap_cart_status = $this->api->cart()->status($twotap_cart_id, null, null, TT_DESTINATION_COUNTRY);

        if (isset($twotap_cart_status['message']) && $twotap_cart_status['message']  == TT_STATUS_STILL_PROCESSING){
            $response['message'] = TT_STATUS_STILL_PROCESSING;
        } else {
            $estimates = $this->api->cart()->estimates($twotap_cart_id, $fields_input, null, TT_DESTINATION_COUNTRY);
            // l('Estimates response.', [$estimates, $fields_input]);

            if ($estimates['message'] == 'failed') {
                if (strpos($estimates['description'], 'has not finished yet') !==  false){
                    $response['message'] = TT_STATUS_STILL_PROCESSING;
                } else {
                    $response['message'] = TT_STATUS_FAILED;
                }
            } else {
                foreach ($estimates as $key => $value) {
                    $response[$key] = $value;
                }
                $response['message'] = TT_STATUS_DONE;
            }

            if (isset($estimates['description'])) {
                $response['description'] = $estimates['description'];
            }
        }
        return $response;
    }

    public function woocommerce_cart_calculate_fees($instance)
    {
        $twotap_estimate = WC()->session->get('twotap_estimate');
        $taxes = $instance->get_cart_contents_taxes();

        if (!is_null($twotap_estimate)) {
            $twotap_sales_tax = sanitize_price($twotap_estimate['estimated_total_prices']['sales_tax']);
            if (empty($instance->get_cart_contents_taxes()) && $twotap_sales_tax != 0) {
                $instance->add_fee( __('Sales tax', 'woocommerce'), $twotap_sales_tax );
            } else {
                foreach ($instance->taxes as $key => $tax) {
                    $cost = floatval($tax);
                    $instance->taxes[$key] = ($cost + $twotap_sales_tax);
                }
            }
        }

        return $instance;
    }

    public function woocommerce_shipping_packages($packages)
    {
        // clearing the cache to force update the Two Tap estimates
        $this->clear_wc_shipping_rates_cache();
        return $packages;
    }

    public function enqueue_scripts()
    {
        if ( function_exists( 'is_woocommerce' ) ) {
            if ( is_cart() || is_checkout() ) {
                $tt_products = get_twotap_products_in_cart(WC()->cart);
                if (count($tt_products) > 0) {
                    wp_enqueue_script( 'two-tap-estimates', TT_PLUGIN_URL . '/public/js/two-tap-estimates.js', ['jquery-core', 'underscore'], false, true );
                }
            }
        }
    }

    public function clear_wc_shipping_rates_cache()
    {
        $packages = WC()->cart->get_shipping_packages();

        foreach ($packages as $key => $value) {
            $shipping_session = "shipping_for_package_$key";

            unset(WC()->session->$shipping_session);
        }
    }

    public function add_to_twotap_category($post_id)
    {
        // adding order to Two Tap category
        $twotap_order_term = get_term_by('slug', TT_TERM_SLUG_ORDER, TT_TERM_TAXONOMY_ORDER);
        if ($twotap_order_term) {
            wp_set_post_terms( $post_id, [$twotap_order_term->term_id], TT_TERM_TAXONOMY_ORDER, false );
        }
    }
}