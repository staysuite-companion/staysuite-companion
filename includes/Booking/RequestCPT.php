<?php
/**
 * Group booking request storage and admin workflow.
 *
 * @package StaySuite\Companion\Booking
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Booking;

use StaySuite\Companion\Hotel\Repository;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stores group quote requests and tracks their status.
 */
class RequestCPT {

    /**
     * Post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'ssc_group_request';

    /**
     * Request statuses.
     *
     * @var array<string,string>
     */
    const STATUSES = array(
        'pending'   => 'Pending',
        'quoted'    => 'Quoted',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
    );

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( __CLASS__, 'register' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_status' ) );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_list_columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_list_column' ), 10, 2 );
    }

    /**
     * Register the request post type (admin UI only).
     *
     * Requests hold visitor names, emails and phone numbers, so every
     * primitive capability maps to manage_options: administrators only.
     * The parent StaySuite menu already requires manage_options, which
     * keeps the screen out of reach for lower roles entirely.
     *
     * @return void
     */
    public static function register() {
        register_post_type(
            self::POST_TYPE, array(
				'labels' => array(
					'name'               => esc_html__( 'Group Requests', 'staysuite-companion' ),
					'singular_name'      => esc_html__( 'Group Request', 'staysuite-companion' ),
					'add_new'            => esc_html__( 'Add New', 'staysuite-companion' ),
					'add_new_item'       => esc_html__( 'Add New Request', 'staysuite-companion' ),
					'edit_item'          => esc_html__( 'Edit Request', 'staysuite-companion' ),
					'search_items'       => esc_html__( 'Search Requests', 'staysuite-companion' ),
					'not_found'          => esc_html__( 'No requests found', 'staysuite-companion' ),
					'all_items'          => esc_html__( 'All Requests', 'staysuite-companion' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'ssc-staysuite',
				'supports'     => array( 'title' ),
				'show_in_rest' => false,
				'capabilities' => array(
					'edit_post'              => 'manage_options',
					'read_post'              => 'manage_options',
					'delete_post'            => 'manage_options',
					'edit_posts'             => 'manage_options',
					'edit_others_posts'      => 'manage_options',
					'delete_posts'           => 'manage_options',
					'publish_posts'          => 'manage_options',
					'read_private_posts'     => 'manage_options',
					'delete_private_posts'   => 'manage_options',
					'delete_published_posts' => 'manage_options',
					'delete_others_posts'    => 'manage_options',
					'edit_private_posts'     => 'manage_options',
					'edit_published_posts'   => 'manage_options',
					'create_posts'           => 'manage_options',
				),
				'map_meta_cap' => true,
            )
        );
    }

    /**
     * Get a request's status.
     *
     * @param int $request_id Request post ID.
     * @return string Status slug, defaults to pending.
     */
    public static function get_status( $request_id ) {
        $status = get_post_meta( intval( $request_id ), '_ssc_status', true );
        return isset( self::STATUSES[ $status ] ) ? $status : 'pending';
    }

    /**
     * Register the details / status meta box.
     *
     * @return void
     */
    public function add_meta_box() {
        add_meta_box(
            'ssc_request_details',
            esc_html__( 'Request Details', 'staysuite-companion' ),
            array( $this, 'render_meta_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Render request details and status selector.
     *
     * @param WP_Post $post Current request post.
     * @return void
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'ssc_request_status', 'ssc_request_status_nonce' );
        $fields = array(
            'city'         => __( 'City', 'staysuite-companion' ),
            'check_in'     => __( 'Check in', 'staysuite-companion' ),
            'check_out'    => __( 'Check out', 'staysuite-companion' ),
            'rooms'        => __( 'Rooms needed', 'staysuite-companion' ),
            'guests'       => __( 'Guests', 'staysuite-companion' ),
            'male'         => __( 'Male', 'staysuite-companion' ),
            'female'       => __( 'Female', 'staysuite-companion' ),
            'budget_min'   => __( 'Budget min', 'staysuite-companion' ),
            'budget_max'   => __( 'Budget max', 'staysuite-companion' ),
            'name'         => __( 'Contact name', 'staysuite-companion' ),
            'email'        => __( 'Contact email', 'staysuite-companion' ),
            'phone'        => __( 'Contact phone', 'staysuite-companion' ),
            'requirements' => __( 'Extra requirements', 'staysuite-companion' ),
        );
        print '<table class="form-table">';
        foreach ( $fields as $key => $label ) {
            $value = get_post_meta( $post->ID, '_ssc_' . $key, true );
            print '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
        }
        $matched = (array) get_post_meta( $post->ID, '_ssc_matched', true );
        $links = array();
        foreach ( $matched as $room_id ) {
            $room_id = intval( $room_id );
            if ( get_post_status( $room_id ) ) {
                $links[] = '<a href="' . esc_url( get_edit_post_link( $room_id ) ) . '">' . esc_html( get_the_title( $room_id ) ) . '</a>';
            }
        }
        print '<tr><th>' . esc_html__( 'Suggested properties', 'staysuite-companion' ) . '</th><td>'
            . ( ! empty( $links ) ? implode( '<br>', $links ) : esc_html__( 'None', 'staysuite-companion' ) ) . '</td></tr>';
        print self::selection_rows( intval( $post->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rows escape every value when built.
        print '</table>';
        print '<p><label for="ssc_request_status"><strong>' . esc_html__( 'Status', 'staysuite-companion' ) . '</strong></label> ';
        print '<select id="ssc_request_status" name="ssc_request_status">';
        foreach ( self::STATUSES as $slug => $label ) {
            print '<option value="' . esc_attr( $slug ) . '" ' . selected( self::get_status( $post->ID ), $slug, false ) . '>'
                . esc_html( $label ) . '</option>';
        }
        print '</select></p>';
    }

    /**
     * Selected-rooms rows for group-quote requests (Pro).
     *
     * Reads the Pro-persisted selection (_ssc_pro_room_ids,
     * _ssc_pro_room_qty, _ssc_pro_total_estimate); renders nothing when the
     * request came from the plain group form.
     *
     * @param int $request_id Request post ID.
     * @return string Table rows, empty when no selection exists.
     */
    private static function selection_rows( $request_id ) {
        $ids = array_map( 'intval', (array) get_post_meta( $request_id, '_ssc_pro_room_ids', true ) );
        $ids = array_values( array_filter( $ids ) );
        if ( $ids === array() ) {
            return '';
        }
        $qty = (array) get_post_meta( $request_id, '_ssc_pro_room_qty', true );
        $lines = array();
        foreach ( $ids as $room_id ) {
            $count = isset( $qty[ $room_id ] ) ? max( 1, intval( $qty[ $room_id ] ) ) : 1;
            $title = get_post_status( $room_id ) ? get_the_title( $room_id ) : __( '(removed listing)', 'staysuite-companion' );
            $lines[] = sprintf(
                /* translators: 1: quantity, 2: room title. */
                esc_html__( '%1$d × %2$s', 'staysuite-companion' ),
                $count,
                $title
            );
        }
        $total = floatval( get_post_meta( $request_id, '_ssc_pro_total_estimate', true ) );
        $html = '<tr><th>' . esc_html__( 'Selected rooms', 'staysuite-companion' ) . '</th><td>'
            . esc_html( implode( ', ', $lines ) ) . '</td></tr>';
        if ( $total > 0 ) {
            // format_price() escapes its own output (symbol via esc_html).
            $html .= '<tr><th>' . esc_html__( 'Estimated total', 'staysuite-companion' ) . '</th><td>'
                . Repository::format_price( $total ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the formatter, see above.
        }
        return $html;
    }

    /**
     * Save the request status.
     *
     * @param int $post_id Post being saved.
     * @return void
     */
    public function save_status( $post_id ) {
        $nonce = isset( $_POST['ssc_request_status_nonce'] ) && is_string( $_POST['ssc_request_status_nonce'] )
            ? sanitize_key( wp_unslash( $_POST['ssc_request_status_nonce'] ) )
            : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'ssc_request_status' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( get_post_type( $post_id ) !== self::POST_TYPE ) {
            return;
        }
        $status = isset( $_POST['ssc_request_status'] ) ? sanitize_key( $_POST['ssc_request_status'] ) : 'pending';
        update_post_meta( $post_id, '_ssc_status', isset( self::STATUSES[ $status ] ) ? $status : 'pending' );
    }

    /**
     * Add admin list columns.
     *
     * @param array<string,string> $columns List table columns.
     * @return array<string,string> Columns with request fields added.
     */
    public function add_list_columns( $columns ) {
        $columns['ssc_city'] = esc_html__( 'City', 'staysuite-companion' );
        $columns['ssc_dates'] = esc_html__( 'Dates', 'staysuite-companion' );
        $columns['ssc_party'] = esc_html__( 'Party', 'staysuite-companion' );
        $columns['ssc_quote'] = esc_html__( 'Quote', 'staysuite-companion' );
        $columns['ssc_status'] = esc_html__( 'Status', 'staysuite-companion' );
        return $columns;
    }

    /**
     * Render admin list column cells.
     *
     * @param string $column  Column slug.
     * @param int    $post_id Request post ID.
     * @return void
     */
    public function render_list_column( $column, $post_id ) {
        switch ( $column ) {
            case 'ssc_city':
                echo esc_html( get_post_meta( $post_id, '_ssc_city', true ) );
                break;
            case 'ssc_dates':
                echo esc_html( trim( get_post_meta( $post_id, '_ssc_check_in', true ) . ' → ' . get_post_meta( $post_id, '_ssc_check_out', true ), ' →' ) );
                break;
            case 'ssc_party':
                printf(
                    /* translators: 1: rooms, 2: guests */
                    esc_html__( '%1$s rooms · %2$s guests', 'staysuite-companion' ),
                    esc_html( get_post_meta( $post_id, '_ssc_rooms', true ) ),
                    esc_html( get_post_meta( $post_id, '_ssc_guests', true ) )
                );
                break;
            case 'ssc_status':
                echo esc_html( self::STATUSES[ self::get_status( $post_id ) ] );
                break;
            case 'ssc_quote':
                echo esc_html( self::quote_summary( intval( $post_id ) ) );
                break;
        }
    }

    /**
     * One-line quote summary for the requests list ("2 rooms · ৳24,000").
     *
     * Empty for plain group-form requests without a room selection.
     *
     * @param int $request_id Request post ID.
     * @return string Summary or empty string.
     */
    private static function quote_summary( $request_id ) {
        $ids = array_filter( array_map( 'intval', (array) get_post_meta( $request_id, '_ssc_pro_room_ids', true ) ) );
        if ( $ids === array() ) {
            return '';
        }
        $qty = (array) get_post_meta( $request_id, '_ssc_pro_room_qty', true );
        $units = 0;
        foreach ( $ids as $room_id ) {
            $units += isset( $qty[ $room_id ] ) ? max( 1, intval( $qty[ $room_id ] ) ) : 1;
        }
        $summary = sprintf(
            /* translators: %d: number of rooms. */
            _n( '%d room', '%d rooms', $units, 'staysuite-companion' ),
            $units
        );
        $total = floatval( get_post_meta( $request_id, '_ssc_pro_total_estimate', true ) );
        if ( $total > 0 ) {
            $summary .= ' · ' . html_entity_decode( wp_strip_all_tags( Repository::format_price( $total ) ), ENT_QUOTES, 'UTF-8' );
        }
        return $summary;
    }
}
