<?php
/**
 * Room <-> hotel read queries.
 *
 * @package StaySuite\Companion\Hotel
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Hotel;

use WP_Query;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Central place for hotel/room lookups used by templates and AJAX.
 */
class Repository {

    /**
     * Meta key linking a room (estate_property) to its hotel.
     *
     * @var string
     */
    const ROOM_HOTEL_META = '_ssc_hotel_id';

    /**
     * Meta key for the room's pre-discount price (display only).
     *
     * The theme's property_price stays the booking price; this higher
     * value renders struck through on hotel cards.
     *
     * @var string
     */
    const ROOM_ORIGINAL_PRICE_META = '_ssc_original_price';

    /**
     * Get the hotel ID linked to a room.
     *
     * @param int $room_id Room post ID.
     * @return int Hotel post ID, 0 when unassigned.
     */
    public static function get_room_hotel_id( $room_id ) {
        return intval( get_post_meta( intval( $room_id ), self::ROOM_HOTEL_META, true ) );
    }

    /**
     * Get the room's pre-discount display price.
     *
     * @param int $room_id Room post ID.
     * @return float Original price, 0 when unset.
     */
    public static function get_original_price( $room_id ) {
        return floatval( get_post_meta( intval( $room_id ), self::ROOM_ORIGINAL_PRICE_META, true ) );
    }

    /**
     * Get published rooms belonging to a hotel, cheapest first.
     *
     * @param int $hotel_id Hotel post ID.
     * @param int $limit    Maximum rooms to return.
     * @return WP_Query Rooms query.
     */
    public static function get_rooms( $hotel_id, $limit = 100 ) {
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded, indexed lookup of the rooms belonging to one hotel.
        return new WP_Query(
            array(
				'post_type'       => 'estate_property',
				'post_status'     => 'publish',
				'posts_per_page'  => intval( $limit ),
				'ssc_room_lookup' => true,
				// Named clauses: a bare orderby=meta_value_num would sort by
				// the hotel-link clause (constant per hotel), not the price.
				'meta_query'      => array(
					'relation'     => 'AND',
					'hotel_clause' => array(
						'key'     => self::ROOM_HOTEL_META,
						'value'   => intval( $hotel_id ),
						'compare' => '=',
					),
					'price_clause' => array(
						'key'     => 'property_price',
						'type'    => 'NUMERIC',
						'compare' => 'EXISTS',
					),
				),
				'orderby'         => array(
					'price_clause' => 'ASC',
				),
				'no_found_rows'   => true,
            )
        );
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
    }

    /**
     * Count published rooms in a hotel.
     *
     * @param int $hotel_id Hotel post ID.
     * @return int Room count.
     */
    public static function get_room_count( $hotel_id ) {
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- COUNT-only query on an indexed meta key.
        $query = new WP_Query(
            array(
				'post_type'       => 'estate_property',
				'post_status'     => 'publish',
				'posts_per_page'  => 1,
				'ssc_room_lookup' => true,
				'meta_key'       => self::ROOM_HOTEL_META,
				'meta_value'     => intval( $hotel_id ),
				'meta_compare'   => '=',
				'fields'         => 'ids',
				'no_found_rows'  => false,
            )
        );
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        return intval( $query->found_posts );
    }

    /**
     * Get the lowest nightly price across a hotel's rooms.
     *
     * @param int $hotel_id Hotel post ID.
     * @return float Minimum price, 0 when no room is priced.
     */
    public static function get_min_price( $hotel_id ) {
        global $wpdb;
        $min = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MIN(CAST(pm2.meta_value AS DECIMAL(12,2))) FROM {$wpdb->postmeta} pm1
             INNER JOIN {$wpdb->posts} p ON p.ID = pm1.post_id
             INNER JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = pm1.post_id AND pm2.meta_key = 'property_price'
             WHERE pm1.meta_key = %s AND pm1.meta_value = %s
             AND p.post_type = 'estate_property' AND p.post_status = 'publish'",
                self::ROOM_HOTEL_META,
                (string) intval( $hotel_id )
            )
        );
        return floatval( $min );
    }

    /**
     * Get the city term (property_city) stored on a hotel.
     *
     * @param int $hotel_id Hotel post ID.
     * @return WP_Term|null City term or null when missing.
     */
    public static function get_city_term( $hotel_id ) {
        if ( ! taxonomy_exists( 'property_city' ) ) {
            return null;
        }
        $slug = sanitize_title( get_post_meta( intval( $hotel_id ), '_ssc_city', true ) );
        if ( $slug === '' ) {
            return null;
        }
        $term = get_term_by( 'slug', $slug, 'property_city' );
        return ( $term instanceof WP_Term ) ? $term : null;
    }

    /**
     * Get IDs and titles of all published hotels, ordered by title.
     *
     * @return array<int, string> Hotel ID => title map.
     */
    public static function get_all_hotels() {
        $ids = get_posts(
            array(
				'post_type'      => HotelCPT::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
            )
        );
        $map = array();
        foreach ( $ids as $hotel_id ) {
            $map[ intval( $hotel_id ) ] = get_the_title( $hotel_id );
        }
        return $map;
    }

    /**
     * Suggest a hotel when the room title starts with the hotel title.
     *
     * Longest matching prefix wins.
     *
     * @param string          $room_title      Room title.
     * @param array<int,string> $hotels_by_title Hotel ID => title map.
     * @return int Suggested hotel ID, 0 when nothing matches.
     */
    public static function suggest_hotel( $room_title, $hotels_by_title ) {
        $room_title = mb_strtolower( trim( $room_title ) );
        $best = 0;
        $best_len = 0;
        foreach ( $hotels_by_title as $hotel_id => $hotel_title ) {
            $hotel_title = mb_strtolower( trim( $hotel_title ) );
            if ( $hotel_title === '' || mb_strpos( $room_title, $hotel_title ) !== 0 ) {
                continue;
            }
            if ( mb_strlen( $hotel_title ) > $best_len ) {
                $best = intval( $hotel_id );
                $best_len = mb_strlen( $hotel_title );
            }
        }
        return $best;
    }

    /**
     * Get gallery attachment IDs: hotel featured image first, then rooms'.
     *
     * @param int $hotel_id Hotel post ID.
     * @param int $limit    Maximum images.
     * @return int[] Attachment IDs.
     */
    public static function get_gallery( $hotel_id, $limit = 5 ) {
        $ids = array();
        $hotel_thumb = intval( get_post_thumbnail_id( intval( $hotel_id ) ) );
        if ( $hotel_thumb > 0 ) {
            $ids[] = $hotel_thumb;
        }
        $rooms = self::get_rooms( $hotel_id, 100 );
        foreach ( $rooms->posts as $room ) {
            $thumb = intval( get_post_thumbnail_id( $room->ID ) );
            if ( $thumb > 0 && ! in_array( $thumb, $ids, true ) ) {
                $ids[] = $thumb;
            }
            if ( count( $ids ) >= intval( $limit ) ) {
                break;
            }
        }
        return array_slice( $ids, 0, intval( $limit ) );
    }

    /**
     * Get the union of all rooms' property_features terms.
     *
     * @param int $hotel_id Hotel post ID.
     * @return WP_Term[] Unique feature terms, ordered by name.
     */
    public static function get_facilities( $hotel_id ) {
        if ( ! taxonomy_exists( 'property_features' ) ) {
            return array();
        }
        $rooms = self::get_rooms( $hotel_id, 100 );
        $by_id = array();
        foreach ( $rooms->posts as $room ) {
            $terms = get_the_terms( $room->ID, 'property_features' );
            if ( ! is_array( $terms ) ) {
                continue;
            }
            foreach ( $terms as $term ) {
                if ( $term instanceof WP_Term ) {
                    $by_id[ intval( $term->term_id ) ] = $term;
                }
            }
        }
        $terms = array_values( $by_id );
        usort(
            $terms, function ( $a, $b ) {
				return strcasecmp( $a->name, $b->name );
			}
        );
        return $terms;
    }

    /**
     * Get a feature term's icon URL (theme stores it in term options).
     *
     * @param WP_Term $term Feature term.
     * @return string Icon URL, empty when none.
     */
    public static function get_feature_icon( $term ) {
        if ( ! ( $term instanceof WP_Term ) ) {
            return '';
        }
        $meta = get_option( 'taxonomy_' . intval( $term->term_id ) );
        if ( is_array( $meta ) && ! empty( $meta['category_featured_image'] ) ) {
            return esc_url_raw( $meta['category_featured_image'] );
        }
        return '';
    }

    /**
     * Get room display data for hotel cards.
     *
     * @param int $room_id Room post ID.
     * @return array{id:int,title:string,url:string,thumb:string,price:float,capacity:int,features:WP_Term[]} Room data.
     */
    public static function get_room_data( $room_id ) {
        $room_id = intval( $room_id );
        $features = array();
        if ( taxonomy_exists( 'property_features' ) ) {
            $terms = get_the_terms( $room_id, 'property_features' );
            if ( is_array( $terms ) ) {
                foreach ( $terms as $term ) {
                    if ( $term instanceof WP_Term ) {
                        $features[] = $term;
                    }
                }
            }
        }
        return array(
            'id'       => $room_id,
            'title'    => get_the_title( $room_id ),
            'url'      => get_permalink( $room_id ),
            'thumb'    => get_the_post_thumbnail_url( $room_id, 'medium_large' ),
            'price'    => floatval( get_post_meta( $room_id, 'property_price', true ) ),
            'capacity' => intval( get_post_meta( $room_id, 'guest_no', true ) ),
            'features' => $features,
        );
    }

    /**
     * Get IDs of all published rooms linked to a hotel.
     *
     * Unlike get_rooms(), this includes rooms without a price, so term
     * syncing sees every room that belongs to the hotel.
     *
     * @param int $hotel_id Hotel post ID.
     * @return int[] Room post IDs.
     */
    public static function get_room_ids( $hotel_id ) {
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Bounded ID-only lookup of one hotel's rooms.
        $ids = get_posts(
            array(
				'post_type'       => 'estate_property',
				'post_status'     => 'publish',
				'posts_per_page'  => -1,
				'fields'          => 'ids',
				'no_found_rows'   => true,
				'ssc_room_lookup' => true,
				'meta_key'       => self::ROOM_HOTEL_META,
				'meta_value'     => intval( $hotel_id ),
				'meta_compare'   => '=',
            )
        );
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        if ( ! is_array( $ids ) ) {
            return array();
        }
        return array_values( array_unique( array_map( 'intval', $ids ) ) );
    }

    /**
     * Sync a hotel's display data from its linked rooms.
     *
     * Hotels carry no location/category taxonomy of their own, so the
     * theme's property-unit card and map pins would render empty for them.
     * Inherit the rooms' union: min price into property_price, lat/lng via
     * ensure_coords(), shared terms into the hotel's own term lists, and the
     * first city into _ssc_city when unset.
     *
     * @param int $hotel_id Hotel post ID.
     * @return void
     */
    public static function sync_hotel_data( $hotel_id ) {
        $hotel_id = intval( $hotel_id );
        if ( get_post_type( $hotel_id ) !== HotelCPT::POST_TYPE ) {
            return;
        }
        $room_ids = self::get_room_ids( $hotel_id );

        $min = self::get_min_price( $hotel_id );
        if ( $min > 0 ) {
            update_post_meta( $hotel_id, 'property_price', $min );
        } else {
            delete_post_meta( $hotel_id, 'property_price' );
        }

        self::ensure_coords( $hotel_id );

        $taxonomies = array( 'property_city', 'property_area', 'property_category', 'property_action_category', 'property_status' );
        foreach ( $taxonomies as $taxonomy ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }
            $term_ids = array();
            foreach ( $room_ids as $room_id ) {
                $terms = get_the_terms( $room_id, $taxonomy );
                if ( ! is_array( $terms ) ) {
                    continue;
                }
                foreach ( $terms as $term ) {
                    if ( $term instanceof WP_Term ) {
                        $term_ids[] = intval( $term->term_id );
                    }
                }
            }
            wp_set_object_terms( $hotel_id, array_values( array_unique( $term_ids ) ), $taxonomy );
        }

        if ( '' === get_post_meta( $hotel_id, '_ssc_city', true ) ) {
            $cities = get_the_terms( $hotel_id, 'property_city' );
            if ( is_array( $cities ) && isset( $cities[0] ) && $cities[0] instanceof WP_Term ) {
                update_post_meta( $hotel_id, '_ssc_city', $cities[0]->slug );
            }
        }
    }

    /**
     * Ensure the hotel carries lat/lng meta, inheriting the first room's.
     *
     * Lets the theme map renderer work with a hotel post ID directly.
     *
     * @param int $hotel_id Hotel post ID.
     * @return array{lat:float,lng:float} Coordinates (0,0 when unknown).
     */
    public static function ensure_coords( $hotel_id ) {
        $hotel_id = intval( $hotel_id );
        $lat = floatval( get_post_meta( $hotel_id, 'property_latitude', true ) );
        $lng = floatval( get_post_meta( $hotel_id, 'property_longitude', true ) );
        if ( $lat !== 0.0 && $lng !== 0.0 ) {
            return array(
				'lat' => $lat,
				'lng' => $lng,
			);
        }
        $rooms = self::get_rooms( $hotel_id, 1 );
        foreach ( $rooms->posts as $room ) {
            $lat = floatval( get_post_meta( $room->ID, 'property_latitude', true ) );
            $lng = floatval( get_post_meta( $room->ID, 'property_longitude', true ) );
            if ( $lat !== 0.0 && $lng !== 0.0 ) {
                update_post_meta( $hotel_id, 'property_latitude', $lat );
                update_post_meta( $hotel_id, 'property_longitude', $lng );
                return array(
					'lat' => $lat,
					'lng' => $lng,
				);
            }
        }
        return array(
			'lat' => 0.0,
			'lng' => 0.0,
		);
    }

    /**
     * Check room availability for a date range via the theme engine.
     *
     * Accepts Y-m-d (native inputs, converted) or the theme's own date
     * format (theme widget inputs, passed through).
     *
     * @param int    $room_id Room post ID.
     * @param string $from    Check-in date, empty to skip.
     * @param string $to      Check-out date, empty to skip.
     * @return bool|null True/false when dates given, null when skipped.
     */
    public static function is_available( $room_id, $from, $to ) {
        $from = sanitize_text_field( (string) $from );
        $to = sanitize_text_field( (string) $to );
        if ( $from === '' || $to === '' ) {
            return null;
        }
        if ( ! function_exists( 'wpestate_check_booking_valability' ) ) {
            return null;
        }
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) && function_exists( 'wpestate_convert_dateformat_reverse' ) ) {
            $from = wpestate_convert_dateformat_reverse( $from );
            $to = wpestate_convert_dateformat_reverse( $to );
        }
        try {
            return (bool) wpestate_check_booking_valability( $from, $to, intval( $room_id ) );
        } catch ( \Throwable $e ) {
            return null;
        }
    }

    /**
     * Format a price using the theme currency options.
     *
     * @param float $price Price value.
     * @return string Formatted price with currency symbol.
     */
    public static function format_price( $price ) {
        $price = floatval( $price );
        $symbol = function_exists( 'wprentals_get_option' )
            ? wprentals_get_option( 'wp_estate_currency_symbol', '' )
            : '';
        $symbol_after = function_exists( 'wprentals_get_option' )
            ? wprentals_get_option( 'wp_estate_where_currency_symbol', '' )
            : '';
        $number = number_format_i18n( $price, ( fmod( $price, 1 ) === 0.0 ) ? 0 : 2 );
        if ( $symbol_after === '1' ) {
            return $number . esc_html( $symbol );
        }
        return esc_html( $symbol ) . $number;
    }
}
