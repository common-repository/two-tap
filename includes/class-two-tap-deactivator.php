<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://twotap.com
 * @since      1.0.0
 *
 * @package    Two_Tap
 * @subpackage Two_Tap/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Two_Tap
 * @subpackage Two_Tap/includes
 * @author     Two Tap <support@twotap.com>
 */
class Two_Tap_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

        if(wc_tokens_set() && wc_api_enabled()){
            global $wc_api;
            $webhooks = $wc_api->get('webhooks');
            $webhooks = array_filter($webhooks, function($hook){
                return $hook['topic'] == 'order.created' && $hook['delivery_url'] == site_url('wp-json/two_tap/wc_order_created');
            });
            if(count($webhooks)){
                foreach ($webhooks as $hook) {
                    $wc_api->delete("webhooks/{$hook['id']}", ['force' => true]);
                };
            }
        }

        /**
         * Delete options
         */

        $options_to_delete = [
            'twotap_public_token',
            'twotap_private_token',

            'twotap_product_import_limit',
            'twotap_product_failed_decision',
            'twotap_markup_type',
            'twotap_markup_value',
            'twotap_fulfilment_markup',
            'twotap_auto_send_purchase',
            'twotap_refresh_images',

            TT_OPTION_TWOTAP_PRODUCTS_LAST_SYNCED,
            'twotap_logistics_type',
            'twotap_logistics_settings',
            'twotap_shipping_info',
            TT_OPTION_WEBHOOKS_OK,
            'twotap_marked_products',
            'twotap_notices',
            'twotap_products_last_synced',
            'twotap_wc_api_enabled',
            'twotap_international_logistics_enabled',

            'wc_url',
            'wc_consumer_key',
            'wc_consumer_secret',
        ];

        array_walk($options_to_delete, function($option){
            delete_option( $option );
        });

        $transients_to_delete = [
            TT_TRANSIENT_KEY_SUPPORTED_SITES,
            TT_TRANSIENT_KEY_CATEGORIES
        ];

        array_walk($transients_to_delete, function($option){
            delete_transient( $option );
        });

        /**
         * Delete Two Tap Products terms
         */
        // $twotap_product_term = get_term_by('slug', TT_TERM_SLUG_PRODUCT, TT_TERM_TAXONOMY_PRODUCT);
        // if($twotap_product_term){
        //     wp_delete_term( $twotap_product_term->term_id, TT_TERM_TAXONOMY_PRODUCT, TT_TERM_SLUG_PRODUCT );
        // }

        /**
         * Delete Two Tap Orders terms
         */
        // $twotap_order_term = get_term_by('slug', TT_TERM_SLUG_ORDER, TT_TERM_TAXONOMY_ORDER);
        // if($twotap_order_term){
        //     wp_delete_term( $twotap_order_term->term_id, TT_TERM_TAXONOMY_ORDER, TT_TERM_SLUG_ORDER );
        // }

        /**
         * Clear scheduled events
         */
        $events = ['twotap_cron_everysecond', 'twotap_cron_everytenminutes', 'twotap_cron_hourly', 'twotap_cron_twicedaily', 'twotap_cron_daily'];
        array_walk($events, function($event){
            wp_clear_scheduled_hook($event);
        });

	}

}
