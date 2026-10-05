<?php
/**
 * Search scope: what the theme's search surfaces return.
 *
 * The theme hardcodes 'estate_property' (rooms) in its advanced search
 * query builder and the plain ?s= search surfaces its listings. When the
 * StaySuite search_result setting is 'hotels' (the default), we rewrite
 * those queries to ssc_hotel and serve hotel-card templates instead of the
 * theme's property-unit templates. 'listings' leaves the theme alone.
 *
 * @package StaySuite\Companion\Frontend
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Frontend;

use StaySuite\Companion\Admin\Settings;
use StaySuite\Companion\Hotel\HotelCPT;
use StaySuite\Companion\Hotel\Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rewrites estate_property search queries to hotels and swaps templates.
 */
class SearchScope {

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'pre_get_posts', array( $this, 'scope_search_queries' ) );
        add_filter( 'template_include', array( $this, 'load_search_templates' ), 25 );
        add_action( 'init', array( $this, 'unslash_search_params' ) );
    }

    /**
     * Unslash the theme's search form GET parameters.
     *
     * WordPress slashes $_GET/$_REQUEST (wp_magic_quotes). The theme's
     * Elementor search form reads $_REQUEST['search_location'] and sanitizes
     * with sanitize_text_field() — which does not unslash — so an apostrophe
     * in a location arrives as Cox\'s Bazar and is printed with the backslash.
     * Unslash the known search fields before the theme reads them.
     *
     * @return void
     */
    public function unslash_search_params() {
        $fields = array(
            'search_location',
            'advanced_city',
            'advanced_area',
            'advanced_country',
            'property_admin_area',
        );
        foreach ( $fields as $field ) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only unslash of search form fields; the theme sanitizes on render.
            if ( isset( $_GET[ $field ] ) ) {
                $_GET[ $field ] = wp_unslash( $_GET[ $field ] );
            }
            if ( isset( $_REQUEST[ $field ] ) ) {
                $_REQUEST[ $field ] = wp_unslash( $_REQUEST[ $field ] );
            }
            // phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }
    }

    /**
     * Hotels or listings? Only rewrite when hotels is configured.
     *
     * @return bool True when search should return hotels.
     */
    public static function returns_hotels() {
        return Settings::get( 'search_result' ) !== 'listings';
    }

    /**
     * Rewrite estate_property search queries to ssc_hotel.
     *
     * @param \WP_Query $query Query about to run.
     * @return void
     */
    public function scope_search_queries( $query ) {
        if ( is_admin() || ! self::returns_hotels() || ! $query instanceof \WP_Query ) {
            return;
        }

        // Our own room-lookup query must never be rewritten — mark it.
        if ( $query->get( 'ssc_room_lookup' ) ) {
            return;
        }

        $is_native_search = $query->is_search() && $query->is_main_query();
        $is_adv_search    = $this->is_advanced_search_page()
            && $query->get( 'post_type' ) === 'estate_property';

        if ( ! $is_native_search && ! $is_adv_search ) {
            return;
        }

        // The availability scan in the theme's date-filter double loop runs
        // with posts_per_page = -1 and must stay a ROOM query: booking
        // availability lives on rooms, not hotels.
        if ( $is_adv_search && intval( $query->get( 'posts_per_page' ) ) === -1 ) {
            return;
        }

        // estate_property carries the taxonomy/price meta the theme filters
        // by; hotels do not. Evaluate those filters against rooms, then keep
        // only hotels with at least one matching room.
        if ( $is_adv_search ) {
            $matching_hotel_ids = $this->hotel_ids_matching_filters( $query );
            if ( $matching_hotel_ids !== null ) {
                // An empty post__in means "no restriction" in WP_Query — use 0 to force zero results.
                $query->set( 'post__in', $matching_hotel_ids === array() ? array( 0 ) : $matching_hotel_ids );
            }
            $query->set( 'meta_query', array() );
            $query->set( 'tax_query', array() );
        }

        $query->set( 'post_type', HotelCPT::POST_TYPE );
        $query->set( 'ssc_search_hotels', 1 );

        // Hotels carry no estate_property meta, so any meta_key/orderby the
        // theme set (prop_featured, property_bedrooms, …) would make the
        // postmeta INNER JOIN exclude every hotel. Clear it unconditionally.
        $query->set( 'meta_key', '' );
        if ( in_array( $query->get( 'orderby' ), array( 'meta_value', 'meta_value_num' ), true ) ) {
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'DESC' );
        }

        // The theme's posts_orderby callback orders by postmeta.meta_value
        // assuming the prop_featured meta join exists — without it the SQL
        // errors and the query returns zero rows.
        remove_filter( 'posts_orderby', 'wpestate_my_order' );
    }

    /**
     * Resolve hotel IDs that have at least one room matching the query's
     * tax_query / meta_query / post__in filters. Null when the query carries
     * no room-level filters at all.
     *
     * @param \WP_Query $query Query about to run.
     * @return int[]|null Matching hotel IDs (possibly empty) or null.
     */
    private function hotel_ids_matching_filters( $query ) {
        // The theme pushes `null` elements into its tax_query for every
        // empty filter field (({"relation":"AND","0":null})); WP silently
        // ignores them, but they are not a filter. Strip non-array clauses
        // before deciding there is a filter to translate.
        $tax_query  = self::clean_query_clauses( $query->get( 'tax_query' ) );
        $meta_query = self::clean_query_clauses( $query->get( 'meta_query' ) );
        $post__in   = $query->get( 'post__in' );

        $has_tax  = $tax_query !== array();
        $has_meta = $meta_query !== array();
        $has_ids  = is_array( $post__in ) && $post__in !== array();

        if ( ! $has_tax && ! $has_meta && ! $has_ids ) {
            return null;
        }

        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Hotel search resolves room filters in one bounded room query; running it per-hotel would be far worse.
        $room_args = array(
            'post_type'       => 'estate_property',
            'post_status'     => 'publish',
            'posts_per_page'  => -1,
            'fields'          => 'ids',
            'no_found_rows'   => true,
            'ssc_room_lookup' => true,
        );
        if ( $has_tax ) {
            $room_args['tax_query'] = $tax_query;
        }
        if ( $has_meta ) {
            $room_args['meta_query'] = $meta_query;
        }
        if ( $has_ids ) {
            $room_args['post__in'] = $post__in;
        }

        $room_ids = get_posts( $room_args );
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        if ( ! is_array( $room_ids ) ) {
            return array();
        }
        return $this->rooms_to_hotels( $room_ids );
    }

    /**
     * Strip null and non-array clauses from a tax/meta query, and drop the
     * bare 'relation' key when nothing remains. Mirrors how WP_Query parses
     * these, so an empty/broken clause list is treated as no filter.
     *
     * @param mixed $query Raw tax_query or meta_query.
     * @return array Cleaned clause list (empty when no real clause).
     */
    private static function clean_query_clauses( $query ) {
        if ( ! is_array( $query ) ) {
            return array();
        }
        $clean = array();
        foreach ( $query as $key => $clause ) {
            if ( $key === 'relation' ) {
                continue; // Re-added below only when clauses remain.
            }
            if ( is_array( $clause ) && $clause !== array() ) {
                $clean[] = $clause;
            }
        }
        if ( $clean === array() ) {
            return array();
        }
        if ( isset( $query['relation'] ) && is_string( $query['relation'] ) ) {
            $clean['relation'] = $query['relation'];
        }
        return $clean;
    }

    /**
     * Map room IDs to their hotel IDs, deduplicated.
     *
     * @param int[] $room_ids Room post IDs.
     * @return int[] Hotel post IDs.
     */
    private function rooms_to_hotels( $room_ids ) {
        $hotel_ids = array();
        foreach ( $room_ids as $room_id ) {
            $hotel_id = Repository::get_room_hotel_id( intval( $room_id ) );
            if ( $hotel_id > 0 ) {
                $hotel_ids[] = $hotel_id;
            }
        }
        return array_values( array_unique( $hotel_ids ) );
    }

    /**
     * True when the queried page uses the theme's Advanced Search Results template.
     *
     * @return bool Template match.
     */
    private function is_advanced_search_page() {
        if ( function_exists( 'is_page_template' ) && is_page_template( 'advanced_search_results.php' ) ) {
            return true;
        }
        $slug = get_page_template_slug();
        return $slug === 'advanced_search_results.php';
    }

    /**
     * Serve plugin hotel templates for search surfaces.
     *
     * @param string $template Resolved template path.
     * @return string Plugin template or original.
     */
    public function load_search_templates( $template ) {
        if ( ! self::returns_hotels() ) {
            return $template;
        }

        if ( is_search() ) {
            $plugin_template = SSC_PATH . 'templates/search-ssc-hotels.php';
        } elseif ( $this->is_advanced_search_page() ) {
            $plugin_template = SSC_PATH . 'templates/advanced-search-results-ssc-hotels.php';
        } else {
            return $template;
        }

        if ( file_exists( $plugin_template ) ) {
            /**
             * Filter the hotel search template path (Pro: overrides).
             *
             * @param string $plugin_template Plugin template file.
             */
            return apply_filters( 'ssc_search_template', $plugin_template );
        }
        return $template;
    }
}
