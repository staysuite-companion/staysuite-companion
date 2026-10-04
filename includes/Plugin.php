<?php
/**
 * Main plugin class: constants, container and hook wiring.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion;

use StaySuite\Companion\Admin\AssignPage;
use StaySuite\Companion\Frontend\CardBadge;
use StaySuite\Companion\Frontend\RoomSingleLink;
use StaySuite\Companion\Frontend\Scripts;
use StaySuite\Companion\Frontend\TemplateLoader;
use StaySuite\Companion\Frontend\SearchScope;
use StaySuite\Companion\Hotel\HotelCPT;
use StaySuite\Companion\Hotel\RoomLink;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 *
 * Singleton that owns the plugin constants, the class container and the
 * action hooks. The bootstrap file only passes the plugin file path in and
 * calls boot(); everything else happens here. Global SSC_* constants remain
 * as aliases for backward compatibility — the class constants below are the
 * source of truth.
 */
final class Plugin {

    /**
     * Plugin version. Synced by bin/release.sh — never edit by hand.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Minimum PHP version required.
     *
     * @var string
     */
    const MIN_PHP = '8.1';

    /**
     * Plugin text domain.
     *
     * @var string
     */
    const TEXT_DOMAIN = 'staysuite-companion';

    /**
     * User meta key remembering the theme-notice dismissal.
     *
     * Deprecated: moved to Notice::DISMISS_META. Kept as an alias so
     * existing references keep working.
     *
     * @var string
     */
    const NOTICE_DISMISS_META = Notice::DISMISS_META;

    /**
     * Nonce action for notice dismissal.
     *
     * Deprecated: moved to Notice::NONCE_ACTION. Kept as an alias so
     * existing references keep working.
     *
     * @var string
     */
    const NOTICE_NONCE_ACTION = Notice::NONCE_ACTION;

    /**
     * Absolute path of the main plugin file.
     *
     * @var string
     */
    private static $file = '';

    /**
     * Absolute path of the plugin directory, with trailing slash.
     *
     * @var string
     */
    private static $path = '';

    /**
     * URL of the plugin directory, with trailing slash.
     *
     * @var string
     */
    private static $url = '';

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = self::VERSION;

    /**
     * Holds shared class instances.
     *
     * @var array<string, object>
     */
    private $container = array();

    /**
     * Single instance of this class.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Register the plugin's lifecycle hooks.
     *
     * Called once from the bootstrap file. Stores the file, path and URL
     * the whole plugin derives from, and wires booting plus the
     * activation/deactivation callbacks. Everything the plugin hooks into
     * WordPress is registered here, not in the bootstrap file.
     *
     * @param string $plugin_file Absolute path of the main plugin file.
     * @return void
     */
    public static function register( $plugin_file ) {
        self::$file = $plugin_file;
        self::$path = plugin_dir_path( $plugin_file );
        self::$url  = plugin_dir_url( $plugin_file );

        add_action( 'plugins_loaded', array( __CLASS__, 'boot' ), 5 );
        register_activation_hook( $plugin_file, array( __CLASS__, 'activate' ) );
        register_deactivation_hook( $plugin_file, array( __CLASS__, 'deactivate' ) );
    }

    /**
     * Boot on plugins_loaded.
     *
     * @return void
     */
    public static function boot() {
        self::init();
    }

    /**
     * Activation callback registered in register().
     *
     * @return void
     */
    public static function activate() {
        Installer::activate();
    }

    /**
     * Deactivation callback registered in register().
     *
     * @return void
     */
    public static function deactivate() {
        Installer::deactivate();
    }

    /**
     * Get or create the plugin instance.
     *
     * @return Plugin Single instance of this class.
     */
    public static function init() {
        if ( ! isset( self::$instance ) || ! ( self::$instance instanceof Plugin ) ) {
            self::$instance = new Plugin();
            self::$instance->setup();
        }
        return self::$instance;
    }

    /**
     * Absolute path of the main plugin file.
     *
     * @return string Absolute file path.
     */
    public static function file() {
        return self::$file;
    }

    /**
     * Absolute path of the plugin directory, with trailing slash.
     *
     * @return string Absolute directory path.
     */
    public static function path() {
        return self::$path;
    }

    /**
     * URL of the plugin directory, with trailing slash.
     *
     * @return string Plugin directory URL.
     */
    public static function url() {
        return self::$url;
    }

    /**
     * Plugin version.
     *
     * @return string Current version.
     */
    public static function version() {
        return self::VERSION;
    }

    /**
     * Set up the plugin: includes, instances and hooks.
     *
     * @return void
     */
    private function setup() {
        if ( ! $this->is_supported_php() ) {
            add_action( 'admin_notices', array( $this->container_notice_fallback(), 'php_version_notice' ) );
            return;
        }

        $this->instantiate();
        $this->init_actions();

        do_action( 'ssc_loaded' );
    }

    /**
     * Notice instance for the unsupported-PHP path.
     *
     * The container does not exist yet on this path, so build a throwaway
     * Notice bound to this instance instead of reading the container.
     *
     * @return Notice Notice instance bound to this plugin.
     */
    private function container_notice_fallback() {
        return new Notice( $this );
    }

    /**
     * Magic getter for container instances.
     *
     * @param string $prop Instance key.
     * @return mixed Instance or null when unknown.
     */
    public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }
        return null;
    }

    /**
     * Magic isset for container instances.
     *
     * @param string $prop Instance key.
     * @return bool Whether the instance exists.
     */
    public function __isset( $prop ) {
        return isset( $this->container[ $prop ] );
    }

    /**
     * Check whether the server PHP version is supported.
     *
     * @return bool True when supported.
     */
    public function is_supported_php() {
        return version_compare( PHP_VERSION, self::MIN_PHP, '>=' );
    }

    /**
     * Instantiate domain classes and keep shared ones in the container.
     *
     * @return void
     */
    private function instantiate() {
        $this->container['hotel_cpt']       = new HotelCPT();
        $this->container['room_link']       = new RoomLink();
        $this->container['template_loader'] = new TemplateLoader();
        $this->container['page_template']   = new Frontend\PageTemplate();
        $this->container['room_single']     = new RoomSingleLink();
        $this->container['card_badge']      = new CardBadge();
        $this->container['scripts']         = new Scripts();
        $this->container['page_setup']      = new Frontend\PageSetup();
        $this->container['settings']        = new Admin\Settings();
        $this->container['search_scope']    = new SearchScope();
        $this->container['homepage_setup']  = new Admin\HomepageSetup();
        $this->container['blocks']          = new Blocks\Registry();
        $this->container['block_preview']   = new Blocks\PreviewEndpoint();
        $this->container['patterns']        = new Blocks\Patterns();

        $this->container['group_request']    = new Booking\RequestCPT();
        $this->container['quote_form']       = new Booking\QuoteForm();
        $this->container['quote_ajax']       = new Booking\QuoteAjax();

        if ( is_admin() ) {
            $this->container['assign_page'] = new AssignPage();
            $this->container['term_repair'] = new Admin\TermRepair();
            $this->container['term_image'] = new Admin\TermImage();
        }

        $this->container['notices'] = new Notice( $this );
    }

    /**
     * Initialize localization, links and admin notices.
     *
     * @return void
     */
    private function init_actions() {
        add_filter( 'plugin_action_links_' . plugin_basename( self::$file ), array( $this, 'plugin_action_links' ) );
        $this->container['notices']->register();
    }

    /**
     * Add action links on the plugins list.
     *
     * @param string[] $links Existing action links.
     * @return string[] Links with companion pages added.
     */
    public function plugin_action_links( $links ) {
        $links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . HotelCPT::POST_TYPE ) ) . '">'
            . esc_html__( 'Hotels', 'staysuite-companion' ) . '</a>';
        return $links;
    }
}
