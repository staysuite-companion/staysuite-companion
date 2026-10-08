<?php
/**
 * StaySuite top-level menu, tabbed React settings shell and behavior flags.
 *
 * All StaySuite sections live on one page (menu slug ssc-staysuite) with
 * tabs registered through the ssc.admin.tabs JS filter, so Pro injects
 * License/AI tabs without touching free. Go Pro only renders when Pro
 * is absent. Settings persist via the ssc/v1/settings REST route.
 *
 * @package StaySuite\Companion\Admin
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Admin;

use StaySuite\Companion\Links;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Menu, settings storage, REST and frontend behavior flags.
 */
class Settings {

    /**
     * Option key for the settings array.
     *
     * @var string
     */
    const OPTION = 'ssc_settings';

    /**
     * StaySuite menu slug.
     *
     * @var string
     */
    const MENU_SLUG = 'ssc-staysuite';

    /**
     * Compiled admin stylesheet, relative to the plugin root.
     *
     * Built from src/scss/ssc-admin.scss by `npm run build:css`.
     *
     * @var string
     */
    const ADMIN_STYLE = 'assets/build/css/ssc-admin.css';

    /**
     * Default values.
     *
     * @return array<string,mixed> Defaults.
     */
    public static function defaults() {
        return array(
            'default_adults'      => 2,
            'color_mode'          => 'theme',
            'color_submit'        => '#137699',
            'color_hover'         => '#022947',
            'delete_on_uninstall' => 0,
            'search_result'       => 'hotels',
            'group_selection'     => 1,
            'contact_required'    => 'email',
            'invoice_brand'       => 'logo_name',
            'signup_phone'        => 'required',
            'signup_gender'       => 'profile',
            'confirmation_auto'   => 1,
            'confirmation_notes'  => '',
            'mobile_submit'       => 1,
        );
    }

    /**
     * Read and sanitize settings.
     *
     * @param string|null $key Single key or null for all.
     * @return mixed Value or full array.
     */
    public static function get( $key = null ) {
        $all = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
        $all['default_adults'] = min( 6, max( 0, intval( $all['default_adults'] ) ) );
        $all['color_mode'] = ( $all['color_mode'] === 'custom' ) ? 'custom' : 'theme';
        $all['color_submit'] = self::hex_or_default( $all['color_submit'], '#137699' );
        $all['color_hover']  = self::hex_or_default( $all['color_hover'], '#022947' );
        $all['delete_on_uninstall'] = ! empty( $all['delete_on_uninstall'] ) ? 1 : 0;
        $all['search_result'] = ( $all['search_result'] === 'listings' ) ? 'listings' : 'hotels';
        $all['group_selection'] = ! empty( $all['group_selection'] ) ? 1 : 0;
        $all['contact_required'] = in_array( $all['contact_required'], array( 'email', 'phone', 'both' ), true ) ? $all['contact_required'] : 'email';
        $all['invoice_brand'] = in_array( $all['invoice_brand'], array( 'logo_name', 'logo', 'name' ), true ) ? $all['invoice_brand'] : 'logo_name';
        $all['signup_phone'] = self::mode_or_default( $all['signup_phone'] ?? '', 'required', array( 'off', 'optional', 'required' ) );
        $all['signup_gender'] = self::mode_or_default( $all['signup_gender'] ?? '', 'profile', array( 'off', 'profile', 'optional', 'required' ) );
        $all['confirmation_auto'] = ! empty( $all['confirmation_auto'] ) ? 1 : 0;
        $all['confirmation_notes'] = isset( $all['confirmation_notes'] ) ? sanitize_textarea_field( $all['confirmation_notes'] ) : '';
        $all['mobile_submit'] = ! empty( $all['mobile_submit'] ) ? 1 : 0;
        if ( $key === null ) {
            return $all;
        }
        return array_key_exists( $key, $all ) ? $all[ $key ] : null;
    }

    /**
     * Whether multi-room group quotes are enabled.
     *
     * The toggle lives here so owners control it from StaySuite Settings;
     * Pro renders its Add to quote buttons only when this allows it.
     *
     * @return bool True unless the owner disabled group quotes.
     */
    public static function group_selection_enabled() {
        /**
         * Filter group-quote selection availability (Pro: Add to quote buttons).
         *
         * @param bool $enabled Enabled unless disabled in settings.
         */
        return (bool) apply_filters( 'ssc_group_selection_enabled', ! empty( self::get( 'group_selection' ) ) );
    }

    /**
     * Sanitize a raw settings payload (REST + legacy shapes).
     *
     * @param array<string,mixed> $raw Raw input.
     * @return array<string,mixed> Sanitized settings.
     */
    public static function sanitize( $raw ) {
        if ( ! is_array( $raw ) ) {
            $raw = array();
        }
        return array(
            'default_adults' => isset( $raw['default_adults'] ) ? min( 6, max( 0, intval( $raw['default_adults'] ) ) ) : 2,
            'color_mode'     => ( isset( $raw['color_mode'] ) && $raw['color_mode'] === 'custom' ) ? 'custom' : 'theme',
            'color_submit'   => self::hex_or_default( $raw['color_submit'] ?? '', '#137699' ),
            'color_hover'    => self::hex_or_default( $raw['color_hover'] ?? '', '#022947' ),
            'delete_on_uninstall' => ! empty( $raw['delete_on_uninstall'] ) ? 1 : 0,
            'search_result'  => ( isset( $raw['search_result'] ) && $raw['search_result'] === 'listings' ) ? 'listings' : 'hotels',
            'group_selection' => ! empty( $raw['group_selection'] ) ? 1 : 0,
            'contact_required' => ( isset( $raw['contact_required'] ) && in_array( $raw['contact_required'], array( 'email', 'phone', 'both' ), true ) ) ? $raw['contact_required'] : 'email',
            'invoice_brand'    => ( isset( $raw['invoice_brand'] ) && in_array( $raw['invoice_brand'], array( 'logo_name', 'logo', 'name' ), true ) ) ? $raw['invoice_brand'] : 'logo_name',
            'signup_phone'     => self::mode_or_default( $raw['signup_phone'] ?? '', 'required', array( 'off', 'optional', 'required' ) ),
            'signup_gender'    => self::mode_or_default( $raw['signup_gender'] ?? '', 'profile', array( 'off', 'profile', 'optional', 'required' ) ),
            'confirmation_auto' => ! empty( $raw['confirmation_auto'] ) ? 1 : 0,
            'confirmation_notes' => isset( $raw['confirmation_notes'] ) ? sanitize_textarea_field( $raw['confirmation_notes'] ) : '',
            'mobile_submit' => ! empty( $raw['mobile_submit'] ) ? 1 : 0,
        );
    }

    /**
     * Normalize an off/optional/required-style mode with fallback.
     *
     * @param mixed    $value    Raw value.
     * @param string   $fallback Fallback mode.
     * @param string[] $allowed  Valid modes.
     * @return string Valid mode.
     */
    private static function mode_or_default( $value, $fallback, $allowed ) {
        return in_array( $value, $allowed, true ) ? $value : $fallback;
    }

    /**
     * Wire up hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'rest_api_init', array( $this, 'rest_routes' ) );
        add_filter( 'submenu_file', array( $this, 'highlight_tab' ), 10, 2 );
    }

    /**
     * Register the StaySuite top-level menu (below Hotels).
     *
     * Two submenus: Settings (the single-page tab shell) and Group
     * Requests (the CPT list, registered by RequestCPT). Deep links for
     * License/AI and Go Pro live as tabs inside the Settings shell, not
     * as their own submenu entries.
     *
     * @return void
     */
    public function menu() {
        add_menu_page(
            esc_html__( 'StaySuite', 'staysuite-companion' ),
            esc_html__( 'StaySuite', 'staysuite-companion' ),
            'manage_options',
            self::MENU_SLUG,
            array( $this, 'render_page' ),
            self::logo_icon(),
            26
        );
        add_submenu_page(
            self::MENU_SLUG,
            esc_html__( 'Settings', 'staysuite-companion' ),
            esc_html__( 'Settings', 'staysuite-companion' ),
            'manage_options',
            self::MENU_SLUG . '&tab=settings',
            array( $this, 'render_page' )
        );

        global $submenu;
        $first = $submenu[ self::MENU_SLUG ][0] ?? null;
        if ( is_array( $first ) && ( $first[2] ?? '' ) === self::MENU_SLUG ) {
            unset( $submenu[ self::MENU_SLUG ][0] );
        }
    }

    /**
     * Sanitize a hex color, falling back to a default.
     *
     * @param mixed  $value    Raw color.
     * @param string $fallback Color used when the value is not a hex color.
     * @return string Valid hex color or the fallback.
     */
    private static function hex_or_default( $value, $fallback ) {
        if ( ! is_string( $value ) ) {
            return $fallback;
        }
        $color = sanitize_hex_color( $value );
        return ( $color === null || $color === '' ) ? $fallback : $color;
    }

    /**
     * Highlight the matching tab submenu on the single page.
     *
     * @param string|false $submenu_file Current submenu file.
     * @param string       $parent_file  Current parent file.
     * @return string|false Submenu file with tab suffix when applicable.
     */
    public function highlight_tab( $submenu_file, $parent_file ) {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only menu highlight; the Settings screen itself is capability + nonce protected.
        if ( $parent_file !== self::MENU_SLUG ) {
            return $submenu_file;
        }
        // Only force the Settings tab-highlight while actually on the
        // StaySuite settings page. On its CPT screens (Group Requests
        // list/edit, future subscreens) keep whatever WordPress picked,
        // otherwise Settings always looks active.
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( $page !== self::MENU_SLUG ) {
            return $submenu_file;
        }
        if ( ! isset( $_GET['tab'] ) ) {
            return self::MENU_SLUG . '&tab=settings';
        }
        $tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ( ! in_array( $tab, array( 'settings', 'go-pro', 'license', 'ai' ), true ) ) {
            return $submenu_file;
        }
        return self::MENU_SLUG . '&tab=' . $tab;
    }

    /**
     * Brand SVG as menu icon.
     *
     * @return string Data URI or dashicon fallback.
     */
    public static function logo_icon() {
        static $icon = null;
        if ( $icon === null ) {
            $svg = SSC_PATH . 'assets/images/staysuite-logo-menu.svg';
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a bundled local file, not a remote URL.
            $contents = is_readable( $svg ) ? file_get_contents( $svg ) : false;
            if ( is_string( $contents ) ) {
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Data URI for a local SVG menu icon, not obfuscated code.
                $icon = 'data:image/svg+xml;base64,' . base64_encode( $contents );
            } else {
                $icon = 'dashicons-admin-generic';
            }
        }
        return $icon;
    }

    /**
     * Render the tab shell (React mounts here).
     *
     * @return void
     */
    public function render_page() {
        printf(
            '<div id="ssc-admin-root" data-pro="%d" data-logo="%s"></div>',
            defined( 'SSC_PRO_VERSION' ) ? 1 : 0,
            esc_url( SSC_URL . 'assets/images/staysuite-logo.svg' )
        );
    }

    /**
     * Load the admin tab bundle on the StaySuite page.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function admin_assets( $hook ) {
        if ( $hook !== 'toplevel_page_' . self::MENU_SLUG ) {
            return;
        }
        $asset = $this->app_asset();
        wp_enqueue_script(
            'ssc-admin',
            SSC_URL . 'assets/build/admin.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
        wp_localize_script( 'ssc-admin', 'sscLinks', Links::all() );
        wp_enqueue_style(
            'ssc-admin',
            SSC_URL . self::ADMIN_STYLE,
            array(),
            $this->css_version()
        );
    }

    /**
     * Read the wp-scripts asset manifest for the admin bundle.
     *
     * @return array{dependencies: string[], version: string} Asset data.
     */
    private function app_asset() {
        $fallback = array(
			'dependencies' => array( 'wp-element', 'wp-hooks', 'wp-api-fetch', 'wp-i18n', 'wp-components' ),
			'version' => SSC_VERSION,
		);
        $path = SSC_PATH . 'assets/build/admin.asset.php';
        if ( ! file_exists( $path ) ) {
            return $fallback;
        }
        $asset = include $path;
        if ( ! is_array( $asset ) ) {
            return $fallback;
        }
        return array(
            'dependencies' => isset( $asset['dependencies'] ) ? (array) $asset['dependencies'] : $fallback['dependencies'],
            'version'      => isset( $asset['version'] ) ? (string) $asset['version'] : SSC_VERSION,
        );
    }

    /**
     * Version the admin stylesheet by content hash.
     *
     * @return string Cache-busting version.
     */
    private function css_version() {
        $path = SSC_PATH . self::ADMIN_STYLE;
        if ( file_exists( $path ) ) {
            $hash = md5_file( $path );
            if ( is_string( $hash ) && $hash !== '' ) {
                return substr( $hash, 0, 12 );
            }
        }
        return SSC_VERSION;
    }

    /**
     * Register the settings REST route.
     *
     * @return void
     */
    public function rest_routes() {
        register_rest_route(
            'ssc/v1', '/settings', array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get' ),
					'permission_callback' => array( $this, 'rest_auth' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'rest_save' ),
					'permission_callback' => array( $this, 'rest_auth' ),
				),
            )
        );
    }

    /**
     * Capability check for settings routes.
     *
     * @return bool True for admins.
     */
    public function rest_auth() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Return current settings.
     *
     * @return WP_REST_Response Settings payload.
     */
    public function rest_get() {
        return new WP_REST_Response( array( 'settings' => self::get() ), 200 );
    }

    /**
     * Save settings from a JSON payload.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error Fresh settings or error.
     */
    public function rest_save( $request ) {
        $raw = $request->get_param( 'settings' );
        if ( ! is_array( $raw ) ) {
            return new WP_Error( 'ssc_bad_settings', esc_html__( 'Settings payload missing.', 'staysuite-companion' ), array( 'status' => 400 ) );
        }
        update_option( self::OPTION, self::sanitize( $raw ) );
        return new WP_REST_Response( array( 'settings' => self::get() ), 200 );
    }
}
