<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://twotap.com
 * @since             1.0.0
 * @package           Two_Tap
 *
 * @wordpress-plugin
 * Plugin Name:       Two Tap Product Catalog
 * Plugin URI:        https://twotap.com/ecommerce-plugins/
 * Description:       Two Tap Product Catalog allows WooCommerce stores to easily import products from well known US brands and retailers and ship them directly to customers.
 * Version:           0.2.13
 * Author:            Two Tap
 * Author URI:        https://twotap.com/ecommerce-plugins/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       two-tap
 * Domain Path:       /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.3.3
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-two-tap-activator.php
 */
 if(!function_exists('activate_two_tap')) {
    function activate_two_tap() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-two-tap-activator.php';
        Two_Tap_Activator::activate();
    }
}

/**
* The code that runs during plugin deactivation.
* This action is documented in includes/class-two-tap-deactivator.php
*/
if(!function_exists('deactivate_two_tap')) {
    function deactivate_two_tap() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-two-tap-deactivator.php';
        Two_Tap_Deactivator::deactivate();
    }
}

register_activation_hook( __FILE__, 'activate_two_tap' );
register_deactivation_hook( __FILE__, 'deactivate_two_tap' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-two-tap.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if(!function_exists('run_two_tap')) {
    function run_two_tap() {
        define( 'TT_ABSPATH', dirname( __FILE__ ) );
        define( 'TT_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

        $plugin = new Two_Tap();
        $plugin->run();

    }
}
run_two_tap();
