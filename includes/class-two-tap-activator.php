<?php

/**
 * Fired during plugin activation
 *
 * @link       https://twotap.com
 * @since      1.0.0
 *
 * @package    Two_Tap
 * @subpackage Two_Tap/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Two_Tap
 * @subpackage Two_Tap/includes
 * @author     Two Tap <support@twotap.com>
 */
class Two_Tap_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        update_option( 'twotap_public_token', '' );
        update_option( 'twotap_private_token', '' );

        update_option( 'twotap_product_import_limit', 1000 );
        update_option( 'twotap_product_failed_decision', 'outofstock' );
        update_option( 'twotap_markup_type', 'none' );
        update_option( 'twotap_markup_value', 0 );
        update_option( 'twotap_fulfilment_markup', 0 );
        update_option( 'twotap_auto_send_purchase', 'yes' );
        update_option( 'twotap_refresh_images', true );
        update_option( 'twotap_international_logistics_enabled', false );

        update_option( 'wc_url', site_url() );
        update_option( 'wc_consumer_key', '' );
        update_option( 'wc_consumer_secret', '' );
        update_option( TT_OPTION_WEBHOOKS_OK, false );


        global $wp_roles;

        if ( ! class_exists( 'WP_Roles' ) ) {
            return;
        }

        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        $capabilities = self::get_core_capabilities();

        foreach ( $capabilities as $cap_group ) {
            foreach ( $cap_group as $cap ) {
                $wp_roles->add_cap( 'shop_manager', $cap );
                $wp_roles->add_cap( 'administrator', $cap );
            }
        }

        $activator = new Two_Tap_Activator();

        add_action( 'activated_plugin', [$activator, 'onboarding_redirect'] );
    }

    public static function onboarding_redirect()
    {
        $this->setup_taxonomies();

        exit(wp_safe_redirect( admin_url( 'index.php?page=twotap-setup' ) ));
    }

    public function setup_taxonomies()
    {

        require_once TT_ABSPATH . '/includes/post-types/class-two-tap-product-post-type.php';
        $product_post_type = new Two_Tap_Product_Post_Type( 1, 1 );
        $product_post_type->taxonomies();

        require_once TT_ABSPATH . '/includes/post-types/class-two-tap-shop-order-post-type.php';
        $shop_order_post_type = new Two_Tap_Shop_Order_Post_Type( 1, 1 );
        $shop_order_post_type->taxonomies();

        /**
         * Add Two Tap Products taxonomy
         */
        wp_insert_term(
            TT_TERM_TITLE_PRODUCT,
            TT_TERM_TAXONOMY_PRODUCT,
            [
                'description'=> TT_TERM_TITLE_PRODUCT,
                'slug' => TT_TERM_SLUG_PRODUCT,
            ]
        );

        /**
         * Add Two Tap Orders taxonomy
         */
        wp_insert_term(
            TT_TERM_TITLE_ORDER,
            TT_TERM_TAXONOMY_ORDER,
            [
                'description'=> TT_TERM_TITLE_ORDER,
                'slug' => TT_TERM_SLUG_ORDER,
            ]
        );

    }

    /**
     * Get capabilities for WooCommerce - these are assigned to admin/shop manager during installation or reset.
     *
     * @return array
     */
     private static function get_core_capabilities() {
        $capabilities = array();

        $capabilities['core'] = array(
            'manage_twotap',
        );

        $capability_types = array( TT_POST_TYPE_CART, TT_POST_TYPE_PURCHASE );

        foreach ( $capability_types as $capability_type ) {

            $capabilities[ $capability_type ] = array(
                // Post type
                "edit_{$capability_type}",
                "read_{$capability_type}",
                "delete_{$capability_type}",
                "edit_{$capability_type}s",
                "edit_others_{$capability_type}s",
                "publish_{$capability_type}s",
                "read_private_{$capability_type}s",
                "delete_{$capability_type}s",
                "delete_private_{$capability_type}s",
                "delete_published_{$capability_type}s",
                "delete_others_{$capability_type}s",
                "edit_private_{$capability_type}s",
                "edit_published_{$capability_type}s",

                // Terms
                "manage_{$capability_type}_terms",
                "edit_{$capability_type}_terms",
                "delete_{$capability_type}_terms",
                "assign_{$capability_type}_terms",
            );
        }

        return $capabilities;
    }

}
