<?php
/**
 * Email branding for invoice messages.
 *
 * Wprentals-core sends invoice mails (new_wire_transfer, full_invoice_reminder,
 * admin wire notifications) through wp_mail. We do not change the theme or core;
 * this filter detects invoice subjects and replaces the raw message with an
 * email-safe StaySuite invoice shell that keeps the original body content.
 *
 * @package StaySuite\Companion\Frontend
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Frontend;

use StaySuite\Companion\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers wp_mail filtering.
 */
class InvoiceEmail {

    /**
     * Wire hooks.
     */
    public function __construct() {
        add_filter( 'wp_mail', array( $this, 'filter_invoice_email' ) );
    }

    /**
     * Filter wp_mail arguments.
     *
     * @param array<string,mixed> $args WP mail args.
     * @return array<string,mixed> Modified arguments.
     */
    public function filter_invoice_email( $args ) {
        if ( ! is_array( $args ) || empty( $args['subject'] ) || empty( $args['message'] ) ) {
            return $args;
        }

        // StaySuite document shells (confirmation receipts) brand
        // themselves; wrapping them again would nest shells.
        if ( strpos( (string) $args['message'], '<!--ssc-doc-->' ) !== false ) {
            return $args;
        }

        if ( stripos( (string) $args['subject'], 'invoice' ) === false && stripos( (string) $args['message'], 'invoice' ) === false ) {
            return $args;
        }

        $args['message'] = $this->branded_message( (string) $args['message'], (string) $args['subject'] );
        return $args;
    }

    /**
     * Build a basic branded email body into the StaySuite look.
     *
     * @param string $message Existing mail body.
     * @param string $subject Original subject.
     * @return string Email-safe branded body.
     */
    private function branded_message( $message, $subject ) {
        $logo = '';
        if ( function_exists( 'wprentals_get_option' ) ) {
            $logo = wprentals_get_option( 'wp_estate_logo_image', 'url' );
        }
        if ( empty( $logo ) ) {
            $logo = SSC_URL . 'assets/images/staysuite-logo.svg';
        }
        $brand = esc_html( get_bloginfo( 'name' ) );
        $brand_mode = Settings::get( 'invoice_brand' );

        $html = '<div style="background:#f7f7f7;padding:24px 0;">';
        $html .= '<table role="presentation" width="100%" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e5e5;">';
        $html .= '<tr><td style="padding:24px;display:flex;align-items:center;gap:16px;">';
        if ( $brand_mode !== 'name' ) {
            $html .= '<img src="' . esc_url( $logo ) . '" alt="' . $brand . '" style="width:120px;max-height:60px;object-fit:contain;display:block;" />';
        }
        if ( $brand_mode !== 'logo' ) {
            $html .= '<div><div style="font-size:18px;font-weight:700;color:#2b2b2b;">' . $brand . '</div></div>';
        }
        $html .= '</td></tr>';
        $html .= '<tr><td style="padding:0 24px 24px;"><h2 style="margin:0 0 16px;color:#2b2b2b;">' . esc_html( $subject ) . '</h2><div style="color:#5d6475;font-size:15px;line-height:1.6;">' . wp_kses_post( $message ) . '</div></td></tr>';
        $html .= '</table></div>';
        return $html;
    }
}
