<?php
/**
 * Plugin-owned page templates selectable in Page Attributes.
 *
 * WordPress only lists templates from the theme, so this class injects
 * ours through theme_page_templates and serves them via template_include.
 * No theme files are touched.
 *
 * @package StaySuite\Companion\Frontend
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers and serves plugin page templates.
 */
class PageTemplate {

    /**
     * Template slug used in Page Attributes and post meta.
     *
     * @var string
     */
    const HOMEPAGE_SLUG = 'ssc-homepage';

    /**
     * Template slug for the branded invoice page.
     *
     * @var string
     */
    const INVOICE_SLUG = 'ssc-invoice';

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'theme_page_templates', array( $this, 'add_templates' ) );
        add_filter( 'template_include', array( $this, 'load_template' ), 30 );
        add_filter( 'body_class', array( $this, 'add_homepage_class' ) );
        add_filter( 'get_post_metadata', array( $this, 'force_transparent_header' ), 10, 4 );
    }

    /**
     * Add plugin templates to the Page Attributes dropdown.
     *
     * @param array<string,string> $templates Theme templates (file => label).
     * @return array<string,string> Templates with ours added.
     */
    public function add_templates( $templates ) {
        $templates[ self::HOMEPAGE_SLUG ] = esc_html__( 'StaySuite Homepage', 'staysuite-companion' );
        $templates[ self::INVOICE_SLUG ]  = esc_html__( 'StaySuite Invoice', 'staysuite-companion' );
        return $templates;
    }

    /**
     * Serve the plugin template file for assigned pages.
     *
     * @param string $template Template path resolved by WordPress.
     * @return string Plugin template for StaySuite Homepage pages, otherwise untouched.
     */
    public function load_template( $template ) {
        if ( is_page() && get_page_template_slug() === self::HOMEPAGE_SLUG ) {
            $plugin_template = SSC_PATH . 'templates/page-ssc-homepage.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        if ( is_page() && get_page_template_slug() === self::INVOICE_SLUG ) {
            $plugin_template = SSC_PATH . 'templates/page-ssc-invoice.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        return $template;
    }

    /**
     * Flag StaySuite Homepage pages for the transparent overlay header.
     *
     * @param string[] $classes Body classes.
     * @return string[] Body classes with homepage flag added when applicable.
     */
    public function add_homepage_class( $classes ) {
        if ( is_page() && get_page_template_slug() === self::HOMEPAGE_SLUG ) {
            $classes[] = 'ssc-homepage';
        }
        if ( is_page() && get_page_template_slug() === self::INVOICE_SLUG ) {
            $classes[] = 'ssc-invoice-page';
        }
        return $classes;
    }

    /**
     * Force the theme's transparent header on StaySuite Homepage pages.
     *
     * The theme reads the transparent_status post meta directly, so this
     * answers "yes" for our template and the theme applies its own
     * transparent_header styling (light menu links included).
     *
     * @param mixed  $value   Filtered meta value (null to proceed normally).
     * @param int    $post_id Post ID.
     * @param string $meta_key Meta key requested.
     * @param bool   $single  Whether a single value was requested.
     * @return mixed "yes" for our pages, otherwise untouched.
     */
    public function force_transparent_header( $value, $post_id, $meta_key, $single ) {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by the get_post_metadata filter signature.
        unset( $single );
        if ( $meta_key !== 'transparent_status' || is_admin() || $value !== null ) {
            return $value;
        }
        if ( get_post_type( $post_id ) !== 'page' ) {
            return $value;
        }
        if ( get_page_template_slug( $post_id ) !== self::HOMEPAGE_SLUG ) {
            return $value;
        }
        return 'yes';
    }
}
