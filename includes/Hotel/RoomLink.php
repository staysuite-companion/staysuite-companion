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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_tab' ) );
        // Room prices change outside the edit screen too (imports, bulk
        // tools, REST, direct meta writes): keep the hotel's stored min
        // in step no matter where the change comes from.
        add_action( 'added_post_meta', array( $this, 'sync_on_price_change' ), 10, 4 );
        add_action( 'updated_post_meta', array( $this, 'sync_on_price_change' ), 10, 4 );
        add_action( 'deleted_post_meta', array( $this, 'sync_on_price_change' ), 10, 4 );
    }

    /**
     * Register the hotel selector meta box on rooms.
     *
     * Side/high lands it right after Publish. Skipped when the theme's
     * tabbed Property Details box is present — the fields live in a
     * StaySuite tab there instead (see enqueue_tab()).
     *
     * @return void
     */
    public function add_meta_box() {
        if ( ! post_type_exists( 'estate_property' ) ) {
            return;
        }
        if ( self::theme_tabbed_box_present() ) {
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
     * Whether the theme's tabbed Property Details box is on the screen.
     *
     * WpRentals renders its room fields as tabs with hardcoded markup and
     * no registration seam, so presence is detected through its render
     * callback rather than a filter.
     *
     * @return bool True when the StaySuite fields belong in a tab.
     */
    private static function theme_tabbed_box_present() {
        return function_exists( 'estate_tabbed_interface' );
    }

    /**
     * Enqueue the script that adds the StaySuite tab to Property Details.
     *
     * The tab markup and values ship as localized data; the script appends
     * a nav item plus panel to the theme's hardcoded tab skeleton and binds
     * the same active_tab switching the theme uses.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_tab( $hook ) {
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->post_type !== 'estate_property' ) {
            return;
        }
        if ( ! self::theme_tabbed_box_present() ) {
            return;
        }
        // Display context only; the save handler re-checks capabilities.
        global $post;
        $post_id = ( $post instanceof WP_Post ) ? intval( $post->ID ) : 0;
        $hotels = array();
        foreach ( Repository::get_all_hotels() as $hotel_id => $hotel_title ) {
            $hotels[] = array(
                'id'    => intval( $hotel_id ),
                'title' => $hotel_title,
            );
        }
        $original = Repository::get_original_price( $post_id );
        $handle = 'ssc-hotel-tab';
        $path = SSC_PATH . 'assets/js/ssc-hotel-tab.js';
        $version = SSC_VERSION;
        if ( file_exists( $path ) ) {
            $hash = md5_file( $path );
            if ( is_string( $hash ) ) {
                $version = substr( $hash, 0, 12 );
            }
        }
        wp_enqueue_script( $handle, SSC_URL . 'assets/js/ssc-hotel-tab.js', array(), $version, true );
        wp_localize_script(
            $handle,
            'sscHotelTab',
            array(
                'hotels'   => $hotels,
                'current'  => Repository::get_room_hotel_id( $post_id ),
                'original' => $original > 0 ? $original : '',
                'nonce'    => wp_create_nonce( 'ssc_room_hotel' ),
                'i18n'     => array(
                    'tab'         => esc_html__( 'Hotel (StaySuite)', 'staysuite-companion' ),
                    'belongs'     => esc_html__( 'Belongs to hotel', 'staysuite-companion' ),
                    'standalone'  => esc_html__( '— Standalone listing —', 'staysuite-companion' ),
                    'original'    => esc_html__( 'Original price (before discount)', 'staysuite-companion' ),
                    'description' => esc_html__( 'Rooms with a hotel show a "View hotel" link and appear on the hotel page. When the original price is higher, it shows struck through next to the booking price.', 'staysuite-companion' ),
                ),
            )
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
     * Re-sync the linked hotel whenever a room's booking price changes.
     *
     * The hotel card shows a stored min price, so any property_price write
     * — edit screen, import, bulk tool, REST — must refresh it, or the
     * hotel keeps showing a stale "From" value. Hotel posts carry
     * property_price too but are a different post type, so the sync's own
     * write cannot recurse back here.
     *
     * @param int    $meta_id    Meta row ID.
     * @param int    $post_id    Post the meta belongs to.
     * @param string $meta_key   Meta key that changed.
     * @param mixed  $meta_value New meta value.
     * @return void
     */
    public function sync_on_price_change( $meta_id, $post_id, $meta_key, $meta_value ) {
        unset( $meta_id, $meta_value );
        if ( $meta_key !== 'property_price' ) {
            return;
        }
        if ( get_post_type( $post_id ) !== 'estate_property' ) {
            return;
        }
        $hotel_id = Repository::get_room_hotel_id( $post_id );
        if ( $hotel_id > 0 ) {
            Repository::sync_hotel_data( $hotel_id );
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
