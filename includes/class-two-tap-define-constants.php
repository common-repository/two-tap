<?php
/**
 * @package TTDefineConstants
 * @version 1.0
 */
/**
 * Main TTDefineConstants Class.
 *
 * @class TTDefineConstants
 * @version 1.0.0
 */
class Two_Tap_Define_Constants {

    /**
    * Two_Tap_Define_Constants Constructor.
    */
    public function __construct()
    {
        $this->register_dotenv();
        $this->define_constants();

        do_action( 'two_tap_define_constants_loaded' );
    }


    public function register_dotenv()
    {
        if(file_exists(ABSPATH . '.env')){
            $dotenv = new Dotenv\Dotenv(ABSPATH);
            $dotenv->load();
        }
    }

    public function define_constants()
    {
        do_action( 'two_tap_before_define_constants' );

        $logistics_settings = get_option('twotap_logistics_settings');

        $this->define( 'TT_DEBUG', $this->env('TT_DEBUG', false) );
        $this->define( 'TT_URL_SITE', $this->env('TT_URL_SITE', site_url()) );
        $this->define( 'TT_URL_API', $this->env('TT_URL_API', 'https://api.twotap.com/v1.0') );

        // URLs
        $this->define( 'TT_URL_API_APP_STATUS', $this->env('TT_URL_API_APP_STATUS', TT_URL_API . '/app/status') );
        $this->define( 'TT_URL_CORE', $this->env('TT_URL_CORE', 'https://core.twotap.com') );

        // set api variables
        $api_variables = ['twotap_public_token', 'twotap_private_token', 'wc_url', 'wc_consumer_key', 'wc_consumer_secret'];

        foreach ($api_variables as $variable) {
            $upper_variable = strtoupper($variable);
            $value = $this->env($upper_variable, false);
            if(!$value){
                $value = get_option($variable);
            }
            $this->define( $upper_variable, $value );
        }

        // plugin settings
        $this->define( 'TT_PRODUCT_IMPORT_LIMIT', 10 ); // size of requested items in /product/scroll
        $this->define( 'TT_PRODUCT_STOCK_STATUS', true );
        $this->define( 'TT_PRODUCT_FAILED_DECISION', 'outofstock' );
        $this->define( 'TT_REFRESH_PRODUCT_INFO_JOB', 'refresh_product_info' );
        $this->define( 'TT_ADD_PRODUCT_TO_DB_DELAYED_JOB', 'add_product_to_db_delayed' );
        $this->define( 'TT_GET_SCROLL_PRODUCTS_JOB', 'get_scroll_products' );
        $this->define( 'TT_AUTO_SEND_PURCHASE', get_option('twotap_auto_send_purchase', true) );
        $this->define( 'TT_PURCHASE_DEFAULT_SHIPPING_OPTION', 'cheapest' );
        $this->define( 'TT_SETTINGS_PAGE', 'twotap_settings_page' );
        $this->define( 'TT_PRODUCTS_PAGE', 'twotap_products_page' );

        $destination_country = isset($logistics_settings['shipping_country']) ? $logistics_settings['shipping_country'] : 'United States of America';
        $this->define( 'TT_DESTINATION_COUNTRY', $destination_country );

        // Post types
        $this->define( 'TT_POST_TYPE_CART', 'tt_cart' );
        $this->define( 'TT_POST_TYPE_PURCHASE', 'tt_purchase' );
        $this->define( 'TT_POST_TYPE_PRODUCT', 'product' );
        $this->define( 'TT_POST_TYPE_ORDER', 'shop_order' );
        $this->define( 'TT_POST_TYPE_PRODUCT_VARIATION', 'product_variation' );

        // Post types terms info
        $this->define( 'TT_TERM_TAXONOMY_PRODUCT', 'twotap_products' );
        $this->define( 'TT_TERM_SLUG_PRODUCT', 'twotap-product' );
        $this->define( 'TT_TERM_TITLE_PRODUCT', 'Two Tap Products' );
        $this->define( 'TT_TERM_TAXONOMY_ORDER', 'twotap_orders' );
        $this->define( 'TT_TERM_SLUG_ORDER', 'twotap-order' );
        $this->define( 'TT_TERM_TITLE_ORDER', 'Two Tap Orders' );

        // Meta fields
        $this->define( 'TT_META_TWOTAP_STATUS', 'twotap_status' ); // used for order items
        $this->define( 'TT_META_TWOTAP_SITE_ID', 'twotap_site_id' );
        $this->define( 'TT_META_TWOTAP_PRODUCT_MD5', 'twotap_product_md5' );
        $this->define( 'TT_META_TWOTAP_ORIGINAL_URL', 'twotap_original_url' );
        $this->define( 'TT_META_TWOTAP_CART_ID', 'twotap_cart_id' );
        $this->define( 'TT_META_TWOTAP_PURCHASE_ID', 'twotap_purchase_id' );
        $this->define( 'TT_META_TWOTAP_LAST_RESPONSE', 'twotap_last_response' );
        $this->define( 'TT_META_TWOTAP_LAST_STATUS', 'twotap_last_status' );
        $this->define( 'TT_META_TWOTAP_ESTIMATE', 'twotap_estimate' );
        $this->define( 'TT_META_TWOTAP_AVAILABLE_ESTIMATES', 'twotap_available_estimates' );
        $this->define( 'TT_META_TWOTAP_INSUFFICIENT_FUNDS', 'twotap_insufficient_funds' );
        $this->define( 'TT_META_TWOTAP_LOGISTICS_FAILED', 'twotap_logistics_failed' );
        $this->define( 'TT_META_TWOTAP_DISCOUNTS_APPLIED', 'twotap_discounts_applied' );
        $this->define( 'TT_META_TWOTAP_CART_PRODUCTS', 'twotap_cart_products' );
        $this->define( 'TT_META_TWOTAP_REQUEST_PARAMS', 'twotap_request_params' );
        $this->define( 'TT_META_TWOTAP_VARIATION_VALUES', 'twotap_variation_values' );
        $this->define( 'TT_META_TWOTAP_SENT_STATE', 'twotap_sent_state' ); // used for order state (pending|sent)
        $this->define( 'TT_META_ORDER_ID', 'order_id' );
        $this->define( 'TT_META_PRODUCT_INFO_NOT_UPDATED', 'twotap_product_info_not_updated' );
        $this->define( 'TT_META_VARIATIONS_NOT_UPDATED', 'twotap_variations_not_updated' );
        $this->define( 'TT_META_VARIATION_IMAGE_META_NOT_UPDATED', 'twotap_variation_image_meta_not_updated' );
        $this->define( 'TT_META_DEFAULT_VARIATION_NOT_UPDATED', 'twotap_default_variation_not_updated' );
        $this->define( 'TT_META_TWOTAP_LAST_SYNCED', 'twotap_last_synced' );

        // Options
        $this->define( 'TT_OPTION_WEBHOOKS_OK', 'twotap_wc_webhooks_ok' );
        $this->define( 'TT_OPTION_WC_API_ENABLED', 'twotap_wc_api_enabled' );
        $this->define( 'TT_OPTION_DEFAULT_SHIPPING_OPTION', 'cheapest');
        $this->define( 'TT_OPTION_PURCHASE_TEST_MODE', $this->env('TT_OPTION_PURCHASE_TEST_MODE', null));
        $this->define( 'TT_OPTION_REFRESH_IMAGES', get_option('twotap_refresh_images', true) );
        $this->define( 'TT_OPTION_DELETE_CUSTOM_IMAGES', false );
        $this->define( 'TT_OPTION_TWOTAP_PRODUCTS_LAST_SYNCED', 'twotap_products_last_synced' );

        // Transients
        $this->define( 'TT_TRANSIENT_KEY_CATEGORIES', 'twotap_categories' );
        $this->define( 'TT_TRANSIENT_KEY_SUPPORTED_SITES', 'twotap_supported_sites' );
        $this->define( 'TT_TRANSIENT_KEY_CART_STATUS_REFRESH_TIME', 'twotap_cart_status_refresh_time_' );

        // Misc.
        $this->define( 'TT_THIRTEEN_HOURS_IN_SECONDS', HOUR_IN_SECONDS * 13 );

        // Two Tap statuses
        $this->define( 'TT_STATUS_DONE', 'done' );
        $this->define( 'TT_STATUS_STILL_PROCESSING', 'still_processing' );
        $this->define( 'TT_STATUS_FAILED', 'failed' );
        $this->define( 'TT_STATUS_HAS_FAILURES', 'has_failures' );

        do_action( 'two_tap_after_define_constants' );
    }

    public function define($name, $value){
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    public function env($key, $default = null)
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

new Two_Tap_Define_Constants();