<?php
/**
 * Plugin installer: activation / deactivation tasks.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion;

use StaySuite\Companion\Blocks\Patterns;
use StaySuite\Companion\Frontend\PageTemplate;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles plugin lifecycle hooks.
 */
class Installer {

    /**
     * Required parent theme slug.
     *
     * @var string
     */
    const REQUIRED_THEME = 'wprentals';

    /**
     * Option holding the ID of the homepage page created on activation.
     *
     * @var string
     */
    const HOMEPAGE_OPTION = 'ssc_homepage_page_id';

    /**
     * Option holding the last homepage creation failure timestamp.
     *
     * Set when automatic creation throws (usually a third-party
     * save_post listener fatalling inside wp_insert_post, e.g.
     * Elementor's strictly-typed template listener meeting a null
     * post from another dispatcher). Admin retries hourly until the
     * page exists; deleted on success.
     *
     * @var string
     */
    const HOMEPAGE_RETRY = 'ssc_homepage_retry';

    /**
     * Title of the homepage page created on activation.
     *
     * @var string
     */
    const HOMEPAGE_TITLE = 'Homepage - StaySuite';

    /**
     * Run on plugin activation.
     *
     * Refuses to activate unless WpRentals is the active theme, then
     * registers post types, creates the homepage page and flushes rules.
     *
     * @return void
     */
    public static function activate() {
        if ( ! self::is_required_theme_active() ) {
            deactivate_plugins( plugin_basename( Plugin::file() ) );
            wp_die(
                esc_html__( 'StaySuite Companion for WpRentals requires the WpRentals theme to be installed and activated.', 'staysuite-companion' ),
                esc_html__( 'Plugin Activation Error', 'staysuite-companion' ),
                array(
					'response' => 200,
					'back_link' => true,
                )
            );
        }
        Hotel\HotelCPT::register();
        Booking\RequestCPT::register();
        self::maybe_create_homepage_page();
        flush_rewrite_rules();
    }

    /**
     * Create the StaySuite homepage page if the site does not have one yet.
     *
     * Deliberately does not touch show_on_front or page_on_front: which
     * page fronts the site is the owner's decision, and the site may
     * already have one. Idempotent through the stored option plus a
     * title/template lookup, so reactivating never duplicates the page.
     *
     * The insert runs inside third-party save_post listeners that this
     * plugin does not control, so any throwable is caught, logged and
     * flagged for an hourly admin retry: a hostile hook must never break
     * activation. A throw after the row insert still leaves the page
     * behind, and the title lookup below adopts it on retry instead of
     * cloning it.
     *
     * @return int Page ID, or 0 when creation failed.
     */
    public static function maybe_create_homepage_page() {
        $page_id = intval( get_option( self::HOMEPAGE_OPTION, 0 ) );
        if ( self::is_homepage_page( $page_id ) ) {
            self::apply_homepage_template( $page_id );
            delete_option( self::HOMEPAGE_RETRY );
            return $page_id;
        }

        $page_id = self::find_homepage_page();
        if ( $page_id > 0 ) {
            self::apply_homepage_template( $page_id );
            update_option( self::HOMEPAGE_OPTION, $page_id );
            delete_option( self::HOMEPAGE_RETRY );
            return $page_id;
        }

        try {
            $page_id = wp_insert_post(
                array(
                    'post_title'   => self::HOMEPAGE_TITLE,
                    'post_name'    => sanitize_title( self::HOMEPAGE_TITLE ),
                    'post_content' => Patterns::homepage_content(),
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ),
                true
            );
        } catch ( \Throwable $e ) {
            trigger_error(
                esc_html__( 'StaySuite Companion: homepage creation failed: ', 'staysuite-companion' ) . $e->getMessage(),
                E_USER_WARNING
            );
            update_option( self::HOMEPAGE_RETRY, time() );
            return 0;
        }

        if ( is_wp_error( $page_id ) || ! $page_id ) {
            update_option( self::HOMEPAGE_RETRY, time() );
            return 0;
        }

        $page_id = intval( $page_id );
        self::apply_homepage_template( $page_id );
        update_option( self::HOMEPAGE_OPTION, $page_id );
        delete_option( self::HOMEPAGE_RETRY );

        return $page_id;
    }

    /**
     * Retry a failed automatic homepage creation, throttled to hourly.
     *
     * Called from admin_init. A hostile save_post dispatcher fails every
     * attempt the same way, so retrying on every admin load would only
     * burn cycles; hourly gives the site owner time to fix or remove the
     * conflicting code while still self-healing.
     *
     * @return void
     */
    public static function maybe_retry_homepage() {
        $last = intval( get_option( self::HOMEPAGE_RETRY, 0 ) );
        if ( $last <= 0 || time() - $last < HOUR_IN_SECONDS ) {
            return;
        }
        update_option( self::HOMEPAGE_RETRY, time() );
        self::maybe_create_homepage_page();
    }

    /**
     * Whether a post ID is still a usable homepage page.
     *
     * @param int $page_id Candidate page ID.
     * @return bool True when the ID is a page that is not in the trash.
     */
    private static function is_homepage_page( $page_id ) {
        if ( $page_id <= 0 ) {
            return false;
        }
        return 'page' === get_post_type( $page_id ) && 'trash' !== get_post_status( $page_id );
    }

    /**
     * Find a homepage page that already exists on this site.
     *
     * Matches our page title first, then any page already on the StaySuite
     * Homepage template. The second lookup adopts a homepage the site
     * owner already set up rather than cloning it.
     *
     * @return int Matching page ID, or 0 when none exists.
     */
    private static function find_homepage_page() {
        $found = get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => 'any',
                'title'          => self::HOMEPAGE_TITLE,
                'posts_per_page' => 1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'fields'         => 'ids',
                'no_found_rows'  => true,
            )
        );

        if ( ! empty( $found ) && self::is_homepage_page( intval( $found[0] ) ) ) {
            return intval( $found[0] );
        }

        $found = get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'fields'         => 'ids',
                'no_found_rows'  => true,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One meta lookup on activation only.
                'meta_key'       => '_wp_page_template',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- See above.
                'meta_value'     => PageTemplate::HOMEPAGE_SLUG,
            )
        );

        if ( ! empty( $found ) && self::is_homepage_page( intval( $found[0] ) ) ) {
            return intval( $found[0] );
        }

        return 0;
    }

    /**
     * Assign the StaySuite Homepage template to a page.
     *
     * @param int $page_id Page ID.
     * @return void
     */
    private static function apply_homepage_template( $page_id ) {
        if ( PageTemplate::HOMEPAGE_SLUG === get_page_template_slug( $page_id ) ) {
            return;
        }
        update_post_meta( $page_id, '_wp_page_template', PageTemplate::HOMEPAGE_SLUG );
    }

    /**
     * Check whether WpRentals is installed and active.
     *
     * The get_template() function returns the parent theme slug, so a child
     * theme built on WpRentals also passes this check.
     *
     * @return bool True when the required theme is active.
     */
    public static function is_required_theme_active() {
        if ( get_template() !== self::REQUIRED_THEME ) {
            return false;
        }
        return wp_get_theme( self::REQUIRED_THEME )->exists();
    }

    /**
     * Run on plugin deactivation.
     *
     * @return void
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
