<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://#
 * @since             1.0.0
 * @package           Pos_Product_Sync
 *
 * @wordpress-plugin
 * Plugin Name:       Pos Product Sync
 * Plugin URI:        https://wpmessiah.com
 * Description:       Pos Product Sync For Woocommerce Product endpoint
 * Version:           1.0.0
 * Author:            Ali Hasan
 * Author URI:        https://#/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pos-product-sync
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Check if WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'Pos Product Sync requires WooCommerce to be installed and active.', 'pos-product-sync' );
        echo '</p></div>';
    } );
    return; // Stop plugin execution
}

/**
 * Currently plugin version.
 */
define( 'POS_PRODUCT_SYNC_VERSION', '1.0.0' );

define( 'BEARER' , 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ0ZXN0LXVzZXIiLCJhdWQiOiJhcGkuZXhhbXBsZS5jb20iLCJpYXQiOjE2OTg1NjAwMDAsImV4cCI6MTY5ODU2MzYwMH0.4JcF5yO3z5uBvFhOQwI8JrR6qJ8tP9x7yQnPjG4kHhA');

/**
 * Activation and Deactivation Hooks
 */
function activate_pos_product_sync() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-pos-product-sync-activator.php';
    Pos_Product_Sync_Activator::activate();
}

function deactivate_pos_product_sync() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-pos-product-sync-deactivator.php';
    Pos_Product_Sync_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pos_product_sync' );
register_deactivation_hook( __FILE__, 'deactivate_pos_product_sync' );

/**
 * Core plugin class
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pos-product-sync.php';

function run_pos_product_sync() {
    $plugin = new Pos_Product_Sync();
    $plugin->run();
}

run_pos_product_sync();
