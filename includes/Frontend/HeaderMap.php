<?php
/**
 * Header map suppression for selected pages.
 *
 * The theme shows a Google Map header on any page left on the global
 * header type (maps), with no global opt-out. This enforces the
 * StaySuite "hide map" page list by reporting those pages as local
 * header type "none", which also keeps the Maps JS off the page.
 * No theme file is touched.
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
 * Reports map-hidden pages as local header "none" to the theme.
 */
class HeaderMap {

    /**
     * Theme local header type meaning "none".
     *
     * @var string
     */
    const HEADER_NONE = '1';

    /**
     * Wire up the metadata filter.
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'get_post_metadata', array( $this, 'hide_map_header' ), 10, 4 );
    }

    /**
     * Fake the theme header_type meta as "none" on map-hidden pages.
     *
     * The theme reads this meta on every header render (header_media.php,
     * css_js_include.php), so one filter covers both the map markup and
     * the Maps script queue. Admin reads pass through untouched, so each
     * page's real option still shows in its Page Options box.
     *
     * @param mixed  $value     Filtered value (null = no override).
     * @param int    $object_id Post ID.
     * @param string $meta_key  Meta key.
     * @param bool   $single    Single-value request.
     * @return mixed '1' for hidden pages, untouched otherwise.
     */
    public function hide_map_header( $value, $object_id, $meta_key, $single ) {
        if ( is_admin() || 'header_type' !== $meta_key || ! $single ) {
            return $value;
        }
        $hidden = Settings::get( 'hide_map_pages' );
        if ( ! is_array( $hidden ) || ! in_array( intval( $object_id ), $hidden, true ) ) {
            return $value;
        }
        return self::HEADER_NONE;
    }
}
