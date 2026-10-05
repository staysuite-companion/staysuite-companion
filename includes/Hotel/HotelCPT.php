<?php
/**
 * Hotel custom post type and hotel-level meta.
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
 * Registers the hotel post type and its details meta box.
 */
class HotelCPT {

    /**
     * Post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'ssc_hotel';

    /**
     * Archive rewrite slug.
     *
     * @var string
     */
    const ARCHIVE_SLUG = 'hotels';

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( __CLASS__, 'register' ), 5 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta' ) );
    }

    /**
     * Register the hotel post type.
     *
     * @return void
     */
    public static function register() {
        register_post_type(
            self::POST_TYPE, array(
				'labels' => array(
					'name'               => esc_html__( 'Hotels', 'staysuite-companion' ),
					'singular_name'      => esc_html__( 'Hotel', 'staysuite-companion' ),
					'add_new'            => esc_html__( 'Add New', 'staysuite-companion' ),
					'add_new_item'       => esc_html__( 'Add New Hotel', 'staysuite-companion' ),
					'edit_item'          => esc_html__( 'Edit Hotel', 'staysuite-companion' ),
					'new_item'           => esc_html__( 'New Hotel', 'staysuite-companion' ),
					'view_item'          => esc_html__( 'View Hotel', 'staysuite-companion' ),
					'search_items'       => esc_html__( 'Search Hotels', 'staysuite-companion' ),
					'not_found'          => esc_html__( 'No hotels found', 'staysuite-companion' ),
					'not_found_in_trash' => esc_html__( 'No hotels found in Trash', 'staysuite-companion' ),
					'all_items'          => esc_html__( 'All Hotels', 'staysuite-companion' ),
				),
				'public'        => true,
				'has_archive'   => true,
				'rewrite'       => array(
					'slug' => self::ARCHIVE_SLUG,
					'with_front' => false,
				),
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-building',
				'menu_position' => 25,
            )
        );
    }

    /**
     * Register the hotel details meta box.
     *
     * @return void
     */
    public function add_meta_box() {
        add_meta_box(
            'ssc_hotel_details',
            esc_html__( 'Hotel Details', 'staysuite-companion' ),
            array( $this, 'render_meta_box' ),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Render the hotel details meta box.
     *
     * @param WP_Post $post Current hotel post.
     * @return void
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'ssc_hotel_meta', 'ssc_hotel_meta_nonce' );
        $city    = get_post_meta( $post->ID, '_ssc_city', true );
        $address = get_post_meta( $post->ID, '_ssc_address', true );
        $phone   = get_post_meta( $post->ID, '_ssc_phone', true );
        ?>
        <p>
            <label for="ssc_hotel_city"><strong><?php esc_html_e( 'City', 'staysuite-companion' ); ?></strong></label><br>
            <?php if ( taxonomy_exists( 'property_city' ) ) : ?>
                <?php
                wp_dropdown_categories(
                    array(
						'taxonomy'          => 'property_city',
						'hide_empty'        => false,
						'name'              => 'ssc_hotel_city',
						'id'                => 'ssc_hotel_city',
						'selected'          => sanitize_title( $city ),
						'value_field'       => 'slug',
						'show_option_none'  => esc_html__( '— Select city —', 'staysuite-companion' ),
						'option_none_value' => '',
						'class'             => 'widefat',
                    )
                );
                ?>
            <?php else : ?>
                <input type="text" id="ssc_hotel_city" name="ssc_hotel_city" class="widefat" value="<?php echo esc_attr( $city ); ?>">
            <?php endif; ?>
        </p>
        <p>
            <label for="ssc_hotel_address"><strong><?php esc_html_e( 'Address', 'staysuite-companion' ); ?></strong></label><br>
            <input type="text" id="ssc_hotel_address" name="ssc_hotel_address" class="widefat" value="<?php echo esc_attr( $address ); ?>">
        </p>
    <p>
        <label for="ssc_hotel_phone"><strong><?php esc_html_e( 'Phone', 'staysuite-companion' ); ?></strong></label><br>
        <input type="text" id="ssc_hotel_phone" name="ssc_hotel_phone" class="widefat" value="<?php echo esc_attr( $phone ); ?>">
    </p>
    <p>
        <label for="ssc_hotel_featured">
            <input type="checkbox" id="ssc_hotel_featured" name="ssc_hotel_featured" value="1" <?php checked( get_post_meta( $post->ID, '_ssc_featured', true ), '1' ); ?>>
            <?php esc_html_e( 'Featured hotel (shows in featured rows)', 'staysuite-companion' ); ?>
        </label>
    </p>
        <?php
    }

    /**
     * Save hotel details meta.
     *
     * @param int $post_id Post being saved.
     * @return void
     */
    public function save_meta( $post_id ) {
        $nonce = isset( $_POST['ssc_hotel_meta_nonce'] ) && is_string( $_POST['ssc_hotel_meta_nonce'] )
            ? sanitize_key( wp_unslash( $_POST['ssc_hotel_meta_nonce'] ) )
            : '';
        if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'ssc_hotel_meta' ) ) {
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
        update_post_meta( $post_id, '_ssc_city', isset( $_POST['ssc_hotel_city'] ) ? sanitize_title( wp_unslash( $_POST['ssc_hotel_city'] ) ) : '' );
        update_post_meta( $post_id, '_ssc_address', isset( $_POST['ssc_hotel_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ssc_hotel_address'] ) ) : '' );
        update_post_meta( $post_id, '_ssc_phone', isset( $_POST['ssc_hotel_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ssc_hotel_phone'] ) ) : '' );
        if ( isset( $_POST['ssc_hotel_featured'] ) && $_POST['ssc_hotel_featured'] === '1' ) {
            update_post_meta( $post_id, '_ssc_featured', '1' );
        } else {
            delete_post_meta( $post_id, '_ssc_featured' );
        }
        Repository::sync_hotel_data( $post_id );
    }
}
