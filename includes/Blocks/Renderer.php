<?php
/**
 * Shared server-side renderers for blocks and shortcodes.
 *
 * Every public render method accepts a normalized attribute array and
 * returns HTML. Blocks (render_callback) and shortcodes call the same
 * methods, so both editors stay in sync by construction.
 *
 * @package StaySuite\Companion\Blocks
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Blocks;

use StaySuite\Companion\Hotel\HotelCPT;
use StaySuite\Companion\Hotel\Repository;
use WP_Post;
use WP_Query;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders tablets, carousels and hero sections.
 */
class Renderer {

    /**
     * Taxonomies allowed as tablet / carousel sources.
     *
     * @var string[]
     */
    const ALLOWED_TAXONOMIES = array(
        'property_city',
        'property_category',
        'property_action_category',
        'property_area',
    );

    /**
     * Render category / city tablets.
     *
     * @param array<string,mixed> $atts Normalized attributes.
     * @return string Tablets HTML.
     */
    public static function render_term_tablets( $atts ) {
        $atts = self::normalize_tablets_atts( $atts );
        if ( ! taxonomy_exists( $atts['taxonomy'] ) ) {
            return '';
        }
        $terms = get_terms(
            array(
				'taxonomy'   => $atts['taxonomy'],
				'number'     => $atts['number'],
				'orderby'    => 'count',
				'order'      => 'DESC',
				'hide_empty' => $atts['hide_empty'],
            )
        );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }
        $html = '<div class="ssc-carousel-root ssc-tablets-root" data-ssc-carousel><div class="ssc-tablets ssc-carousel-track' . self::align_class( $atts ) . '">';
        foreach ( $terms as $term ) {
            $html .= self::render_tablet( $term );
        }
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Gutenberg alignment class for a block ("", " alignfull", " alignwide").
     *
     * Dynamic blocks must output this themselves — the editor does not
     * add it to server-rendered markup.
     *
     * @param array<string,mixed> $atts Block attributes.
     * @return string Alignment class fragment.
     */
    private static function align_class( $atts ) {
        if ( isset( $atts['align'] ) && in_array( $atts['align'], array( 'full', 'wide' ), true ) ) {
            return ' align' . $atts['align'];
        }
        return '';
    }

    /**
     * Normalize tablets attributes.
     *
     * @param array<string,mixed> $atts Raw attributes.
     * @return array{taxonomy:string,number:int,hide_empty:bool} Normalized attributes.
     */
    private static function normalize_tablets_atts( $atts ) {
        $taxonomy = isset( $atts['taxonomy'] ) ? sanitize_key( $atts['taxonomy'] ) : 'property_city';
        if ( ! in_array( $taxonomy, self::ALLOWED_TAXONOMIES, true ) ) {
            $taxonomy = 'property_city';
        }
        return array(
            'taxonomy'   => $taxonomy,
            'number'     => isset( $atts['number'] ) ? max( 1, min( 24, intval( $atts['number'] ) ) ) : 6,
            'hide_empty' => ! isset( $atts['hide_empty'] ) || (bool) $atts['hide_empty'],
        );
    }

    /**
     * Render a single term tablet with image, name and count.
     *
     * @param WP_Term $term Term to render.
     * @return string Tablet HTML.
     */
    private static function render_tablet( $term ) {
        $link = get_term_link( $term );
        if ( is_wp_error( $link ) ) {
            return '';
        }
        $image = self::get_term_image( $term->term_id );
        $style = $image !== '' ? ' style="background-image:url(' . esc_url( $image ) . ')"' : '';
        $html = '<a class="ssc-tablet' . ( $image !== '' ? '' : ' ssc-tablet-noimg' ) . '"' . $style . ' href="' . esc_url( $link ) . '">';
        $html .= '<span class="ssc-tablet-name">' . esc_html( $term->name ) . '</span>';
        $html .= '<span class="ssc-tablet-count">' . sprintf(
            /* translators: %d: listing count */
            esc_html( _n( '%d stay', '%d stays', intval( $term->count ), 'staysuite-companion' ) ),
            intval( $term->count )
        ) . '</span>';
        $html .= '</a>';
        return $html;
    }

    /**
     * Get a term's featured image URL (theme convention: taxonomy_$id option).
     *
     * @param int $term_id Term ID.
     * @return string Image URL or empty string.
     */
    private static function get_term_image( $term_id ) {
        $data = get_option( 'taxonomy_' . intval( $term_id ) );
        $attach_id = 0;
        if ( is_array( $data ) && isset( $data['category_attach_id'] ) ) {
            $attach_id = intval( $data['category_attach_id'] );
        }
        if ( $attach_id > 0 ) {
            $src = wp_get_attachment_image_src( $attach_id, 'medium' );
            if ( is_array( $src ) ) {
                return $src[0];
            }
        }
        if ( is_array( $data ) && ! empty( $data['category_featured_image'] ) ) {
            return esc_url_raw( $data['category_featured_image'] );
        }
        return '';
    }

    /**
     * Render a listing carousel row (rooms or hotels).
     *
     * @param array<string,mixed> $atts Normalized attributes.
     * @return string Carousel HTML.
     */
    public static function render_listing_carousel( $atts ) {
        $atts = self::normalize_carousel_atts( $atts );
        if ( $atts['source'] === 'hotels' ) {
            $items_html = self::render_hotel_items( $atts );
        } else {
            $items_html = self::render_room_items( $atts );
        }
        if ( $items_html === '' ) {
            return '';
        }
        $html = '<section class="ssc-row' . self::align_class( $atts ) . '">';
        if ( $atts['title'] !== '' ) {
            $html .= '<h2 class="ssc-row-title">' . esc_html( $atts['title'] ) . '</h2>';
        }
        $html .= '<div class="ssc-carousel-root" data-ssc-carousel><div class="ssc-carousel-track">' . $items_html . '</div></div>';
        $html .= '</section>';
        return $html;
    }

    /**
     * Normalize carousel attributes.
     *
     * @param array<string,mixed> $atts Raw attributes.
     * @return array{title:string,source:string,taxonomy:string,term:string,city:string,count:int,featured_only:bool,include_ids:int[],order:string} Normalized attributes.
     */
    private static function normalize_carousel_atts( $atts ) {
        $source = isset( $atts['source'] ) && $atts['source'] === 'hotels' ? 'hotels' : 'rooms';
        $taxonomy = isset( $atts['taxonomy'] ) ? sanitize_key( $atts['taxonomy'] ) : '';
        if ( $taxonomy !== '' && ! in_array( $taxonomy, self::ALLOWED_TAXONOMIES, true ) ) {
            $taxonomy = '';
        }
        $order = isset( $atts['order'] ) ? sanitize_key( $atts['order'] ) : 'featured';
        if ( ! in_array( $order, array( 'featured', 'price_asc', 'price_desc', 'newest', 'rand' ), true ) ) {
            $order = 'featured';
        }
        $include_ids = array();
        if ( ! empty( $atts['include_ids'] ) ) {
            $raw = is_array( $atts['include_ids'] ) ? implode( ',', $atts['include_ids'] ) : (string) $atts['include_ids'];
            foreach ( explode( ',', $raw ) as $id ) {
                $id = intval( trim( $id ) );
                if ( $id > 0 ) {
                    $include_ids[] = $id;
                }
            }
        }
        return array(
            'title'         => isset( $atts['title'] ) ? sanitize_text_field( $atts['title'] ) : '',
            'source'        => $source,
            'taxonomy'      => $taxonomy,
            'term'          => isset( $atts['term'] ) ? sanitize_title( $atts['term'] ) : '',
            'city'          => isset( $atts['city'] ) ? sanitize_title( $atts['city'] ) : '',
            'count'         => isset( $atts['count'] ) ? max( 1, min( 24, intval( $atts['count'] ) ) ) : 8,
            'featured_only' => ! empty( $atts['featured_only'] ),
            'include_ids'   => $include_ids,
            'order'         => $order,
        );
    }

    /**
     * Build the rooms query for a carousel.
     *
     * @param array<string,mixed> $atts Normalized carousel attributes.
     * @return WP_Query Rooms query.
     */
    // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.DB.SlowDBQuery.slow_db_query_meta_query, WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- The theme stores prices and the featured flag in post meta, so the block can only order and filter on them.
    private static function query_rooms( $atts ) {
        $args = array(
            'post_type'       => 'estate_property',
            'post_status'     => 'publish',
            'posts_per_page'  => $atts['count'],
            'no_found_rows'   => true,
            'ssc_room_lookup' => true,
        );
        if ( ! empty( $atts['include_ids'] ) ) {
            // Hand-picked posts override every filter and ordering.
            $args['post__in'] = $atts['include_ids'];
            $args['orderby'] = 'post__in';
            return new WP_Query( $args );
        }
        $tax_query = array();
        if ( $atts['taxonomy'] !== '' && $atts['term'] !== '' && taxonomy_exists( $atts['taxonomy'] ) ) {
            $tax_query[] = array(
                'taxonomy' => $atts['taxonomy'],
                'field'    => 'slug',
                'terms'    => array( $atts['term'] ),
            );
        }
        if ( $atts['city'] !== '' && taxonomy_exists( 'property_city' ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_city',
                'field'    => 'slug',
                'terms'    => array( $atts['city'] ),
            );
        }
        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }
        $meta_query = array();
        if ( $atts['featured_only'] ) {
            $meta_query[] = array(
				'key' => 'prop_featured',
				'value' => '1',
				'compare' => '=',
			);
        }
        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
        }
        switch ( $atts['order'] ) {
            case 'price_asc':
                $args['meta_key'] = 'property_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price_desc':
                $args['meta_key'] = 'property_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'newest':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            case 'rand':
                $args['orderby'] = 'rand';
                break;
            case 'featured':
            default:
                $args['meta_key'] = 'prop_featured';
                $args['orderby'] = 'meta_value_num date';
                $args['order'] = 'DESC';
                break;
        }
        return new WP_Query( $args );
    }

    /**
     * Render room cards for a carousel using the theme's card template.
     *
     * @param array<string,mixed> $atts Normalized carousel attributes.
     * @return string Cards HTML.
     */
    private static function render_room_items( $atts ) {
        $query = self::query_rooms( $atts );
        if ( ! $query->have_posts() ) {
            return '';
        }
        self::setup_card_context();
        $html = '';
        while ( $query->have_posts() ) {
            $query->the_post();
            $html .= self::render_room_card( get_the_ID() );
        }
        wp_reset_postdata();
        return $html;
    }

    /**
     * Set the globals the theme card template expects.
     *
     * Mirrors property_list.php / normal_map_core.php.
     *
     * @return void
     */
    // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.DB.SlowDBQuery.slow_db_query_meta_query, WordPress.DB.SlowDBQuery.slow_db_query_tax_query

    public static function setup_card_context() {
        global $wpestate_curent_fav, $wpestate_currency, $wpestate_where_currency,
                $wpestate_listing_type, $wpestate_property_unit_slider,
                $wpestate_options, $show_compare, $schema_flag;

        $current_user = wp_get_current_user();
        $wpestate_currency = function_exists( 'wprentals_get_option' ) ? esc_html( wprentals_get_option( 'wp_estate_currency_label_main', '' ) ) : '';
        $wpestate_where_currency = function_exists( 'wprentals_get_option' ) ? esc_html( wprentals_get_option( 'wp_estate_where_currency_symbol', '' ) ) : '';
        $wpestate_curent_fav = get_option( 'favorites' . $current_user->ID );
        $wpestate_listing_type = function_exists( 'wprentals_get_option' ) ? wprentals_get_option( 'wp_estate_listing_unit_type', '' ) : '';
        $wpestate_property_unit_slider = function_exists( 'wprentals_get_option' ) ? esc_html( wprentals_get_option( 'wp_estate_prop_list_slider', '' ) ) : '';
        $wpestate_options = array();
        $show_compare = 0;
        $schema_flag = 0;
    }

    /**
     * Render one room card through the theme template.
     *
     * @param int $room_id Room post ID.
     * @return string Card HTML.
     */
    public static function render_room_card( $room_id ) {
        global $post;
        $room = get_post( intval( $room_id ) );
        if ( ! $room instanceof WP_Post || $room->post_type !== 'estate_property' ) {
            return '';
        }
        $previous = $post;
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The theme template requires the room to be the current post; restored below.
        $post = $room;
        setup_postdata( $post );
        $html = '';
        $card = locate_template( 'templates/property_unit.php' );
        if ( $card !== '' ) {
            ob_start();
            include $card;
            $html = (string) ob_get_clean();
            $html = self::append_booking_context( $html, $room->ID );
        }
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the global saved before the swap.
        $post = $previous;
        wp_reset_postdata();
        return $html;
    }

    /**
     * Build the hotels query for a carousel.
     *
     * @param array<string,mixed> $atts Normalized carousel attributes.
     * @return WP_Query Hotels query.
     */
    // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Hotel meta is plugin-owned post meta, matched on an indexed key.
    private static function query_hotels( $atts ) {
        $args = array(
            'post_type'      => HotelCPT::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $atts['count'],
            'no_found_rows'  => true,
        );
        if ( ! empty( $atts['include_ids'] ) ) {
            // Hand-picked posts override every filter and ordering.
            $args['post__in'] = $atts['include_ids'];
            $args['orderby'] = 'post__in';
            return new WP_Query( $args );
        }
        if ( $atts['order'] === 'rand' ) {
            $args['orderby'] = 'rand';
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Hotel taxonomy filter on a bounded carousel query.
        $tax_query = array();
        if ( $atts['taxonomy'] !== '' && $atts['term'] !== '' && taxonomy_exists( $atts['taxonomy'] ) ) {
            $tax_query[] = array(
                'taxonomy' => $atts['taxonomy'],
                'field'    => 'slug',
                'terms'    => array( $atts['term'] ),
            );
        }
        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        $meta_query = array();
        if ( $atts['featured_only'] ) {
            $meta_query[] = array(
				'key' => '_ssc_featured',
				'value' => '1',
				'compare' => '=',
			);
        }
        if ( $atts['city'] !== '' ) {
            $meta_query[] = array(
				'key' => '_ssc_city',
				'value' => $atts['city'],
				'compare' => '=',
			);
        }
        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
        }
        return new WP_Query( $args );
    }

    /**
     * Render hotel cards for a carousel.
     *
     * @param array<string,mixed> $atts Normalized carousel attributes.
     * @return string Cards HTML.
     */
    // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.DB.SlowDBQuery.slow_db_query_meta_query

    private static function render_hotel_items( $atts ) {
        $query = self::query_hotels( $atts );
        if ( ! $query->have_posts() ) {
            return '';
        }
        self::setup_card_context();
        $html = '';
        while ( $query->have_posts() ) {
            $query->the_post();
            $html .= self::render_hotel_card( get_the_ID() );
        }
        wp_reset_postdata();
        return $html;
    }

    /**
     * Render one hotel card through the theme template.
     *
     * Same property-unit markup the advanced search page uses, so hotel
     * cards match listing cards everywhere (homepage rows, search pages).
     * Injects the rooms + area meta line the theme leaves empty for hotels.
     *
     * Callers outside the theme loop must run setup_card_context() first;
     * render_hotel_items() already does, search-ssc-hotels.php does too.
     *
     * @param int  $hotel_id Hotel post ID.
     * @param bool $wide     Use the wide unit variant (half-map style).
     * @return string Card HTML.
     */
    public static function render_hotel_card( $hotel_id, $wide = false ) {
        // The theme unit template reads these globals directly; importing
        // them keeps the include working outside the theme loop, where the
        // include would otherwise see an empty scope.
        global $post, $prop_selection, $schema_flag;
        $hotel = get_post( intval( $hotel_id ) );
        if ( ! $hotel instanceof WP_Post || $hotel->post_type !== HotelCPT::POST_TYPE ) {
            return '';
        }
        $previous = $post;
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The theme template requires the hotel to be the current post; restored below.
        $post = $hotel;
        setup_postdata( $post );
        $html = '';
        $card = locate_template( $wide ? 'templates/property_unit_wide.php' : 'templates/property_unit.php' );
        if ( $card !== '' ) {
            ob_start();
            include $card;
            $html = (string) ob_get_clean();
            $html = self::inject_hotel_search_meta( $html, $hotel->ID );
            $html = self::inject_hotel_price_prefix( $html, $hotel->ID );
            $html = self::inject_hotel_original_price( $html, $hotel->ID );
            $html = self::append_search_context( $html, $hotel->ID );
        }
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the global saved before the swap.
        $post = $previous;
        wp_reset_postdata();
        return $html;
    }

    /**
     * Hotel meta line for theme listing cards ("4 rooms · Kolatoli").
     *
     * The theme's property-unit card prints guests/bedrooms from room meta
     * the hotel post does not carry, leaving that line empty. This builds
     * the hotel equivalent from linked rooms so the card still answers
     * "how many rooms, which area".
     *
     * @param int $hotel_id Hotel post ID.
     * @return string Meta line, empty when there is nothing to show.
     */
    public static function hotel_search_meta( $hotel_id ) {
        $hotel_id = intval( $hotel_id );
        $parts    = array();
        $rooms    = Repository::get_room_count( $hotel_id );
        if ( $rooms > 0 ) {
            $parts[] = sprintf(
                /* translators: %d: number of rooms */
                esc_html( _n( '%d room', '%d rooms', $rooms, 'staysuite-companion' ) ),
                intval( $rooms )
            );
        }
        if ( taxonomy_exists( 'property_area' ) ) {
            $areas = get_the_terms( $hotel_id, 'property_area' );
            if ( is_array( $areas ) && $areas !== array() ) {
                usort(
                    $areas,
                    function ( $a, $b ) {
                        return strcmp( $a->name, $b->name );
                    }
                );
                $parts[] = $areas[0]->name;
            }
        }
        return implode( ' · ', $parts );
    }

    /**
     * Inject the hotel meta line into a theme property-unit card.
     *
     * Targets the card's category/actions line so the rooms + area read as
     * part of the card body. Falls back to the untouched card when the
     * anchor is missing or there is no meta to show.
     *
     * @param string $card_html Theme card HTML.
     * @param int    $hotel_id  Hotel post ID.
     * @return string Card HTML with the meta line injected.
     */
    public static function inject_hotel_search_meta( $card_html, $hotel_id ) {
        $line = self::hotel_search_meta( $hotel_id );
        if ( $line !== '' ) {
            $meta_div  = '<div class="category_tagline ssc-hotel-meta">' . esc_html( $line ) . '</div>';
            $injected  = preg_replace(
                '#(<div class="category_tagline actions_icon">.*?</div>)#s',
                '$1' . $meta_div,
                $card_html,
                1
            );
            if ( is_string( $injected ) ) {
                $card_html = $injected;
            }
        }
        // Hotels have no room-type taxonomy of their own; the synced
        // category/action terms only exist to feed search, so the
        // "Apartment · Entire home" line would mislead — drop it.
        $stripped = preg_replace(
            '#<div class="category_tagline actions_icon">.*?</div>#s',
            '',
            $card_html,
            1
        );
        return is_string( $stripped ) ? $stripped : $card_html;
    }

    /**
     * Prefix a hotel card's price with "From".
     *
     * The synced property_price is the lowest room price, so the card
     * price is a starting price. The theme prints the price twice per
     * card (slider overlay and body), so every price div gets the prefix —
     * whichever one the card style shows reads "From …". Skipped when the
     * hotel has no price, mirroring the theme hiding the per-night label
     * at zero.
     *
     * @param string $card_html Theme card HTML.
     * @param int    $hotel_id  Hotel post ID.
     * @return string Card HTML with the price prefix injected.
     */
    public static function inject_hotel_price_prefix( $card_html, $hotel_id ) {
        $price = floatval( get_post_meta( intval( $hotel_id ), 'property_price', true ) );
        if ( $price <= 0 ) {
            return $card_html;
        }
        $from = '<span class="ssc-price-from">' . esc_html__( 'From', 'staysuite-companion' ) . '</span> ';
        $prefixed = preg_replace(
            '#(<div class="price_unit">)#',
            '$1' . $from,
            $card_html
        );
        return is_string( $prefixed ) ? $prefixed : $card_html;
    }

    /**
     * Show the cheapest room's pre-discount price struck through on hotel cards.
     *
     * Mirrors the room rows on the hotel page: when the room behind the
     * synced min price carries an original (pre-discount) price, it renders
     * struck through ahead of the "From" price. Skipped when there is no
     * discount to show.
     *
     * @param string $card_html Theme card HTML.
     * @param int    $hotel_id  Hotel post ID.
     * @return string Card HTML with the original price injected.
     */
    public static function inject_hotel_original_price( $card_html, $hotel_id ) {
        $rooms = Repository::get_rooms( intval( $hotel_id ), 1 );
        if ( ! is_array( $rooms->posts ) || $rooms->posts === array() || ! $rooms->posts[0] instanceof WP_Post ) {
            return $card_html;
        }
        $room_id = $rooms->posts[0]->ID;
        $price = floatval( get_post_meta( $room_id, 'property_price', true ) );
        $original = Repository::get_original_price( $room_id );
        if ( $price <= 0 || $original <= $price ) {
            return $card_html;
        }
        // format_price() escapes its own output (symbol via esc_html).
        $was = '<span class="ssc-price-was">' . Repository::format_price( $original ) . '</span> '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the formatter, see above.
        $with_was = preg_replace(
            '#(<div class="price_unit">)#',
            '$1' . $was,
            $card_html
        );
        return is_string( $with_was ) ? $with_was : $card_html;
    }

    /**
     * Read the visitor's current search filters from the request.
     *
     * Normalizes both guest spellings the plugin and theme use.
     *
     * @return array{check_in:string,check_out:string,guests:string} Filters, empty when absent.
     */
    private static function current_search_params() {
        $raw = array();
        foreach ( array( 'check_in', 'check_out', 'guest_no', 'guests' ) as $key ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only carry-forward of the visitor's own search filters.
            $raw[ $key ] = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
        }
        $guests = $raw['guest_no'] !== '' ? $raw['guest_no'] : $raw['guests'];
        return array(
            'check_in'  => $raw['check_in'],
            'check_out' => $raw['check_out'],
            'guests'    => is_numeric( $guests ) ? (string) max( 1, intval( $guests ) ) : '',
        );
    }

    /**
     * Append args to every link pointing at a post's permalink in card HTML.
     *
     * @param string              $card_html Card HTML.
     * @param int                 $post_id   Post the links point at.
     * @param array<string,string> $args     Query args to append.
     * @return string Card HTML with contextual links.
     */
    private static function append_link_args( $card_html, $post_id, $args ) {
        $link = get_permalink( intval( $post_id ) );
        if ( ! is_string( $link ) || $link === '' ) {
            return $card_html;
        }
        return str_replace( $link, add_query_arg( $args, $link ), $card_html );
    }

    /**
     * Carry the current search filters onto hotel card links.
     *
     * The hotel page applies dates/guests from these params, so cards
     * rendered on a search surface keep the visitor's context instead of
     * dropping it at the hotel door.
     *
     * @param string $card_html Theme card HTML.
     * @param int    $hotel_id  Hotel post ID.
     * @return string Card HTML with contextual hotel links.
     */
    public static function append_search_context( $card_html, $hotel_id ) {
        $params = self::current_search_params();
        $args = array_filter(
            array(
                'check_in'  => $params['check_in'],
                'check_out' => $params['check_out'],
                'guest_no'  => $params['guests'],
                'guests'    => $params['guests'],
            )
        );
        if ( $args === array() ) {
            return $card_html;
        }
        return self::append_link_args( $card_html, $hotel_id, $args );
    }

    /**
     * Carry the current search filters onto room card links.
     *
     * Room pages are theme templates whose booking form only honors the
     * check_in_prop/check_out_prop/guest_no_prop params, in display date
     * format — so the context is translated, not just forwarded.
     *
     * @param string $card_html Theme card HTML.
     * @param int    $room_id   Room post ID.
     * @return string Card HTML with contextual room links.
     */
    public static function append_booking_context( $card_html, $room_id ) {
        $params = self::current_search_params();
        $args = array_filter(
            array(
                'check_in_prop'  => Repository::to_display_date( $params['check_in'] ),
                'check_out_prop' => Repository::to_display_date( $params['check_out'] ),
                'guest_no_prop'  => $params['guests'],
            )
        );
        if ( $args === array() ) {
            return $card_html;
        }
        return self::append_link_args( $card_html, $room_id, $args );
    }

    /**
     * Render the hero with cover image and search.
     *
     * Search modes: theme (the theme's own search form, so the
     * Individual/Group pill and all theme behavior apply), simple (the
     * plugin's plain GET form), or none.
     *
     * The cover falls back to the page's featured image when the block has no
     * image_id. Content is copied between databases (dev to staging, demo to
     * production) and attachment IDs do not travel with it, so a stored
     * image_id routinely points at nothing on the target site. The featured
     * image resolves in both worlds.
     *
     * @param array<string,mixed> $atts Normalized attributes. May carry post_id
     *                                 for the preview, where there is no query.
     * @return string Hero HTML.
     */
    public static function render_hero( $atts ) {
        $title = isset( $atts['title'] ) ? sanitize_text_field( $atts['title'] ) : '';
        $subtitle = isset( $atts['subtitle'] ) ? sanitize_text_field( $atts['subtitle'] ) : '';
        $image_id = isset( $atts['image_id'] ) ? intval( $atts['image_id'] ) : 0;
        $mode = isset( $atts['search_mode'] ) ? sanitize_key( $atts['search_mode'] ) : 'theme';
        if ( ! in_array( $mode, array( 'theme', 'simple', 'none' ), true ) ) {
            $mode = 'theme';
        }
        if ( isset( $atts['show_search'] ) && ! (bool) $atts['show_search'] ) {
            $mode = 'none';
        }
        $align = self::align_class( $atts );

        if ( $image_id <= 0 ) {
            $image_id = self::hero_fallback_image( $atts );
        }

        $style = '';
        if ( $image_id > 0 ) {
            $src = wp_get_attachment_image_src( $image_id, 'full' );
            if ( is_array( $src ) ) {
                $style = ' style="background-image:url(' . esc_url( $src[0] ) . ')"';
            }
        }
        $html = '<section class="ssc-hero' . $align . '"' . $style . '><div class="ssc-hero-inner">';
        if ( $title !== '' ) {
            $html .= '<h1 class="ssc-hero-title">' . esc_html( $title ) . '</h1>';
        }
        if ( $subtitle !== '' ) {
            $html .= '<p class="ssc-hero-subtitle">' . esc_html( $subtitle ) . '</p>';
        }
        if ( $mode === 'theme' ) {
            $html .= self::render_theme_search();
        } elseif ( $mode === 'simple' ) {
            $html .= self::render_search_form();
        }
        $html .= '</div></section>';
        return $html;
    }

    /**
     * Resolve the cover image used when the block has no image_id.
     *
     * Prefers the post passed in by the editor preview, then the queried
     * object. Returns 0 when the post has no featured image, which leaves the
     * cover background-color in place rather than breaking.
     *
     * @param array<string,mixed> $atts Hero attributes, may carry post_id.
     * @return int Attachment ID, or 0 when there is nothing to fall back to.
     */
    private static function hero_fallback_image( $atts ) {
        $post_id = isset( $atts['post_id'] ) ? intval( $atts['post_id'] ) : 0;

        if ( $post_id <= 0 ) {
            $queried = get_queried_object_id();
            if ( $queried > 0 && is_singular() ) {
                $post_id = $queried;
            }
        }

        if ( $post_id <= 0 || ! function_exists( 'has_post_thumbnail' ) ) {
            return 0;
        }

        $thumbnail_id = (int) get_post_thumbnail_id( $post_id );
        return $thumbnail_id > 0 ? $thumbnail_id : 0;
    }

    /**
     * Render the theme's own search form inside the hero.
     *
     * Prefers the Elementor Search Form Builder widget output — pixel for
     * pixel the default search bar, responsive included. Falls back to the
     * classic shortcode form, then the simple form.
     *
     * @return string Search form HTML.
     */
    private static function render_theme_search() {
        try {
            $elementor = self::render_elementor_search();
        } catch ( \Throwable $e ) {
            $elementor = '';
        }
        if ( $elementor !== '' ) {
            return $elementor;
        }
        global $search_object;
        if ( ( ! isset( $search_object ) || ! is_object( $search_object ) ) && class_exists( 'WpRentalsSearch' ) ) {
            $search_object = new \WpRentalsSearch();
        }
        if ( isset( $search_object ) && is_object( $search_object ) && method_exists( $search_object, 'wpstate_display_search_form' ) ) {
            return (string) $search_object->wpstate_display_search_form( 'shortcode' );
        }
        return self::render_search_form();
    }

    /**
     * Render the Elementor Search Form Builder widget with preset fields.
     *
     * Instantiates the theme's widget outside the editor with Where /
     * Check In / Check Out / Guests settings, so the hero shows the exact
     * default search bar.
     *
     * @return string Widget HTML or empty string when unavailable.
     */
    private static function render_elementor_search() {
        $html = self::render_search_widget( self::elementor_search_settings() );
        if ( strpos( $html, '<form' ) === false ) {
            return '';
        }
        return '<div class="ssc-elementor-search" data-ssc-search>' . $html . '</div>';
    }

    /**
     * Render the hotel search bar: theme widget, no location field.
     *
     * Check In / Check Out / Guests plus the theme's own submit button,
     * posting back to the hotel page so dates filter the room list.
     *
     * @param string $hotel_url Hotel permalink (form action override).
     * @return string Widget HTML or empty string when unavailable.
     */
    public static function render_hotel_search( $hotel_url ) {
        $html = self::render_search_widget( self::hotel_search_settings() );
        if ( strpos( $html, '<form' ) === false ) {
            return '';
        }
        $html = preg_replace( '/action="[^"]*"/', 'action="' . esc_url( $hotel_url ) . '"', $html, 1 );
        return '<div class="ssc-hotel-search" data-ssc-search>' . $html . '</div>';
    }

    /**
     * Instantiate the Search Form Builder widget with given settings.
     *
     * @param array<string,mixed> $settings Widget settings.
     * @return string Widget HTML or empty string when unavailable.
     */
    private static function render_search_widget( $settings ) {
        $widget_class = 'ElementorWpRentals\Widgets\Wprentals_Search_Form_Builder';
        if ( ! class_exists( $widget_class ) ) {
            $widget_file = trailingslashit( defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins' )
                . 'wprentals-elementor/widgets/search_form_builder.php';
            if ( is_readable( $widget_file ) ) {
                require_once $widget_file;
            }
        }
        if ( ! class_exists( $widget_class ) || ! class_exists( 'Elementor\Plugin' ) || ! method_exists( $widget_class, 'print_element' ) ) {
            return '';
        }
        $data = array(
            'id'         => 'ssc-search-' . substr( md5( wp_json_encode( $settings ) ), 0, 8 ),
            'elType'     => 'widget',
            'widgetType' => 'Wprentals_Search_Form_Builder',
            'settings'   => $settings,
        );
        global $post;
        $post_backup = $post;
        if ( ! $post instanceof \WP_Post ) {
            $fallback_id = intval( get_option( 'page_on_front' ) );
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Elementor needs a current post; restored in the finally block.
            $post = ( $fallback_id > 0 && get_post_status( $fallback_id ) ) ? get_post( $fallback_id ) : null;
            if ( ! $post instanceof \WP_Post ) {
                return '';
            }
        }
        try {
            $element = null;
            if ( did_action( 'elementor/widgets/register' ) ) {
                $manager = \Elementor\Plugin::instance()->elements_manager;
                if ( $manager && method_exists( $manager, 'create_element_instance' ) ) {
                    $element = $manager->create_element_instance( $data );
                }
            }
            if ( ! $element ) {
                $element = new $widget_class( $data, array() );
            }
            ob_start();
            $element->print_element();
            $html = (string) ob_get_clean();
        } finally {
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the global saved before the swap.
            $post = $post_backup;
        }
        if ( strpos( $html, '<form' ) === false ) {
            return '';
        }
        return '<div class="ssc-elementor-search" data-ssc-search>' . $html . '</div>';
    }

    /**
     * Bundled search-bar icon in Elementor media-control shape.
     *
     * The widget renders these through Icons_Manager, which falls back to
     * an <img> when the attachment id does not resolve — so local files
     * work with id 0 and no hotlinking or foreign media ids are needed.
     *
     * @param string $file Icon file under assets/images/icons/.
     * @return array<string,mixed> Elementor icon setting.
     */
    private static function search_icon( $file ) {
        // Icons_Manager cannot resolve our local svg id=0 attachment; use
        // inline Font Awesome glyphs instead so the UI elements render.
        $fa = array(
            'location.svg' => 'fas fa-map-marker-alt',
            'calendar.svg' => 'fas fa-calendar-alt',
            'user.svg'     => 'fas fa-user',
            'search.svg'   => 'fas fa-search',
        );
        return array(
            'value'   => isset( $fa[ $file ] ) ? $fa[ $file ] : 'fas fa-search',
            'library' => 'fa-solid',
        );
    }

    /**
     * Preset widget settings for the hero search bar.
     *
     * Mirrors the reference Search Form Builder instance (pill radius 58,
     * coral submit, hairline dividers, SVG icons), since Elementor only
     * compiles style-tab CSS for editor-placed widgets.
     *
     * @return array<string,mixed> Widget settings.
     */
    private static function elementor_search_settings() {
        $location = self::search_icon( 'location.svg' );
        $calendar = self::search_icon( 'calendar.svg' );
        $user     = self::search_icon( 'user.svg' );
        $settings = array(
            'form_fields' => array(
                array(
                    '_id'         => 'ssc_where',
                    'field_type'  => 'Location',
                    'field_how'   => 'like',
                    'field_label' => esc_html__( 'Location', 'staysuite-companion' ),
                    'placeholder' => esc_html__( 'Where are you going', 'staysuite-companion' ),
                    'width'       => '30',
                    'icon'        => $location,
                ),
                array(
                    '_id'         => 'ssc_in',
                    'field_type'  => 'check_in',
                    'field_how'   => 'date bigger',
                    'field_label' => esc_html__( 'Check In', 'staysuite-companion' ),
                    'placeholder' => esc_html__( 'Check In', 'staysuite-companion' ),
                    'width'       => '20',
                    'icon'        => $calendar,
                ),
                array(
                    '_id'         => 'ssc_out',
                    'field_type'  => 'check_out',
                    'field_how'   => 'date smaller',
                    'field_label' => esc_html__( 'Check Out', 'staysuite-companion' ),
                    'placeholder' => esc_html__( 'Check Out', 'staysuite-companion' ),
                    'width'       => '20',
                    'icon'        => $calendar,
                ),
                array(
                    '_id'         => 'ssc_guests',
                    'field_type'  => 'guest_no',
                    'field_how'   => 'greater',
                    'field_label' => esc_html__( 'Guests', 'staysuite-companion' ),
                    'placeholder' => esc_html__( 'Add Guests', 'staysuite-companion' ),
                    'width'       => '20',
                    'icon'        => $user,
                ),
            ),
            'form_field_show_labels'        => '',
            'form_field_show_section_title' => '',
            'form_field_section_title_text' => 'Advanced Search',
            'form_field_show_exra_details'  => '',
            'submit_button_text'            => '',
            'submit_button_width'           => '10',
            'search_icon_button'            => self::search_icon( 'search.svg' ),
        );
        /**
         * Filter hero search widget settings.
         *
         * @param array<string,mixed> $settings Widget settings.
         */
        return apply_filters( 'ssc_hero_search_settings', $settings );
    }

    /**
     * Preset widget settings for the hotel search bar.
     *
     * Theme-native Check In / Check Out / Guests fields with the theme's
     * own submit button — no location field. Style-tab CSS is supplied by
     * the plugin stylesheet (see .ssc-hotel-search).
     *
     * @return array<string,mixed> Widget settings.
     */
    private static function hotel_search_settings() {
        $calendar = self::search_icon( 'calendar.svg' );
        $user     = self::search_icon( 'user.svg' );
        $settings = array(
            'form_fields' => array(
                array(
                    '_id'         => 'ssc_hotel_in',
                    'field_type'  => 'check_in',
                    'field_how'   => 'date bigger',
                    'field_label' => esc_html__( 'Check In', 'staysuite-companion' ),
                    'placeholder' => esc_html__( 'Check In', 'staysuite-companion' ),
                    'width'       => '30',
                    'icon'        => $calendar,
                ),
                array(
                    '_id'         => 'ssc_hotel_out',
                    'field_type'  => 'check_out',
                    'field_how'   => 'date smaller',
                    'field_label' => esc_html__( 'Check Out', 'staysuite-companion' ),
                    'placeholder' => esc_html__( 'Check Out', 'staysuite-companion' ),
                    'width'       => '30',
                    'icon'        => $calendar,
                ),
                array(
                    '_id'         => 'ssc_hotel_guests',
                    'field_type'  => 'guest_no',
                    'field_how'   => 'greater',
                    'field_label' => esc_html__( 'Guests', 'staysuite-companion' ),
                    'placeholder' => esc_html__( 'Add Guests', 'staysuite-companion' ),
                    'width'       => '25',
                    'icon'        => $user,
                ),
            ),
            'form_field_show_labels'        => '',
            'form_field_show_section_title' => '',
            'form_field_section_title_text' => 'Advanced Search',
            'form_field_show_exra_details'  => '',
            'submit_button_text'            => esc_html__( 'Search', 'staysuite-companion' ),
            'submit_button_width'           => '15',
        );
        /**
         * Filter hotel search widget settings (Pro: extra fields, widths).
         *
         * @param array<string,mixed> $settings Widget settings.
         */
        return apply_filters( 'ssc_hotel_search_settings', $settings );
    }

    /**
     * Render the hero search form.
     *
     * @return string Form HTML, empty when no results page exists.
     */
    private static function render_search_form() {
        $action = function_exists( 'wpestate_get_template_link' )
            ? wpestate_get_template_link( 'advanced_search_results.php' )
            : home_url( '/' );
        if ( $action === '' ) {
            return '';
        }
        $html = '<form class="ssc-search" role="search" method="get" action="' . esc_url( $action ) . '">';
        $html .= '<label class="ssc-search-field"><span>' . esc_html__( 'Where are you going?', 'staysuite-companion' ) . '</span>'
            . '<input type="text" name="search_location" autocomplete="off" placeholder="' . esc_attr__( 'Search destination, hotel, area…', 'staysuite-companion' ) . '"></label>';
        $html .= '<input type="hidden" name="stype" value="tax">';
        $html .= '<label class="ssc-search-field"><span>' . esc_html__( 'Check in', 'staysuite-companion' ) . '</span>'
            . '<input type="text" name="check_in" placeholder="YYYY-MM-DD"></label>';
        $html .= '<label class="ssc-search-field"><span>' . esc_html__( 'Check out', 'staysuite-companion' ) . '</span>'
            . '<input type="text" name="check_out" placeholder="YYYY-MM-DD"></label>';
        $html .= '<label class="ssc-search-field"><span>' . esc_html__( 'Guests', 'staysuite-companion' ) . '</span>'
            . '<input type="number" name="guest_no" min="1" value="2"></label>';
        $html .= wp_nonce_field( 'wpestate_regular_search', 'wpestate_regular_search_nonce', true, false );
        $html .= '<button type="submit" class="ssc-search-submit">' . esc_html__( 'Search', 'staysuite-companion' ) . '</button>';
        $html .= '</form>';
        return $html;
    }

    /**
     * Render the payment strip (label + banner image).
     *
     * @param array<string,mixed> $atts Normalized attributes.
     * @return string Strip HTML.
     */
    public static function render_payment_strip( $atts ) {
        $title = isset( $atts['title'] ) ? sanitize_text_field( $atts['title'] ) : '';
        $image_id = isset( $atts['image_id'] ) ? intval( $atts['image_id'] ) : 0;
        $html = '<section class="ssc-payments">';
        if ( $title !== '' ) {
            $html .= '<span class="ssc-pay-title">' . esc_html( $title ) . '</span>';
        }
        if ( $image_id > 0 ) {
            $img = wp_get_attachment_image( $image_id, 'full' );
            if ( $img !== '' ) {
                $html .= '<div class="ssc-pay-banner">' . $img . '</div>';
            }
        }
        $html .= '</section>';
        return $html;
    }
}
