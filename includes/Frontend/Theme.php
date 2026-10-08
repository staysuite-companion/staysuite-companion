<?php
/**
 * Theme bridge: exposes WpRentals design tokens as CSS variables.
 *
 * The accent colors live in theme options (Redux), so they are read at
 * runtime instead of hardcoded. The plugin stylesheet consumes
 * var(--ssc-accent) etc. and automatically tracks the theme.
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
 * Reads theme options and builds the CSS variable block.
 */
class Theme {

    /**
     * Build search-bar color variables from the theme customizer.
     *
     * The hero follows the custom accent (submit + field icons) and the
     * custom hover color, read live on every load — never the reference
     * widget's explicit style-tab colors.
     *
     * @return string CSS custom properties.
     */
    public static function search_vars() {
        if ( Settings::get( 'color_mode' ) === 'custom' ) {
            $submit = Settings::get( 'color_submit' );
            $hover = Settings::get( 'color_hover' );
        } else {
            $submit = self::accent();
            $hover = self::accent_hover();
        }
        $css = sprintf(
            '--ssc-submit:%s;--ssc-submit-hover:%s;--ssc-search-icon:%s;',
            esc_html( $submit ),
            esc_html( $hover ),
            esc_html( $submit )
        );
        /**
         * Filter search-bar color variables (Pro: alternate mapping).
         *
         * @param string $css    CSS custom properties.
         * @param string $submit Submit/icon hex.
         */
        return apply_filters( 'ssc_search_vars', $css, $submit );
    }
    /**
     * Get the theme accent color.
     *
     * @return string Hex color.
     */
    public static function accent() {
        return self::option( 'wp_estate_main_color', '#e8905a' );
    }

    /**
     * Get the theme button hover color.
     *
     * @return string Hex color.
     */
    public static function accent_hover() {
        return self::option( 'wp_estate_hover_button_color', '#d17a45' );
    }

    /**
     * Get the theme body text color.
     *
     * @return string Hex color.
     */
    public static function text() {
        return self::option( 'wp_estate_font_color', '#5d6475' );
    }

    /**
     * Get the theme headings color.
     *
     * @return string Hex color.
     */
    public static function headings() {
        return self::option( 'wp_estate_headings_color', '#2b2b2b' );
    }

    /**
     * Read a theme color option with fallback.
     *
     * @param string $key      Option key.
     * @param string $fallback Fallback hex color.
     * @return string Sanitized hex color.
     */
    private static function option( $key, $fallback ) {
        $value = function_exists( 'wprentals_get_option' ) ? (string) wprentals_get_option( $key, '' ) : '';
        $hex = sanitize_hex_color( $value );
        return $hex !== '' ? $hex : $fallback;
    }

    /**
     * Build the :root CSS variable declaration.
     *
     * Cover height is a per-cover block attribute (inline --ssc-hero-h on
     * the hero section), not a global, so it is not declared here.
     *
     * @return string CSS custom properties.
     */
    public static function inline_vars() {
        return sprintf(
            ':root{--ssc-accent:%s;--ssc-accent-hover:%s;--ssc-text:%s;--ssc-headings:%s;%s}',
            esc_html( self::accent() ),
            esc_html( self::accent_hover() ),
            esc_html( self::text() ),
            esc_html( self::headings() ),
            self::search_vars()
        );
    }
}
