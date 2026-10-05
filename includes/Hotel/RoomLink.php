<?php
/**
 * Room -> hotel linking on estate_property.
 *
 * @package StaySuite\Companion\Hotel
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Hotel;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Meta box, saving and list column linking rooms to hotels.
 */
class RoomLink {

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta' ) );
        add_filter( 'manage_estate_property_posts_columns', array( $this, 'add_list_column' ) );
        add_action( 'manage_estate_property_posts_custom_column', array( $this, 'render_list_column' ), 10, 2 );
        add_action( 'quick_edit_custom_box', array( $this, 'render_quick_edit' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
    }

    /**
     * Register the hotel selector meta box on rooms.
     *
     * Side/high lands it right after Publish.
     *
     * @return void
     */
    public function add_meta_box() {
        if ( ! post_type_exists( 'estate_property' ) ) {
            return;
        }
        add_meta_box(
            'ssc_room_hotel',
            esc_html__( 'Hotel (StaySuite)', 'staysuite-companion' ),
            array( $this, 'render_meta_box' ),
            'estate_property',
            'side',
            'high'
        );
    }

    /**
     * Render the hotel selector.
     *
     * @param WP_Post $post Current room post.
     * @return void
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'ssc_room_hotel', 'ssc_room_hotel_nonce' );
        $current = Repository::get_room_hotel_id( $post->ID );
        $hotels = Repository::get_all_hotels();
        $original = Repository::get_original_price( $post->ID );
        ?>
        <p>
            <label for="ssc_room_hotel_id"><strong><?php esc_html_e( 'Belongs to hotel', 'staysuite-companion' ); ?></strong></label><br>
            <select id="ssc_room_hotel_id" name="ssc_room_hotel_id" class="widefat">
                <option value="0"><?php esc_html_e( '— Standalone listing —', 'staysuite-companion' ); ?></option>
                <?php foreach ( $hotels as $hotel_id => $hotel_title ) : ?>
                    <option value="<?php echo intval( $hotel_id ); ?>" <?php selected( $current, intval( $hotel_id ) ); ?>>
                        <?php echo esc_html( $hotel_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="ssc_room_original_price"><strong><?php esc_html_e( 'Original price (before discount)', 'staysuite-companion' ); ?></strong></label><br>
            <input type="number" id="ssc_room_original_price" name="ssc_room_original_price" class="widefat" min="0" step="any" placeholder="e.g. 2500" value="<?php echo $original > 0 ? esc_attr( $original ) : ''; ?>">
        </p>
        <p class="description">
            <?php esc_html_e( 'Rooms with a hotel show a "View hotel" link and appear on the hotel page. When the original price is higher, it shows struck through next to the booking price.', 'staysuite-companion' ); ?>
        </p>
        <?php
    }

    /**
     * Save the room -> hotel link (and original price on full edit).
     *
     * Quick Edit carries no meta-box nonce, so it authenticates with the
     * inline-edit nonce and saves the hotel only.
     *
     * @param int $post_id Post being saved.
     * @return void
     */
    public function save_meta( $post_id ) {
        $room_nonce = isset( $_POST['ssc_room_hotel_nonce'] ) && is_string( $_POST['ssc_room_hotel_nonce'] )
            ? sanitize_key( wp_unslash( $_POST['ssc_room_hotel_nonce'] ) )
            : '';
        $inline_nonce = isset( $_POST['_inline_edit'] ) && is_string( $_POST['_inline_edit'] )
            ? sanitize_key( wp_unslash( $_POST['_inline_edit'] ) )
            : '';
        $full_edit = '' !== $room_nonce && wp_verify_nonce( $room_nonce, 'ssc_room_hotel' );
        $quick_edit = '' !== $inline_nonce && wp_verify_nonce( $inline_nonce, 'inlineeditnonce' );
        if ( ! $full_edit && ! $quick_edit ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( get_post_type( $post_id ) !== 'estate_property' ) {
            return;
        }
        $hotel_id = isset( $_POST['ssc_room_hotel_id'] ) ? intval( $_POST['ssc_room_hotel_id'] ) : 0;
        if ( $hotel_id > 0 && ( get_post_type( $hotel_id ) !== HotelCPT::POST_TYPE || get_post_status( $hotel_id ) !== 'publish' ) ) {
            $hotel_id = 0;
        }
        $previous_hotel_id = Repository::get_room_hotel_id( $post_id );
        if ( $hotel_id > 0 ) {
            update_post_meta( $post_id, Repository::ROOM_HOTEL_META, $hotel_id );
        } else {
            delete_post_meta( $post_id, Repository::ROOM_HOTEL_META );
        }
        if ( $previous_hotel_id > 0 && $previous_hotel_id !== $hotel_id ) {
            Repository::sync_hotel_data( $previous_hotel_id );
        }
        if ( $hotel_id > 0 ) {
            Repository::sync_hotel_data( $hotel_id );
        }
        if ( $full_edit && isset( $_POST['ssc_room_original_price'] ) ) {
            $original = floatval( $_POST['ssc_room_original_price'] );
            if ( $original > 0 ) {
                update_post_meta( $post_id, Repository::ROOM_ORIGINAL_PRICE_META, $original );
            } else {
                delete_post_meta( $post_id, Repository::ROOM_ORIGINAL_PRICE_META );
            }
        }
    }

    /**
     * Add the hotel column to the rooms list table.
     *
     * @param array<string,string> $columns List table columns.
     * @return array<string,string> Columns with hotel added.
     */
    public function add_list_column( $columns ) {
        $columns['ssc_hotel'] = esc_html__( 'Hotel', 'staysuite-companion' );
        return $columns;
    }

    /**
     * Render the hotel column cell.
     *
     * @param string $column  Column slug.
     * @param int    $post_id Room post ID.
     * @return void
     */
    public function render_list_column( $column, $post_id ) {
        if ( $column !== 'ssc_hotel' ) {
            return;
        }
        $hotel_id = Repository::get_room_hotel_id( $post_id );
        print '<span class="ssc-hotel-cell" data-ssc-hotel-id="' . intval( $hotel_id ) . '">';
        if ( $hotel_id > 0 && get_post_status( $hotel_id ) === 'publish' ) {
            print '<a href="' . esc_url( get_edit_post_link( $hotel_id ) ) . '">' . esc_html( get_the_title( $hotel_id ) ) . '</a>';
        } else {
            print '<span aria-hidden="true">—</span>';
        }
        print '</span>';
    }

    /**
     * Render the hotel selector inside Quick Edit.
     *
     * @param string $column_name Current column slug.
     * @param string $post_type   Current post type.
     * @return void
     */
    public function render_quick_edit( $column_name, $post_type ) {
        if ( $column_name !== 'ssc_hotel' || $post_type !== 'estate_property' ) {
            return;
        }
        $hotels = Repository::get_all_hotels();
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-group">
                <label>
                    <span class="title"><?php esc_html_e( 'Hotel', 'staysuite-companion' ); ?></span>
                    <select name="ssc_room_hotel_id">
                        <option value="0"><?php esc_html_e( '— Standalone —', 'staysuite-companion' ); ?></option>
                        <?php foreach ( $hotels as $hotel_id => $hotel_title ) : ?>
                            <option value="<?php echo intval( $hotel_id ); ?>"><?php echo esc_html( $hotel_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Enqueue the Quick Edit helper on the rooms list table.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin( $hook ) {
        if ( $hook !== 'edit.php' ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->id !== 'edit-estate_property' ) {
            return;
        }
        $path    = SSC_PATH . 'assets/js/ssc-quick-edit.js';
        $version = SSC_VERSION;
        if ( file_exists( $path ) ) {
            $hash = md5_file( $path );
            if ( is_string( $hash ) ) {
                $version = substr( $hash, 0, 12 );
            }
        }
        wp_enqueue_script( 'ssc-quick-edit', SSC_URL . 'assets/js/ssc-quick-edit.js', array( 'jquery', 'inline-edit-post' ), $version, true );
    }
}
