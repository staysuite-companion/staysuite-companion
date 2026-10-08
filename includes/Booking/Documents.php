<?php
/**
 * Guest documents: booking and group-request confirmation receipts.
 *
 * Free scope: Agoda-style email-safe receipts mailed automatically when
 * a theme booking (wpestate_booking.booking_status) or a group request
 * (_ssc_status) turns confirmed. Theme and core files are never touched:
 * transitions are watched via updated_post_meta, data is read from post
 * meta, and hotel address blocks resolve through the plugin's own hotel
 * links with theme-meta fallbacks.
 *
 * Pro scope (seams, not implementation): admin preview, manual resend
 * console, the invoice document and reminders. The public send_*() and
 * render_*() methods below are the API Pro builds on.
 *
 * @package StaySuite\Companion\Booking
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Booking;

use StaySuite\Companion\Admin\Settings;
use StaySuite\Companion\Hotel\Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Builds and sends confirmation receipts.
 */
class Documents {

    /**
     * Meta flagging a sent receipt (timestamp value).
     *
     * @var string
     */
    const SENT_META = '_ssc_confirmation_sent';

    /**
     * Marker keeping InvoiceEmail from double-wrapping our shell.
     *
     * @var string
     */
    const SHELL_MARKER = '<!--ssc-doc-->';

    /**
     * Wire up hooks.
     */
    public function __construct() {
        add_action( 'updated_post_meta', array( $this, 'maybe_auto_send' ), 10, 4 );
    }

    /**
     * Auto-send receipts on confirmation transitions.
     *
     * @param int    $meta_id  Meta row ID.
     * @param int    $post_id  Post ID.
     * @param string $meta_key Meta key.
     * @param mixed  $value    New value.
     * @return void
     */
    public function maybe_auto_send( $meta_id, $post_id, $meta_key, $value ) {
        if ( empty( Settings::get( 'confirmation_auto' ) ) ) {
            return;
        }
        $post_id = intval( $post_id );
        if ( $meta_key === 'booking_status' && $value === 'confirmed' && get_post_type( $post_id ) === 'wpestate_booking' ) {
            self::send_booking_confirmation( $post_id );
            return;
        }
        if ( $meta_key === '_ssc_status' && $value === 'confirmed' && get_post_type( $post_id ) === RequestCPT::POST_TYPE ) {
            self::send_request_confirmation( $post_id );
        }
    }

    /**
     * Send (or resend) a theme-booking receipt to the guest.
     *
     * Public seam for Pro's manual resend console.
     *
     * @param int $booking_id Booking post ID.
     * @return bool True when mailed.
     */
    public static function send_booking_confirmation( $booking_id ) {
        $booking_id = intval( $booking_id );
        $email = self::booking_guest_email( $booking_id );
        if ( $booking_id <= 0 || $email === '' || get_post_type( $booking_id ) !== 'wpestate_booking' ) {
            return false;
        }
        $property_id = intval( get_post_meta( $booking_id, 'booking_id', true ) );
        $subject = sprintf(
            /* translators: 1: property name, 2: booking reference. */
            esc_html__( 'Booking confirmation – %1$s (%2$s)', 'staysuite-companion' ),
            get_the_title( $property_id ),
            $booking_id
        );
        /**
         * Filter the booking confirmation subject.
         *
         * @param string $subject    Subject.
         * @param int    $booking_id Booking post ID.
         */
        $subject = apply_filters( 'ssc_confirmation_subject', $subject, $booking_id );
        $sent = wp_mail( $email, $subject, self::render_booking_confirmation( $booking_id ), self::mail_headers() );
        if ( $sent ) {
            update_post_meta( $booking_id, self::SENT_META, current_time( 'mysql' ) );
            /**
             * Fires after a booking receipt is mailed (Pro: logging, reminders).
             *
             * @param int    $booking_id Booking post ID.
             * @param string $email      Recipient.
             */
            do_action( 'ssc_confirmation_sent', $booking_id, $email );
        }
        return (bool) $sent;
    }

    /**
     * Send (or resend) a group-request receipt to the guest.
     *
     * Public seam for Pro's manual resend console.
     *
     * @param int $request_id Request post ID.
     * @return bool True when mailed.
     */
    public static function send_request_confirmation( $request_id ) {
        $request_id = intval( $request_id );
        $email = (string) get_post_meta( $request_id, '_ssc_email', true );
        if ( $request_id <= 0 || $email === '' || ! is_email( $email ) || get_post_type( $request_id ) !== RequestCPT::POST_TYPE ) {
            return false;
        }
        $subject = sprintf(
            /* translators: %s: request reference. */
            esc_html__( 'Group request confirmation – %s', 'staysuite-companion' ),
            $request_id
        );
        /**
         * Filter the group-request confirmation subject.
         *
         * @param string $subject    Subject.
         * @param int    $request_id Request post ID.
         */
        $subject = apply_filters( 'ssc_request_confirmation_subject', $subject, $request_id );
        $sent = wp_mail( $email, $subject, self::render_request_confirmation( $request_id ), self::mail_headers() );
        if ( $sent ) {
            update_post_meta( $request_id, self::SENT_META, current_time( 'mysql' ) );
            /**
             * Fires after a group-request receipt is mailed.
             *
             * @param int    $request_id Request post ID.
             * @param string $email      Recipient.
             */
            do_action( 'ssc_request_confirmation_sent', $request_id, $email );
        }
        return (bool) $sent;
    }

    /**
     * Guest email for a theme booking: post author, then invoice buyer.
     *
     * @param int $booking_id Booking post ID.
     * @return string Email or empty string.
     */
    private static function booking_guest_email( $booking_id ) {
        $author = intval( get_post_field( 'post_author', $booking_id ) );
        if ( $author > 0 ) {
            $user = get_userdata( $author );
            if ( $user && is_email( $user->user_email ) ) {
                return $user->user_email;
            }
        }
        $invoice_id = intval( get_post_meta( $booking_id, 'booking_invoice_no', true ) );
        foreach ( array( $invoice_id, $booking_id ) as $post_id ) {
            if ( $post_id <= 0 ) {
                continue;
            }
            $email = (string) get_post_meta( $post_id, 'user_email', true );
            if ( is_email( $email ) ) {
                return $email;
            }
        }
        return '';
    }

    /**
     * HTML mail headers.
     *
     * @return string[] Headers.
     */
    private static function mail_headers() {
        return array( 'Content-Type: text/html; charset=UTF-8' );
    }

    /**
     * Render the theme-booking receipt.
     *
     * Public seam for Pro's admin preview.
     *
     * @param int $booking_id Booking post ID.
     * @return string Email-safe HTML.
     */
    public static function render_booking_confirmation( $booking_id ) {
        $booking_id = intval( $booking_id );
        $property_id = intval( get_post_meta( $booking_id, 'booking_id', true ) );
        $invoice_id = intval( get_post_meta( $booking_id, 'booking_invoice_no', true ) );
        $author = get_userdata( intval( get_post_field( 'post_author', $booking_id ) ) );

        $facts = array(
            esc_html__( 'Booking ID', 'staysuite-companion' ) => (string) $booking_id,
            esc_html__( 'Status', 'staysuite-companion' )     => self::booking_status_line( $booking_id, $invoice_id ),
            esc_html__( 'Client', 'staysuite-companion' )     => $author ? $author->display_name : '',
            esc_html__( 'Property', 'staysuite-companion' ) => get_the_title( $property_id ),
            esc_html__( 'Address', 'staysuite-companion' )    => self::stay_address( $property_id ),
            esc_html__( 'Arrival', 'staysuite-companion' )    => self::display_date( get_post_meta( $booking_id, 'booking_from_date', true ) ),
            esc_html__( 'Departure', 'staysuite-companion' ) => self::display_date( get_post_meta( $booking_id, 'booking_to_date', true ) ),
            esc_html__( 'Guests', 'staysuite-companion' )    => self::meta_text( $booking_id, 'booking_guests' ),
            esc_html__( 'Room type', 'staysuite-companion' ) => self::room_type( $property_id ),
        );
        $facts = array_merge( $facts, self::payment_facts( $invoice_id ) );

        $html = self::shell_open( esc_html__( 'Booking Confirmation', 'staysuite-companion' ) );
        $html .= self::fact_table( $facts );
        $html .= self::through_block();
        $html .= self::notes_block( $booking_id, 'booking' );
        $html .= self::shell_close();
        /**
         * Filter the booking receipt HTML (Pro: extra rows, branding).
         *
         * @param string $html       Receipt HTML.
         * @param int    $booking_id Booking post ID.
         */
        return apply_filters( 'ssc_confirmation_html', $html, $booking_id );
    }

    /**
     * Render the group-request receipt.
     *
     * Public seam for Pro's admin preview.
     *
     * @param int $request_id Request post ID.
     * @return string Email-safe HTML.
     */
    public static function render_request_confirmation( $request_id ) {
        $request_id = intval( $request_id );
        $get = static function ( $key ) use ( $request_id ) {
            $v = get_post_meta( $request_id, '_ssc_' . $key, true );
            return ( '' === trim( (string) $v ) ) ? '' : (string) $v;
        };
        $facts = array(
            esc_html__( 'Reference', 'staysuite-companion' ) => (string) $request_id,
            esc_html__( 'Status', 'staysuite-companion' )    => RequestCPT::STATUSES[ RequestCPT::get_status( $request_id ) ],
            esc_html__( 'Name', 'staysuite-companion' )      => $get( 'name' ),
            esc_html__( 'Email', 'staysuite-companion' )     => $get( 'email' ),
            esc_html__( 'Phone', 'staysuite-companion' )     => $get( 'phone' ),
            esc_html__( 'Location', 'staysuite-companion' ) => $get( 'location_text' ) !== '' ? $get( 'location_text' ) : $get( 'city' ),
            esc_html__( 'Check in', 'staysuite-companion' ) => $get( 'check_in' ),
            esc_html__( 'Check out', 'staysuite-companion' ) => $get( 'check_out' ),
            esc_html__( 'Rooms', 'staysuite-companion' )     => $get( 'rooms' ),
            esc_html__( 'Guests', 'staysuite-companion' )    => $get( 'guests' ),
            esc_html__( 'Stays', 'staysuite-companion' )     => self::request_stays( $request_id ),
        );
        $estimate = floatval( get_post_meta( $request_id, '_ssc_pro_total_estimate', true ) );
        if ( $estimate > 0 ) {
            $facts[ esc_html__( 'Estimated total', 'staysuite-companion' ) ] = html_entity_decode( wp_strip_all_tags( Repository::format_price( $estimate ) ), ENT_QUOTES, 'UTF-8' );
        }

        $html = self::shell_open( esc_html__( 'Group Request Confirmation', 'staysuite-companion' ) );
        $html .= self::fact_table( $facts );
        $html .= '<p style="color:#5d6475;font-size:14px;">' . esc_html__( 'No payment has been taken. Our team will confirm availability and email your quote next.', 'staysuite-companion' ) . '</p>';
        $html .= self::through_block();
        $html .= self::notes_block( $request_id, 'request' );
        $html .= self::shell_close();
        /**
         * Filter the group-request receipt HTML.
         *
         * @param string $html       Receipt HTML.
         * @param int    $request_id Request post ID.
         */
        return apply_filters( 'ssc_request_confirmation_html', $html, $request_id );
    }

    /**
     * Status line: confirmed + payment state.
     *
     * @param int $booking_id Booking post ID.
     * @param int $invoice_id Invoice post ID.
     * @return string Status line.
     */
    private static function booking_status_line( $booking_id, $invoice_id ) {
        $full = get_post_meta( $booking_id, 'booking_status_full', true );
        if ( $full === '' && $invoice_id > 0 ) {
            $full = get_post_meta( $invoice_id, 'invoice_status_full', true );
        }
        if ( $full === 'confirmed' ) {
            return esc_html__( 'Confirmed · fully paid', 'staysuite-companion' );
        }
        $paid = $invoice_id > 0 ? floatval( get_post_meta( $invoice_id, 'depozit_paid', true ) ) : 0;
        if ( $paid > 0 ) {
            return esc_html__( 'Confirmed · deposit paid', 'staysuite-companion' );
        }
        return esc_html__( 'Confirmed', 'staysuite-companion' );
    }

    /**
     * Payment rows from the invoice: total, deposit paid, balance due.
     *
     * @param int $invoice_id Invoice post ID.
     * @return array<string,string> Rows, empty when no invoice.
     */
    private static function payment_facts( $invoice_id ) {
        if ( $invoice_id <= 0 || get_post_type( $invoice_id ) !== 'wpestate_invoice' ) {
            return array();
        }
        $total = floatval( get_post_meta( $invoice_id, 'item_price', true ) );
        if ( $total <= 0 ) {
            return array();
        }
        $paid = floatval( get_post_meta( $invoice_id, 'depozit_paid', true ) );
        $rows = array(
            esc_html__( 'Total', 'staysuite-companion' ) => html_entity_decode( wp_strip_all_tags( Repository::format_price( $total ) ), ENT_QUOTES, 'UTF-8' ),
        );
        if ( $paid > 0 ) {
            $rows[ esc_html__( 'Paid', 'staysuite-companion' ) ] = html_entity_decode( wp_strip_all_tags( Repository::format_price( $paid ) ), ENT_QUOTES, 'UTF-8' );
            $rows[ esc_html__( 'Balance due', 'staysuite-companion' ) ] = html_entity_decode( wp_strip_all_tags( Repository::format_price( max( 0, $total - $paid ) ) ), ENT_QUOTES, 'UTF-8' );
        }
        return $rows;
    }

    /**
     * Stay address: linked hotel first, theme property address fallback.
     *
     * @param int $property_id Room post ID.
     * @return string Address lines.
     */
    private static function stay_address( $property_id ) {
        $hotel_id = Repository::get_room_hotel_id( $property_id );
        if ( $hotel_id > 0 ) {
            $parts = array_filter(
                array(
                    get_post_meta( $hotel_id, '_ssc_address', true ),
                    get_post_meta( $hotel_id, '_ssc_city', true ),
                    get_post_meta( $hotel_id, '_ssc_phone', true ),
                )
            );
            if ( $parts !== array() ) {
                return implode( ', ', array_map( 'strval', $parts ) );
            }
            return get_the_title( $hotel_id );
        }
        $parts = array_filter(
            array(
                get_post_meta( $property_id, 'property_address', true ),
                get_post_meta( $property_id, 'property_city', true ),
                get_post_meta( $property_id, 'property_country', true ),
            )
        );
        return implode( ', ', array_map( 'strval', $parts ) );
    }

    /**
     * Room type from the listing-type term.
     *
     * @param int $property_id Room post ID.
     * @return string Term name or empty string.
     */
    private static function room_type( $property_id ) {
        if ( ! taxonomy_exists( 'property_action_category' ) ) {
            return '';
        }
        $terms = get_the_terms( $property_id, 'property_action_category' );
        if ( ! is_array( $terms ) || $terms === array() ) {
            return '';
        }
        return $terms[0]->name;
    }

    /**
     * Selected stay titles for a group request.
     *
     * @param int $request_id Request post ID.
     * @return string Titles or empty string.
     */
    private static function request_stays( $request_id ) {
        $titles = array();
        foreach ( (array) get_post_meta( $request_id, '_ssc_selected', true ) as $room_id ) {
            $room_id = intval( $room_id );
            if ( $room_id > 0 && get_post_status( $room_id ) ) {
                $titles[] = get_the_title( $room_id );
            }
        }
        return implode( ', ', $titles );
    }

    /**
     * Meta value as display text.
     *
     * @param int    $post_id Post ID.
     * @param string $key     Meta key.
     * @return string Value or empty string.
     */
    private static function meta_text( $post_id, $key ) {
        $v = get_post_meta( intval( $post_id ), $key, true );
        return ( '' === trim( (string) $v ) ) ? '' : (string) $v;
    }

    /**
     * Raw Y-m-d (or display) date as-is; theme stores display form.
     *
     * @param mixed $raw Raw meta.
     * @return string Date or empty string.
     */
    private static function display_date( $raw ) {
        return ( '' === trim( (string) $raw ) ) ? '' : (string) $raw;
    }

    /**
     * Open the branded receipt shell.
     *
     * @param string $title Document title.
     * @return string Opening HTML.
     */
    private static function shell_open( $title ) {
        $brand = esc_html( get_bloginfo( 'name' ) );
        $html = self::SHELL_MARKER . '<div style="background:#f7f7f7;padding:24px 0;font-family:Arial,sans-serif;">';
        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;">';
        $html .= '<tr><td style="padding:24px;border-bottom:3px solid #137699;">';
        $html .= '<div style="font-size:26px;font-weight:700;color:#111;">' . esc_html( $title ) . '</div>';
        $html .= '<div style="font-size:13px;color:#5d6475;margin-top:4px;">' . esc_html( $brand ) . ' · ' . esc_html__( 'Please present a copy of this confirmation upon check-in.', 'staysuite-companion' ) . '</div>';
        $html .= '</td></tr><tr><td style="padding:24px;">';
        return $html;
    }

    /**
     * Label/value fact table, skipping empty values.
     *
     * @param array<string,string> $facts Rows.
     * @return string Table HTML.
     */
    private static function fact_table( $facts ) {
        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">';
        foreach ( $facts as $label => $value ) {
            if ( $value === '' ) {
                continue;
            }
            $html .= '<tr><td style="padding:7px 12px 7px 0;font-size:14px;color:#5d6475;white-space:nowrap;vertical-align:top;">' . esc_html( $label ) . '</td>';
            $html .= '<td style="padding:7px 0;font-size:14px;font-weight:700;color:#111;vertical-align:top;">' . esc_html( $value ) . '</td></tr>';
        }
        return $html . '</table>';
    }

    /**
     * Booked-and-payable-through block.
     *
     * @return string Block HTML.
     */
    private static function through_block() {
        return '<div style="margin-top:20px;padding:14px 16px;background:#f1f5f9;border-radius:8px;font-size:13px;color:#334155;">'
            . '<strong>' . esc_html__( 'Booked through', 'staysuite-companion' ) . ':</strong> '
            . esc_html( get_bloginfo( 'name' ) ) . ' · ' . esc_html( home_url( '/' ) )
            . '</div>';
    }

    /**
     * Shared receipt notes from settings.
     *
     * @param int    $ref_id Reference post ID (booking or request).
     * @param string $kind   Reference kind: booking|request.
     * @return string Block HTML or empty string.
     */
    private static function notes_block( $ref_id = 0, $kind = '' ) {
        $notes = Settings::get( 'confirmation_notes' );
        /**
         * Filter receipt notes (Pro: per-hotel override).
         *
         * @param string $notes  Notes text.
         * @param int    $ref_id Reference post ID.
         * @param string $kind   Reference kind: booking|request.
         */
        $notes = (string) apply_filters( 'ssc_confirmation_notes', $notes, intval( $ref_id ), $kind );
        if ( $notes === '' ) {
            return '';
        }
        return '<div style="margin-top:20px;font-size:13px;color:#5d6475;line-height:1.6;"><strong style="color:#111;">'
            . esc_html__( 'Good to know', 'staysuite-companion' ) . '</strong><br>' . nl2br( esc_html( $notes ) ) . '</div>';
    }

    /**
     * Close the receipt shell.
     *
     * @return string Closing HTML.
     */
    private static function shell_close() {
        return '</td></tr></table></div>';
    }
}
