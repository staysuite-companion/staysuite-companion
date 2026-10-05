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
     * Every primitive capability maps to edit_posts, the same bar as
     * the rest of the StaySuite admin. manage_options would lock it to
     * administrators only, but several sites also run broken
     * manage_options mappings mid-debug and lose the screen entirely.
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
					'edit_post'              => 'edit_posts',
					'read_post'              => 'edit_posts',
					'delete_post'            => 'edit_posts',
					'edit_posts'             => 'edit_posts',
					'edit_others_posts'      => 'edit_posts',
					'delete_posts'           => 'edit_posts',
					'publish_posts'          => 'edit_posts',
					'read_private_posts'     => 'edit_posts',
					'delete_private_posts'   => 'edit_posts',
					'delete_published_posts' => 'edit_posts',
					'delete_others_posts'    => 'edit_posts',
					'edit_private_posts'     => 'edit_posts',
					'edit_published_posts'   => 'edit_posts',
					'create_posts'           => 'edit_posts',
				),
				'map_meta_cap' => false,
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
        $get = static function ( $key ) use ( $post ) {
            $v = get_post_meta( $post->ID, '_ssc_' . $key, true );
            return ( '' === trim( (string) $v ) ) ? null : $v;
        };
        $status = self::get_status( $post->ID );
        $status_colors = array(
            'pending'   => array( '#fff7ed', '#c2410c' ),
            'quoted'    => array( '#eff6ff', '#1d4ed8' ),
            'confirmed' => array( '#ecfdf5', '#047857' ),
            'cancelled' => array( '#fef2f2', '#b91c1c' ),
        );
        list( $status_bg, $status_fg ) = isset( $status_colors[ $status ] ) ? $status_colors[ $status ] : $status_colors['pending'];
        $location = $get( 'location_text' );
        if ( null === $location ) {
            $location = $get( 'city' );
        }
        print '<style>
        .ssc-req{font-family:inherit}
        .ssc-req-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:14px 0}
        @media(max-width:900px){.ssc-req-grid{grid-template-columns:repeat(2,1fr)}}
        .ssc-req-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px}
        .ssc-req-card b{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:4px}
        .ssc-req-card span{font-size:15px;font-weight:700;color:#0f172a}
        .ssc-req-section{margin:16px 0 6px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#334155}
        .ssc-req-pill{display:inline-block;padding:4px 12px;border-radius:999px;font-weight:700;font-size:12px;background:' . esc_attr( $status_bg ) . ';color:' . esc_attr( $status_fg ) . '}
        .ssc-req-list{margin:0 0 10px 0;padding:0;list-style:none}
        .ssc-req-list li{padding:4px 0}
        .ssc-req-list a{text-decoration:none;font-weight:600}
        .ssc-req-muted{color:#94a3b8;font-style:italic}
        .ssc-req-contact{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
        @media(max-width:900px){.ssc-req-contact{grid-template-columns:1fr}}
        .ssc-req-reqbox{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;white-space:pre-wrap}
        </style>';
        print '<div class="ssc-req">';
        print '<p><span class="ssc-req-pill">' . esc_html( self::STATUSES[ $status ] ) . '</span></p>';
        print '<div class="ssc-req-grid">';
        $cards = array(
            __( 'Location', 'staysuite-companion' ) => null === $location ? '—' : $location,
            __( 'Check in', 'staysuite-companion' )  => null === $get( 'check_in' ) ? '—' : $get( 'check_in' ),
            __( 'Check out', 'staysuite-companion' ) => null === $get( 'check_out' ) ? '—' : $get( 'check_out' ),
            __( 'Rooms', 'staysuite-companion' )     => null === $get( 'rooms' ) ? '—' : $get( 'rooms' ),
            __( 'Guests', 'staysuite-companion' )    => null === $get( 'guests' ) ? '—' : $get( 'guests' ),
            __( 'Male / Female', 'staysuite-companion' ) => ( (int) $get( 'male' ) + (int) $get( 'female' ) ) > 0 ? ( (int) $get( 'male' ) . ' / ' . (int) $get( 'female' ) ) : '—',
            __( 'Budget min', 'staysuite-companion' ) => null === $get( 'budget_min' ) ? '—' : $get( 'budget_min' ),
            __( 'Budget max', 'staysuite-companion' ) => null === $get( 'budget_max' ) ? '—' : $get( 'budget_max' ),
        );
        foreach ( $cards as $label => $val ) {
            print '<div class="ssc-req-card"><b>' . esc_html( $label ) . '</b><span>' . esc_html( (string) $val ) . '</span></div>';
        }
        print '</div>';
        print '<div class="ssc-req-section">' . esc_html__( 'Contact', 'staysuite-companion' ) . '</div>';
        print '<div class="ssc-req-contact">';
        print '<div class="ssc-req-card"><b>' . esc_html__( 'Name', 'staysuite-companion' ) . '</b><span>' . esc_html( null === $get( 'name' ) ? '—' : $get( 'name' ) ) . '</span></div>';
        print '<div class="ssc-req-card"><b>' . esc_html__( 'Email', 'staysuite-companion' ) . '</b><span>' . esc_html( null === $get( 'email' ) ? '—' : $get( 'email' ) ) . '</span></div>';
        print '<div class="ssc-req-card"><b>' . esc_html__( 'Phone', 'staysuite-companion' ) . '</b><span>' . esc_html( null === $get( 'phone' ) ? '—' : $get( 'phone' ) ) . '</span></div>';
        print '</div>';
        $reqs = $get( 'requirements' );
        if ( null !== $reqs ) {
            print '<div class="ssc-req-section">' . esc_html__( 'Extra requirements', 'staysuite-companion' ) . '</div>';
            print '<div class="ssc-req-reqbox">' . esc_html( $reqs ) . '</div>';
        }
        $matched = (array) get_post_meta( $post->ID, '_ssc_matched', true );
        if ( $matched !== array() ) {
            print '<div class="ssc-req-section">' . esc_html__( 'Suggested properties', 'staysuite-companion' ) . '</div>';
            print '<ul class="ssc-req-list">';
            foreach ( $matched as $room_id ) {
                $room_id = intval( $room_id );
                if ( get_post_status( $room_id ) ) {
                    print '<li><a href="' . esc_url( get_edit_post_link( $room_id ) ) . '">' . esc_html( get_the_title( $room_id ) ) . '</a></li>';
                }
            }
            print '</ul>';
        }
        $selected = array_map( 'intval', (array) get_post_meta( $post->ID, '_ssc_selected', true ) );
        $chosen = array();
        foreach ( $selected as $room_id ) {
            if ( get_post_status( $room_id ) ) {
                $chosen[] = '<a href="' . esc_url( get_edit_post_link( $room_id ) ) . '">' . esc_html( get_the_title( $room_id ) ) . '</a>';
            }
        }
        print '<div class="ssc-req-section">' . esc_html__( 'Selected stays', 'staysuite-companion' ) . '</div>';
        print ! empty( $chosen ) ? '<ul class="ssc-req-list"><li>' . implode( '</li><li>', $chosen ) . '</li></ul>' : '<p class="ssc-req-muted">' . esc_html__( 'None — general quote', 'staysuite-companion' ) . '</p>';
        $selection = self::selection_rows( intval( $post->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rows escape every value when built.
        if ( '' !== $selection ) {
            print '<table class="form-table" style="margin-top:12px">' . $selection . '</table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Selection rows escape their own values.
        }
        print '<hr style="margin:18px 0;border:0;border-top:1px solid #e2e8f0;">';
        print '<p><label for="ssc_request_status"><strong>' . esc_html__( 'Update status', 'staysuite-companion' ) . '</strong></label> ';
        print '<select id="ssc_request_status" name="ssc_request_status">';
        foreach ( self::STATUSES as $slug => $label ) {
            print '<option value="' . esc_attr( $slug ) . '" ' . selected( self::get_status( $post->ID ), $slug, false ) . '>'
                . esc_html( $label ) . '</option>';
        }
        print '</select></p>';
        print '</div>';
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
