<?php
/**
 * Points the front page at the StaySuite Homepage template.
 *
 * The template is what makes the homepage look like the homepage: the
 * full-bleed cover, the transparent header and the centred sections all
 * depend on it, and none of that can be faked from CSS alone. Forgetting to
 * pick it in Page Attributes is invisible on a live site - the page renders,
 * just not like the design - so this adds a notice with a one-click fix
 * instead of leaving it to be discovered.
 *
 * @package StaySuite\Companion\Admin
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Admin;

use StaySuite\Companion\Frontend\PageTemplate;
use StaySuite\Companion\Installer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Warns when the front page is not using the plugin's page template.
 */
class HomepageSetup {

    /**
     * Admin-post action used to apply the template.
     *
     * @var string
     */
    const ACTION = 'ssc_apply_homepage_template';

    /**
     * Query flag set after a successful apply.
     *
     * @var string
     */
    const FLAG = 'ssc_homepage_template_applied';

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'admin_notices', array( $this, 'notice' ) );
        add_action( 'admin_post_' . self::ACTION, array( $this, 'apply' ) );
        add_action( 'admin_init', array( $this, 'retry_homepage' ) );
    }

    /**
     * Tell the user the front page is on the wrong template, with a fix.
     *
     * @return void
     */
    public function notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( is_object( $screen ) && in_array( $screen->base, array( 'dashboard', 'upload' ), true ) ) {
            return;
        }

        if ( intval( get_option( Installer::HOMEPAGE_RETRY, 0 ) ) > 0 && '' === $this->homepage_page_link() ) {
            $this->render_notice(
                esc_html__( 'the "Homepage - StaySuite" page could not be created automatically — something on the site interrupted setup. Creation is retried automatically; if this persists, create a page with that title manually and pick the StaySuite Homepage template for it.', 'staysuite-companion' ),
                null,
                ''
            );
            return;
        }

        if ( 'page' !== get_option( 'show_on_front' ) ) {
            $this->render_notice(
                esc_html__( 'your site shows the latest posts on the front page, so the StaySuite Homepage template cannot be used. Pick a static front page under Settings → Reading first.', 'staysuite-companion' ),
                null,
                ''
            );
            return;
        }

        $front_id = intval( get_option( 'page_on_front' ) );
        if ( $front_id <= 0 || 'page' !== get_post_type( $front_id ) ) {
            return;
        }

        if ( PageTemplate::HOMEPAGE_SLUG === get_page_template_slug( $front_id ) ) {
            if ( $this->was_applied() ) {
                $this->render_notice(
                    esc_html__( 'applied — your front page now uses the StaySuite Homepage template. Check the homepage.', 'staysuite-companion' ),
                    null,
                    ''
                );
            }
            return;
        }

        $this->render_notice(
            esc_html__( 'your front page is not using the StaySuite Homepage template. Without it the theme page template renders the homepage inside its own narrow content column, so the cover is not full-bleed and the sections below it are not centred.', 'staysuite-companion' ),
            $this->action_url(),
            esc_html__( 'Apply the StaySuite Homepage template', 'staysuite-companion' )
        );
    }

    /**
     * Link to the homepage page created on activation, when there is one.
     *
     * @return string Edit URL, or an empty string when no page is known.
     */
    private function homepage_page_link() {
        $page_id = intval( get_option( Installer::HOMEPAGE_OPTION, 0 ) );
        if ( $page_id <= 0 || 'page' !== get_post_type( $page_id ) ) {
            return '';
        }
        return get_edit_post_link( $page_id, 'raw' );
    }

    /**
     * Render the admin notice.
     *
     * @param string      $message Notice body.
     * @param string|null $url     Action URL, or null when there is nothing to click.
     * @param string      $label   Action label.
     * @return void
     */
    private function render_notice( $message, $url, $label ) {
        $applied = $this->was_applied();
        printf(
            '<div class="notice %s"><p><strong>%s</strong> %s</p>',
            $applied ? 'notice-success' : 'notice-warning',
            esc_html__( 'StaySuite:', 'staysuite-companion' ),
            $message
        );

        if ( $url !== null && $url !== '' ) {
            printf(
                '<p><a class="button button-primary" href="%s">%s</a></p>',
                esc_url( $url ),
                $label
            );
        }

        $page_url = $this->homepage_page_link();
        if ( '' !== $page_url && 'page' === get_option( 'show_on_front' ) ) {
            printf(
                '<p><a href="%s">%s</a></p>',
                esc_url( $page_url ),
                esc_html__( 'Edit the StaySuite homepage page', 'staysuite-companion' )
            );
        }

        echo '</div>';
    }

    /**
     * Whether this request is the redirect that follows a successful apply.
     *
     * Only ever selects between the warning and success styling, so the value
     * is never printed or used to build a URL. Read without a nonce on
     * purpose: the apply itself is nonce-protected, and requiring one here
     * would mean inventing a second token that has no security value.
     *
     * @return bool True when the apply flag is present.
     */
    private function was_applied() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag, see docblock.
        return isset( $_GET[ self::FLAG ] );
    }

    /**
     * Retry a homepage creation that failed during activation.
     *
     * Activation survives hostile save_post hooks by deferring the page
     * instead of fatalling; this hourly retry (see Installer) finishes
     * the job once the conflict is gone.
     *
     * @return void
     */
    public function retry_homepage() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        Installer::maybe_retry_homepage();
    }

    /**
     * Apply the template to the front page.
     *
     * @return void
     */
    public function apply() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__( 'You do not have permission to change the front page template.', 'staysuite-companion' ),
                esc_html__( 'StaySuite', 'staysuite-companion' ),
                array( 'response' => 403 )
            );
        }
        check_admin_referer( self::ACTION );

        $front_id = intval( get_option( 'page_on_front' ) );
        if ( $front_id > 0 && 'page' === get_post_type( $front_id ) ) {
            update_post_meta( $front_id, '_wp_page_template', PageTemplate::HOMEPAGE_SLUG );
            wp_update_post( array( 'ID' => $front_id ) );
            wp_safe_redirect(
                add_query_arg( self::FLAG, '1', admin_url( 'index.php' ) )
            );
            exit;
        }

        wp_safe_redirect( admin_url( 'index.php' ) );
        exit;
    }

    /**
     * Build the nonce-protected URL for the apply action.
     *
     * @return string Admin URL.
     */
    private function action_url() {
        return wp_nonce_url(
            admin_url( 'admin-post.php?action=' . self::ACTION ),
            self::ACTION
        );
    }
}
