<?php
/**
 * Group quote matching: query, availability, persist, notify.
 *
 * @package StaySuite\Companion\Booking
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Booking;

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
        $raw = is_array( $_POST ) ? wp_unslash( $_POST ) : array();
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
        $input = $this->sanitize_input( $raw );
        $error = $this->validate( $input );
        if ( $error !== '' ) {
            wp_send_json_error( array( 'message' => $error ) );
        }
        $limited = $this->check_rate_limit();
        if ( $limited !== '' ) {
            wp_send_json_error( array( 'message' => $limited ), 429 );
        }
        $matches = $this->find_matches( $input );
        $request_id = $this->persist( $input, $matches );
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
     * Validate sanitized input.
     *
     * @param array<string,mixed> $input Sanitized input.
     * @return string Error message or empty string when valid.
     */
    private function validate( $input ) {
        if ( $input['name'] === '' ) {
            return esc_html__( 'Please tell us your name.', 'staysuite-companion' );
        }
        if ( ! is_email( $input['email'] ) ) {
            return esc_html__( 'Please enter a valid email address.', 'staysuite-companion' );
        }
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
     * Enforce per-IP quote throttling with transients.
     *
     * Only the hash of the address is used as the transient key; the raw
     * IP is never stored. Limit shape is filterable:
     * `array( 'max' => 5, 'window' => 600 )`.
     *
     * @return string Error message when limited, empty string otherwise.
     */
    private function check_rate_limit() {
        $limit = apply_filters(
            'ssc_quote_rate_limit', array(
				'max' => 5,
				'window' => 600,
            )
        );
        if ( ! is_array( $limit ) ) {
            $limit = array( 'max' => $limit );
        }
        $max = isset( $limit['max'] ) ? max( 1, intval( $limit['max'] ) ) : 5;
        $window = isset( $limit['window'] ) ? max( 60, intval( $limit['window'] ) ) : 600;
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '';
        if ( '' === $ip ) {
            return '';
        }
        $key = 'ssc_rl_' . md5( $ip );
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
        $matches = array();
        $check_dates = $input['check_in'] !== '' && $input['check_out'] !== '';
        foreach ( $pool->posts as $room_id ) {
            if ( count( $matches ) >= self::MAX_MATCHES ) {
                break;
            }
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
            $matches[] = $this->match_data( intval( $room_id ) );
        }
        return $matches;
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
        return array(
            'id'     => $room_id,
            'title'  => get_the_title( $room_id ),
            'url'    => get_permalink( $room_id ),
            'image'  => $thumb !== false ? $thumb : '',
            'price'  => $price > 0 ? Repository::format_price( $price ) : '',
            'guests' => intval( get_post_meta( $room_id, 'guest_no', true ) ),
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
        if ( $request_id <= 0 ) {
            return 0;
        }
        foreach ( array( 'city', 'location_text', 'check_in', 'check_out', 'rooms', 'guests', 'male', 'female', 'budget_min', 'budget_max', 'name', 'email', 'phone', 'requirements' ) as $key ) {
            update_post_meta( $request_id, '_ssc_' . $key, $input[ $key ] );
        }
        update_post_meta( $request_id, '_ssc_status', 'pending' );
        update_post_meta( $request_id, '_ssc_matched', wp_list_pluck( $matches, 'id' ) );
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
            $parts[] = $count . 'x ' . get_the_title( $room_id );
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
        $lines = array(
            sprintf(
                /* translators: %s: visitor name. */
                __( 'Name: %s', 'staysuite-companion' ),
                $input['name']
            ),
            sprintf(
                /* translators: %s: visitor email. */
                __( 'Email: %s', 'staysuite-companion' ),
                $input['email']
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
                /* translators: %s: edit-post URL. */
                __( 'Review: %s', 'staysuite-companion' ),
                get_edit_post_link( $request_id, 'display' )
            ),
        );
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        if ( is_email( $input['email'] ) ) {
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
