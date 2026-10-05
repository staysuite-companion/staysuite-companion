<?php
/**
 * Group quote matching: query, availability, persist, notify.
 *
 * @package StaySuite\Companion\Booking
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Booking;

use StaySuite\Companion\Admin\Settings;
use StaySuite\Companion\Hotel\Repository;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles group quote submissions over AJAX.
 */
class QuoteAjax {

    /**
     * Nonce action for quote submissions.
     *
     * @var string
     */
    const NONCE_ACTION = 'ssc_group_quote';

    /**
     * Honeypot field name. Bots fill it, humans never see it.
     *
     * @var string
     */
    const HONEYPOT_FIELD = 'ssc_company';

    /**
     * Maximum matches returned to the browser.
     *
     * @var int
     */
    const MAX_MATCHES = 12;

    /**
     * Rooms scanned per request before availability filtering.
     *
     * @var int
     */
    const POOL_SIZE = 60;

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'wp_ajax_ssc_group_quote', array( $this, 'handle' ) );
        add_action( 'wp_ajax_nopriv_ssc_group_quote', array( $this, 'handle' ) );
        add_action( 'wp_ajax_ssc_group_suggest', array( $this, 'handle_suggest' ) );
        add_action( 'wp_ajax_nopriv_ssc_group_suggest', array( $this, 'handle_suggest' ) );
        add_action( 'wp_ajax_ssc_quote_nonce', array( $this, 'serve_nonce' ) );
        add_action( 'wp_ajax_nopriv_ssc_quote_nonce', array( $this, 'serve_nonce' ) );
    }

    /**
     * Serve a fresh quote nonce.
     *
     * Pages may be cached, so the localized nonce can go stale. The form
     * refreshes it through this endpoint and retries once (see G.4).
     *
     * @return void
     */
    public function serve_nonce() {
        wp_send_json_success( array( 'nonce' => wp_create_nonce( self::NONCE_ACTION ) ) );
    }

    /**
     * Validate input, find matching rooms, persist the request, notify.
     *
     * @return void
     */
    public function handle() {
        $raw = is_array( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and honeypot verified in verify_access().
        $this->verify_access( $raw );
        $input = $this->sanitize_input( $raw );
        $error = $this->validate_trip( $input );
        if ( '' === $error ) {
            $error = $this->validate_contact( $input );
        }
        if ( $error !== '' ) {
            wp_send_json_error( array( 'message' => $error ) );
        }
        $limited = $this->check_rate_limit();
        if ( $limited !== '' ) {
            wp_send_json_error( array( 'message' => $limited ), 429 );
        }
        $matches = $this->find_matches( $input );
        $request_id = $this->persist( $input, $matches );
        if ( $request_id <= 0 ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Your request could not be saved. Please try again.', 'staysuite-companion' ),
                )
            );
        }
        $this->notify( $request_id, $input, $matches );
        /**
         * Fires after a group request is stored and mailed.
         *
         * Pro entry point: deposit/invoice handoff, CRM sync, reminders.
         *
         * @param int                           $request_id Request post ID.
         * @param array<string,mixed>           $input      Sanitized input.
         * @param array<int,array<string,mixed>> $matches    Match list.
         */
        do_action( 'ssc_group_request_saved', $request_id, $input, $matches );
        wp_send_json_success(
            array(
				'request_id' => $request_id,
				'total'      => count( $matches ),
				'matches'    => $matches,
            )
        );
    }

    /**
     * Suggest matching stays without storing anything.
     *
     * Anonymous first step of the group flow: same matching as a quote,
     * but no contact details needed, nothing persisted, nobody emailed.
     *
     * @return void
     */
    public function handle_suggest() {
        $raw = is_array( $_POST ) ? wp_unslash( $_POST ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and honeypot verified in verify_access().
        $this->verify_access( $raw );
        $input = $this->sanitize_input( $raw );
        $error = $this->validate_trip( $input );
        if ( $error !== '' ) {
            wp_send_json_error( array( 'message' => $error ) );
        }
        $limited = $this->check_rate_limit( 'suggest' );
        if ( $limited !== '' ) {
            wp_send_json_error( array( 'message' => $limited ), 429 );
        }
        $matches = $this->find_matches( $input );
        wp_send_json_success(
            array(
				'total'   => count( $matches ),
				'matches' => $matches,
            )
        );
    }

    /**
     * Check the nonce and honeypot for a submission.
     *
     * Sends the JSON error and exits on failure (like wp_send_json_*).
     *
     * @param array<string,mixed> $raw Unslashed POST data.
     * @return void
     */
    private function verify_access( $raw ) {
        $nonce = isset( $raw['nonce'] ) && is_string( $raw['nonce'] ) ? sanitize_key( $raw['nonce'] ) : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Your session expired. Please try again.', 'staysuite-companion' ),
                    'code'    => 'ssc_nonce_expired',
                ),
                403
            );
        }
        if ( isset( $raw[ self::HONEYPOT_FIELD ] ) && '' !== $raw[ self::HONEYPOT_FIELD ] ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Your request could not be submitted. Please try again.', 'staysuite-companion' ),
                )
            );
        }
    }

    /**
     * Sanitize raw submission data.
     *
     * Lengths are capped (name 100, email 254, phone 40, requirements
     * 2000) and party sizes bounded (rooms 50, guests 500). Dates pass
     * through here and are strictly validated in validate().
     *
     * @param array<string,mixed> $raw Unslashed POST data.
     * @return array<string,mixed> Sanitized input.
     */
    private function sanitize_input( $raw ) {
        $budget_min = isset( $raw['budget_min'] ) ? floatval( $raw['budget_min'] ) : 0;
        $budget_max = isset( $raw['budget_max'] ) ? floatval( $raw['budget_max'] ) : 0;
        if ( $budget_min > 0 && $budget_max > 0 && $budget_min > $budget_max ) {
            $tmp = $budget_min;
            $budget_min = $budget_max;
            $budget_max = $tmp;
        }
        $input = array(
            'city'         => isset( $raw['city'] ) ? sanitize_title( $raw['city'] ) : '',
            'location_text' => isset( $raw['location_text'] ) ? sanitize_text_field( $raw['location_text'] ) : '',
            'check_in'     => isset( $raw['check_in'] ) ? sanitize_text_field( $raw['check_in'] ) : '',
            'check_out'    => isset( $raw['check_out'] ) ? sanitize_text_field( $raw['check_out'] ) : '',
            'rooms'        => isset( $raw['rooms'] ) ? min( 50, max( 1, intval( $raw['rooms'] ) ) ) : 1,
            'guests'       => isset( $raw['guests'] ) ? min( 500, max( 1, intval( $raw['guests'] ) ) ) : 2,
            'male'         => isset( $raw['male'] ) ? min( 500, max( 0, intval( $raw['male'] ) ) ) : 0,
            'female'       => isset( $raw['female'] ) ? min( 500, max( 0, intval( $raw['female'] ) ) ) : 0,
            'budget_min'   => max( 0, $budget_min ),
            'budget_max'   => max( 0, $budget_max ),
            'name'         => isset( $raw['name'] ) ? self::cap_length( sanitize_text_field( $raw['name'] ), 100 ) : '',
            'email'        => isset( $raw['email'] ) ? self::cap_length( sanitize_email( $raw['email'] ), 254 ) : '',
            'phone'        => isset( $raw['phone'] ) ? self::cap_length( sanitize_text_field( $raw['phone'] ), 40 ) : '',
            'requirements' => isset( $raw['requirements'] ) ? self::cap_length( sanitize_textarea_field( $raw['requirements'] ), 2000 ) : '',
            'selected_rooms' => self::sanitize_id_list( $raw['selected_rooms'] ?? array() ),
        );
        $input['check_in']  = self::normalize_date( $input['check_in'] );
        $input['check_out'] = self::normalize_date( $input['check_out'] );
        /**
         * Filter the sanitized quote payload (Pro: add-ons, pricing options).
         *
         * @param array<string,mixed> $input Sanitized input.
         * @param array<string,mixed> $raw   Raw POST data.
         */
        return apply_filters( 'ssc_quote_payload', $input, $raw );
    }

    /**
     * Sanitize a list of room IDs from the request.
     *
     * @param mixed $value Raw value.
     * @return int[] Unique positive room IDs, at most 50.
     */
    private static function sanitize_id_list( $value ) {
        if ( ! is_array( $value ) ) {
            return array();
        }
        $ids = array();
        foreach ( $value as $id ) {
            $id = intval( $id );
            if ( $id > 0 ) {
                $ids[] = $id;
            }
        }
        return array_values( array_unique( array_slice( $ids, 0, 50 ) ) );
    }

    /**
     * Truncate a string to a maximum length (multibyte-safe when available).
     *
     * @param string $value  Value to cap.
     * @param int    $length Maximum characters.
     * @return string Capped value.
     */
    private static function cap_length( $value, $length ) {
        $value = (string) $value;
        if ( function_exists( 'mb_substr' ) ) {
            return mb_substr( $value, 0, $length );
        }
        return substr( $value, 0, $length );
    }

    /**
     * Normalize a submitted date to Y-m-d.
     *
     * Accepts strict Y-m-d first, then the theme's own datepicker format
     * (group mode reads theme fields verbatim), so theme-fed values are
     * not rejected. Anything else becomes an empty string and fails
     * validation.
     *
     * @param string $value Raw date value.
     * @return string Y-m-d date or empty string.
     */
    private static function normalize_date( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }
        $parsed = self::parse_ymd( $value );
        if ( null !== $parsed ) {
            return $parsed;
        }
        if ( function_exists( 'wprentals_get_option' ) ) {
            $parsed = self::parse_theme_date( $value, intval( wprentals_get_option( 'wp_estate_date_format', 0 ) ) );
            if ( null !== $parsed ) {
                return $parsed;
            }
        }
        return '';
    }

    /**
     * Parse a strict Y-m-d calendar date.
     *
     * @param string $value Candidate date.
     * @return string|null Canonical Y-m-d or null.
     */
    private static function parse_ymd( $value ) {
        if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
            return null;
        }
        $year = intval( $m[1] );
        $month = intval( $m[2] );
        $day = intval( $m[3] );
        if ( ! checkdate( $month, $day, $year ) ) {
            return null;
        }
        return sprintf( '%04d-%02d-%02d', $year, $month, $day );
    }

    /**
     * Parse a date in the theme's configured datepicker format.
     *
     * The theme stores its format as 0-5 (yy-mm-dd, yy-dd-mm, dd-mm-yy,
     * mm-dd-yy, dd-yy-mm, mm-yy-dd). Two-digit years assume the 2000s
     * below 70, the 1900s above.
     *
     * @param string $value  Candidate date.
     * @param int    $format Theme format index.
     * @return string|null Canonical Y-m-d or null.
     */
    private static function parse_theme_date( $value, $format ) {
        $orders = array(
            0 => array( 'Y', 'm', 'd' ),
            1 => array( 'Y', 'd', 'm' ),
            2 => array( 'd', 'm', 'Y' ),
            3 => array( 'm', 'd', 'Y' ),
            4 => array( 'd', 'Y', 'm' ),
            5 => array( 'm', 'Y', 'd' ),
        );
        if ( ! isset( $orders[ $format ] ) ) {
            return null;
        }
        $parts = preg_split( '/\D+/', $value );
        if ( ! is_array( $parts ) || count( $parts ) !== 3 ) {
            return null;
        }
        $map = array_combine( $orders[ $format ], array_map( 'intval', $parts ) );
        if ( ! is_array( $map ) ) {
            return null;
        }
        $year = $map['Y'];
        if ( $year < 100 ) {
            $year += ( $year < 70 ) ? 2000 : 1900;
        }
        if ( ! checkdate( $map['m'], $map['d'], $year ) ) {
            return null;
        }
        return sprintf( '%04d-%02d-%02d', $year, $map['m'], $map['d'] );
    }

    /**
     * Validate the trip half of the input (city, dates).
     *
     * Runs for suggestions and quotes alike; contact details are checked
     * separately in validate_contact() so suggestions stay anonymous.
     *
     * @param array<string,mixed> $input Sanitized input.
     * @return string Error message or empty string when valid.
     */
    private function validate_trip( $input ) {
        if ( $input['city'] !== '' && ! get_term_by( 'slug', $input['city'], 'property_city' ) ) {
            return esc_html__( 'Please choose a valid place.', 'staysuite-companion' );
        }
        $dates_set = $input['check_in'] !== '' || $input['check_out'] !== '';
        if ( $dates_set ) {
            if ( $input['check_in'] === '' || $input['check_out'] === '' ) {
                return esc_html__( 'Please enter valid check-in and check-out dates.', 'staysuite-companion' );
            }
            try {
                $check_in  = new \DateTime( $input['check_in'], wp_timezone() );
                $check_out = new \DateTime( $input['check_out'], wp_timezone() );
                $today     = new \DateTime( 'today', wp_timezone() );
            } catch ( \Exception $e ) {
                return esc_html__( 'Please enter valid check-in and check-out dates.', 'staysuite-companion' );
            }
            if ( $check_out <= $check_in ) {
                return esc_html__( 'Check-out must be after check-in.', 'staysuite-companion' );
            }
            if ( $check_in < $today ) {
                return esc_html__( 'Check-in cannot be in the past.', 'staysuite-companion' );
            }
        }
        return '';
    }

    /**
     * Validate the contact half of the input (name + required contact).
     *
     * Which field is mandatory comes from Settings → Group quotes →
     * Required contact (email only, phone only, or both). Phone numbers
     * are only required to be present, never format-checked.
     *
     * @param array<string,mixed> $input Sanitized input.
     * @return string Error message or empty string when valid.
     */
    private function validate_contact( $input ) {
        if ( $input['name'] === '' ) {
            return esc_html__( 'Please tell us your name.', 'staysuite-companion' );
        }
        $required = Settings::get( 'contact_required' );
        if ( 'phone' !== $required && ! is_email( $input['email'] ) ) {
            return esc_html__( 'Please enter a valid email address.', 'staysuite-companion' );
        }
        if ( 'email' !== $required && '' === trim( $input['phone'] ) ) {
            return esc_html__( 'Please enter your phone number.', 'staysuite-companion' );
        }
        return '';
    }

    /**
     * Enforce per-IP throttling with transients.
     *
     * Quotes (stored + emailed) stay strict; suggestions (read-only,
     * nothing stored) get a generous bucket so tweaking prefs never
     * locks a visitor out. Buckets are separate: browsing suggestions
     * does not eat the quote allowance. Only the hash of the address is
     * used as the transient key; the raw IP is never stored. Limit shape
     * is filterable:
     * `array( 'max' => 5, 'window' => 600, 'suggest_max' => 30, 'suggest_window' => 600 )`.
     *
     * @param string $type 'quote' or 'suggest'.
     * @return string Error message when limited, empty string otherwise.
     */
    private function check_rate_limit( $type = 'quote' ) {
        $limit = apply_filters(
            'ssc_quote_rate_limit', array(
				'max' => 5,
				'window' => 600,
				'suggest_max' => 30,
				'suggest_window' => 600,
            )
        );
        if ( ! is_array( $limit ) ) {
            $limit = array( 'max' => $limit );
        }
        if ( 'suggest' === $type ) {
            $max = isset( $limit['suggest_max'] ) ? max( 1, intval( $limit['suggest_max'] ) ) : 30;
            $window = isset( $limit['suggest_window'] ) ? max( 60, intval( $limit['suggest_window'] ) ) : 600;
        } else {
            $max = isset( $limit['max'] ) ? max( 1, intval( $limit['max'] ) ) : 5;
            $window = isset( $limit['window'] ) ? max( 60, intval( $limit['window'] ) ) : 600;
        }
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '';
        if ( '' === $ip ) {
            return '';
        }
        $key = 'ssc_rl_' . md5( $ip ) . '_' . $type;
        $count = intval( get_transient( $key ) );
        if ( $count >= $max ) {
            return esc_html__( 'Too many requests. Please try again in a few minutes.', 'staysuite-companion' );
        }
        set_transient( $key, $count + 1, $window );
        return '';
    }

    /**
     * Resolve free location text to a city term slug.
     *
     * @param string $text Location text from the theme search.
     * @return string City slug or empty string.
     */
    private function resolve_city_slug( $text ) {
        $text = trim( $text );
        if ( $text === '' || ! taxonomy_exists( 'property_city' ) ) {
            return '';
        }
        $term = get_term_by( 'slug', sanitize_title( $text ), 'property_city' );
        if ( $term instanceof \WP_Term ) {
            return $term->slug;
        }
        $terms = get_terms(
            array(
				'taxonomy'   => 'property_city',
				'search'     => $text,
				'number'     => 1,
				'hide_empty' => false,
				'fields'     => 'slugs',
            )
        );
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            return $terms[0];
        }
        return '';
    }

    /**
     * Find rooms matching city, capacity and budget, then availability.
     *
     * Note: budget filters the base property_price meta. Multi-currency
     * conversion (theme divides by cookie rate) is not applied in v1.
     *
     * When the search_result setting returns hotels, matching rooms are
     * grouped by their hotel (same room→hotel rule as the search rewrite)
     * and the hotels are suggested instead of the rooms.
     *
     * @param array<string,mixed> $input Sanitized input.
     * @return array<int,array<string,mixed>> Match list.
     */
    private function find_matches( $input ) {
        $city_slug = $input['city'];
        if ( $city_slug === '' && $input['location_text'] !== '' ) {
            $city_slug = $this->resolve_city_slug( $input['location_text'] );
        }
        // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.DB.SlowDBQuery.slow_db_query_meta_query, WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Quote matching runs once per request against a bounded room pool.
        $args = array(
            'post_type'      => 'estate_property',
            'post_status'    => 'publish',
            'posts_per_page' => self::POOL_SIZE,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'orderby'        => 'meta_value_num date',
            'meta_key'       => 'property_price',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => 'guest_no',
                    'value'   => $input['guests'],
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                ),
            ),
        );
        if ( $input['budget_min'] > 0 || $input['budget_max'] > 0 ) {
            $args['meta_query'][] = array(
                'key'     => 'property_price',
                'value'   => array( $input['budget_min'], $input['budget_max'] > 0 ? $input['budget_max'] : 999999999 ),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        }
        if ( $input['city'] !== '' ) {
            $args['tax_query'] = array(
                array(
					'taxonomy' => 'property_city',
					'field' => 'slug',
					'terms' => array( $input['city'] ),
				),
            );
        } elseif ( $city_slug !== '' ) {
            $args['tax_query'] = array(
                array(
					'taxonomy' => 'property_city',
					'field' => 'slug',
					'terms' => array( $city_slug ),
				),
            );
        }
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.DB.SlowDBQuery.slow_db_query_meta_query, WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        $pool = new WP_Query( $args );
        $check_dates = $input['check_in'] !== '' && $input['check_out'] !== '';
        $room_ids = array();
        foreach ( $pool->posts as $room_id ) {
            $room_id = intval( $room_id );
            if ( $check_dates && function_exists( 'wpestate_check_booking_valability' ) ) {
                try {
                    $available = wpestate_check_booking_valability(
                        Repository::to_engine_date( $input['check_in'] ),
                        Repository::to_engine_date( $input['check_out'] ),
                        $room_id
                    );
                } catch ( \Throwable $e ) {
                    $available = false;
                }
                if ( ! $available ) {
                    continue;
                }
            }
            $room_ids[] = $room_id;
        }
        if ( Settings::get( 'search_result' ) !== 'listings' ) {
            return $this->hotel_matches( $room_ids );
        }
        $matches = array();
        foreach ( $room_ids as $room_id ) {
            if ( count( $matches ) >= self::MAX_MATCHES ) {
                break;
            }
            $matches[] = $this->match_data( $room_id );
        }
        return $matches;
    }

    /**
     * Group matching rooms by hotel for hotel-mode suggestions.
     *
     * Rooms are cheapest-first, so hotels keep that order. Rooms without
     * a hotel link are skipped: in hotels mode there is nothing to show
     * them on.
     *
     * @param int[] $room_ids Available matching room IDs.
     * @return array<int,array<string,mixed>> Hotel match list.
     */
    private function hotel_matches( $room_ids ) {
        $by_hotel = array();
        foreach ( $room_ids as $room_id ) {
            $hotel_id = Repository::get_room_hotel_id( $room_id );
            if ( $hotel_id <= 0 ) {
                continue;
            }
            if ( ! isset( $by_hotel[ $hotel_id ] ) ) {
                $by_hotel[ $hotel_id ] = array();
            }
            $by_hotel[ $hotel_id ][] = $room_id;
        }
        $matches = array();
        foreach ( $by_hotel as $hotel_id => $rooms ) {
            if ( count( $matches ) >= self::MAX_MATCHES ) {
                break;
            }
            $matches[] = $this->hotel_match_data( $hotel_id, $rooms );
        }
        return $matches;
    }

    /**
     * Plain-text post title for JSON and email contexts.
     *
     * The_title filters (wptexturize) turn quotes into entities
     * (`Cox&#8217;s Bazar`). Browsers decode those in HTML, but React
     * text nodes and plain-text emails would show them literally —
     * decode first.
     *
     * @param int $post_id Post ID.
     * @return string Decoded title.
     */
    private static function plain_title( $post_id ) {
        return html_entity_decode( get_the_title( intval( $post_id ) ), ENT_QUOTES, 'UTF-8' );
    }

    /**
     * Build one match entry for the browser.
     *
     * @param int $room_id Room post ID.
     * @return array<string,mixed> Match data.
     */
    private function match_data( $room_id ) {
        $price = floatval( get_post_meta( $room_id, 'property_price', true ) );
        $thumb = get_the_post_thumbnail_url( $room_id, 'medium' );
        $original = Repository::get_original_price( $room_id );
        return array(
            'id'     => $room_id,
            'kind'   => 'room',
            'title'  => self::plain_title( $room_id ),
            'url'    => get_permalink( $room_id ),
            'image'  => $thumb !== false ? $thumb : '',
            'price'  => $price > 0 ? Repository::format_price( $price ) : '',
            'was'    => $original > $price ? Repository::format_price( $original ) : '',
            'guests' => intval( get_post_meta( $room_id, 'guest_no', true ) ),
            'rooms'  => 0,
        );
    }

    /**
     * Build one hotel match entry for the browser.
     *
     * Price is the hotel's synced minimum; the image falls back to the
     * first matching room when the hotel has no featured image. The
     * struck-through price mirrors the hotel cards: the original price
     * of the cheapest matching room, shown only when it beats the min.
     *
     * @param int   $hotel_id Hotel post ID.
     * @param int[] $room_ids Available matching room IDs in the hotel.
     * @return array<string,mixed> Match data.
     */
    private function hotel_match_data( $hotel_id, $room_ids ) {
        $price = Repository::get_min_price( $hotel_id );
        $thumb = get_the_post_thumbnail_url( $hotel_id, 'medium' );
        if ( false === $thumb && isset( $room_ids[0] ) ) {
            $thumb = get_the_post_thumbnail_url( intval( $room_ids[0] ), 'medium' );
        }
        $original = isset( $room_ids[0] ) ? Repository::get_original_price( intval( $room_ids[0] ) ) : 0;
        return array(
            'id'     => $hotel_id,
            'kind'   => 'hotel',
            'title'  => self::plain_title( $hotel_id ),
            'url'    => get_permalink( $hotel_id ),
            'image'  => false !== $thumb ? $thumb : '',
            'price'  => $price > 0 ? Repository::format_price( $price ) : '',
            'was'    => $original > $price ? Repository::format_price( $original ) : '',
            'guests' => 0,
            'rooms'  => count( $room_ids ),
        );
    }

    /**
     * Persist the request as a ssc_group_request post.
     *
     * @param array<string,mixed>         $input   Sanitized input.
     * @param array<int,array<string,mixed>> $matches Match list.
     * @return int Request post ID.
     */
    private function persist( $input, $matches ) {
        $city_name = $input['city'] !== '' ? $input['city'] : ( $input['location_text'] !== '' ? $input['location_text'] : 'anywhere' );
        $request_id = wp_insert_post(
            array(
				'post_title'  => sprintf(
                    /* translators: 1: visitor name, 2: city. */
                    __( 'Group request — %1$s — %2$s', 'staysuite-companion' ),
                    $input['name'],
                    $city_name
                ),
				'post_type'   => RequestCPT::POST_TYPE,
				'post_status' => 'publish',
            )
        );
        if ( is_wp_error( $request_id ) || $request_id <= 0 ) {
            return 0;
        }
        foreach ( array( 'city', 'location_text', 'check_in', 'check_out', 'rooms', 'guests', 'male', 'female', 'budget_min', 'budget_max', 'name', 'email', 'phone', 'requirements' ) as $key ) {
            update_post_meta( $request_id, '_ssc_' . $key, $input[ $key ] );
        }
        update_post_meta( $request_id, '_ssc_status', 'pending' );
        update_post_meta( $request_id, '_ssc_matched', wp_list_pluck( $matches, 'id' ) );
        update_post_meta( $request_id, '_ssc_selected', isset( $input['selected_rooms'] ) && is_array( $input['selected_rooms'] ) ? array_values( array_map( 'intval', $input['selected_rooms'] ) ) : array() );
        return intval( $request_id );
    }

    /**
     * Selection digest line for the admin email.
     *
     * Pro attaches selected_rooms/room_qty/total_estimate via
     * ssc_quote_payload; plain group-form requests carry none of them and
     * contribute an empty line, keeping the digest shape stable.
     *
     * @param array<string,mixed> $input Sanitized input.
     * @return string Digest line or empty string.
     */
    private static function selection_digest_line( $input ) {
        $ids = array();
        if ( isset( $input['selected_rooms'] ) && is_array( $input['selected_rooms'] ) ) {
            foreach ( $input['selected_rooms'] as $room_id ) {
                $room_id = intval( $room_id );
                if ( $room_id > 0 ) {
                    $ids[] = $room_id;
                }
            }
        }
        if ( $ids === array() ) {
            return '';
        }
        $qty = ( isset( $input['room_qty'] ) && is_array( $input['room_qty'] ) ) ? $input['room_qty'] : array();
        $parts = array();
        foreach ( array_unique( $ids ) as $room_id ) {
            $count = isset( $qty[ $room_id ] ) ? max( 1, intval( $qty[ $room_id ] ) ) : 1;
            $parts[] = $count . 'x ' . self::plain_title( $room_id );
        }
        $line = __( 'Selected rooms: ', 'staysuite-companion' ) . implode( ', ', $parts );
        $total = isset( $input['total_estimate'] ) ? floatval( $input['total_estimate'] ) : 0;
        if ( $total > 0 ) {
            $line .= ' (' . __( 'estimate: ', 'staysuite-companion' )
                . html_entity_decode( wp_strip_all_tags( Repository::format_price( $total ) ), ENT_QUOTES, 'UTF-8' ) . ')';
        }
        return $line;
    }

    /**
     * Email the admin digest and the requester confirmation.
     *
     * @param int                           $request_id Request post ID.
     * @param array<string,mixed>           $input      Sanitized input.
     * @param array<int,array<string,mixed>> $matches    Match list.
     * @return void
     */
    private function notify( $request_id, $input, $matches ) {
        if ( $request_id <= 0 ) {
            return;
        }
        $selected = isset( $input['selected_rooms'] ) && is_array( $input['selected_rooms'] ) ? $input['selected_rooms'] : array();
        $has_email = is_email( $input['email'] );
        $lines = array(
            sprintf(
                /* translators: %s: visitor name. */
                __( 'Name: %s', 'staysuite-companion' ),
                $input['name']
            ),
            sprintf(
                /* translators: %s: visitor email. */
                __( 'Email: %s', 'staysuite-companion' ),
                $has_email ? $input['email'] : __( '(none given — call the visitor)', 'staysuite-companion' )
            ),
            sprintf(
                /* translators: %s: visitor phone. */
                __( 'Phone: %s', 'staysuite-companion' ),
                $input['phone']
            ),
            sprintf(
                /* translators: %s: city slug. */
                __( 'City: %s', 'staysuite-companion' ),
                $input['city']
            ),
            sprintf(
                /* translators: 1: check-in date, 2: check-out date. */
                __( 'Dates: %1$s → %2$s', 'staysuite-companion' ),
                $input['check_in'],
                $input['check_out']
            ),
            sprintf(
                /* translators: 1: rooms, 2: guests, 3: male count, 4: female count. */
                __( 'Rooms: %1$d · Guests: %2$d (M:%3$d F:%4$d)', 'staysuite-companion' ),
                $input['rooms'],
                $input['guests'],
                $input['male'],
                $input['female']
            ),
            sprintf(
                /* translators: 1: minimum budget, 2: maximum budget. */
                __( 'Budget: %1$s – %2$s', 'staysuite-companion' ),
                $input['budget_min'],
                $input['budget_max']
            ),
            '',
            __( 'Requirements:', 'staysuite-companion' ),
            $input['requirements'],
            '',
            self::selection_digest_line( $input ),
            sprintf(
                /* translators: %d: number of matching properties. */
                __( 'Suggested properties: %d', 'staysuite-companion' ),
                count( $matches )
            ),
            sprintf(
                /* translators: %d: number of visitor-selected properties. */
                __( 'Selected by visitor: %d', 'staysuite-companion' ),
                count( $selected )
            ),
            sprintf(
                /* translators: %s: edit-post URL. */
                __( 'Review: %s', 'staysuite-companion' ),
                get_edit_post_link( $request_id, 'display' )
            ),
        );
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        if ( $has_email ) {
            $headers[] = 'Reply-To: ' . $input['email'];
        }
        wp_mail(
            get_option( 'admin_email' ),
            sprintf(
                /* translators: 1: site name, 2: request ID. */
                __( '[%1$s] New group booking request #%2$d', 'staysuite-companion' ),
                get_bloginfo( 'name' ),
                $request_id
            ),
            implode( "\n", $lines ),
            $headers
        );
        if ( ! $has_email ) {
            return;
        }
        wp_mail(
            $input['email'],
            sprintf(
                /* translators: %d: group booking request ID. */
                __( 'Your group booking request (#%d) is received', 'staysuite-companion' ),
                $request_id
            ),
            implode(
                "\n", array(
					sprintf(
						/* translators: %s: guest name. */
						__( 'Hi %s,', 'staysuite-companion' ),
						$input['name']
					),
					'',
					sprintf(
						/* translators: %d: number of matching properties. */
						__( 'We found %d properties in your budget and our team will contact you shortly with a quote.', 'staysuite-companion' ),
						count( $matches )
					),
                )
            ),
            $headers
        );
    }
}
