<?php
/**
 * Plugin Name:       StaySuite Companion for WpRentals
 * Plugin URI:        https://jktanmay.com/products/staysuite-companion
 * Description:       Hotel pages, homepage booking blocks and group quote requests for the WpRentals theme. No theme files are modified.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Tanmay Kirtania
 * Author URI:        https://jktanmay.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       staysuite-companion
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion;

// Don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( Plugin::class ) ) {
    add_action(
        'admin_notices',
        static function () {
            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'StaySuite Companion for WpRentals could not load its files. Please reinstall the plugin.', 'staysuite-companion' )
                . '</p></div>';
        }
    );
    return;
}

// Backward-compatibility aliases. The Plugin class constants are the source
// of truth; these globals stay so existing code, the Pro add-on and the
// release tooling keep working. Do not add new SSC_* globals.
if ( ! defined( 'SSC_VERSION' ) ) {
    define( 'SSC_VERSION', Plugin::VERSION );
}
if ( ! defined( 'SSC_FILE' ) ) {
    define( 'SSC_FILE', __FILE__ );
}
if ( ! defined( 'SSC_PATH' ) ) {
    define( 'SSC_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SSC_URL' ) ) {
    define( 'SSC_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Get the plugin instance.
 *
 * @return Plugin Single instance of this class.
 */
// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- Bootstrap file: the singleton accessor belongs beside the plugin it returns.
function plugin() {
    return Plugin::init();
}
// phpcs:enable Universal.Files.SeparateFunctionsFromOO.Mixed

Plugin::register( __FILE__ );
