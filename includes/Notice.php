<?php
/**
 * Admin notices: PHP requirement and WpRentals theme checks.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Owns every admin notice the plugin renders.
 *
 * Extracted from the bootstrap Plugin class so notice copy, screening and
 * dismissal live beside each other. Hook names, the AJAX action and the
 * per-user dismissal key are unchanged.
 */
final class Notice {

    /**
     * User meta key remembering the theme-notice dismissal.
     *
     * @var string
     */
    const DISMISS_META = 'ssc_theme_notice_dismissed';

    /**
     * Nonce action for notice dismissal.
     *
     * @var string
     */
    const NONCE_ACTION = 'ssc_dismiss_notice';

    /**
     * AJAX action for notice dismissal.
     *
     * @var string
     */
    const AJAX_ACTION = 'ssc_dismiss_notice';

    /**
     * Owning plugin instance, for container access.
     *
     * @var Plugin
     */
    private $plugin;

    /**
     * Set the owning plugin instance.
     *
     * @param Plugin $plugin Owning plugin instance.
     */
    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Wire up notice hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'admin_notices', array( $this, 'theme_check_notice' ) );
        add_action( 'admin_notices', array( $this, 'php_upgrade_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'notice_assets' ) );
        add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'dismiss_notice' ) );
    }

    /**
     * Show an admin notice when PHP is too old.
     *
     * Rendered directly (not via register()) because an unsupported PHP
     * version short-circuits the plugin setup before notices register.
     *
     * @return void
     */
    public function php_version_notice() {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %s: minimum PHP version */
                    __( 'StaySuite Companion for WpRentals requires PHP %s or newer.', 'staysuite-companion' ),
                    Plugin::MIN_PHP
                )
            )
        );
    }

    /**
     * Suggest upgrading to PHP 8.3 or newer when the server is on an older
     * 7.x/8.0–8.2 runtime. Admins only, rendered on normal admin screens.
     *
     * @return void
     */
    public function php_upgrade_notice() {
        if ( version_compare( PHP_VERSION, '8.3.0', '>=' ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html__( 'StaySuite Companion supports PHP 7.4+, but PHP 8.3 or newer is strongly recommended for better performance and security.', 'staysuite-companion' )
        );
    }

    /**
     * Warn when the active theme is not WpRentals.
     *
     * Administrators only, dismissed per user, and only on the Plugins
     * screen and StaySuite/Hotels screens — never a global nag.
     *
     * @return void
     */
    public function theme_check_notice() {
        if ( get_template() === Installer::REQUIRED_THEME ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( get_user_meta( get_current_user_id(), self::DISMISS_META, true ) ) {
            return;
        }
        if ( ! $this->is_notice_screen() ) {
            return;
        }
        printf(
            '<div class="notice notice-warning is-dismissible" data-ssc-dismissible="theme"><p>%s</p></div>',
            esc_html__( 'StaySuite Companion for WpRentals is built for the WpRentals theme. Some features may not work with the active theme.', 'staysuite-companion' )
        );
    }

    /**
     * Load the notice-dismissal script where the notice may appear.
     *
     * @return void
     */
    public function notice_assets() {
        if ( get_template() === Installer::REQUIRED_THEME || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( ! $this->is_notice_screen() ) {
            return;
        }
        wp_enqueue_script( 'ssc-notice', Plugin::url() . 'assets/js/ssc-notice.js', array(), Plugin::VERSION, true );
        wp_localize_script(
            'ssc-notice', 'sscNotice', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
            )
        );
    }

    /**
     * Remember a notice dismissal for the current user.
     *
     * @return void
     */
    public function dismiss_notice() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(), 403 );
        }
        $key = isset( $_POST['key'] ) && is_string( $_POST['key'] )
            ? sanitize_key( wp_unslash( $_POST['key'] ) )
            : '';
        if ( 'theme' !== $key ) {
            wp_send_json_error( array(), 400 );
        }
        update_user_meta( get_current_user_id(), self::DISMISS_META, 1 );
        wp_send_json_success();
    }

    /**
     * Whether the current admin screen may show the theme notice.
     *
     * @return bool True on the Plugins screen and StaySuite/Hotels screens.
     */
    private function is_notice_screen() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return false;
        }
        $screen = get_current_screen();
        if ( ! $screen instanceof \WP_Screen ) {
            return false;
        }
        $allowed = array(
            'plugins',
            'toplevel_page_' . Admin\Settings::MENU_SLUG,
            'edit-' . Hotel\HotelCPT::POST_TYPE,
            Hotel\HotelCPT::POST_TYPE,
            'edit-' . Booking\RequestCPT::POST_TYPE,
            Booking\RequestCPT::POST_TYPE,
        );
        $assign = $this->plugin->assign_page;
        if ( $assign instanceof Admin\AssignPage && '' !== $assign->hook_suffix() ) {
            $allowed[] = $assign->hook_suffix();
        }
        return in_array( $screen->id, $allowed, true );
    }
}
