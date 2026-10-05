<?php
/**
 * Single hotel page: gallery cover, facilities, date strip, room cards, map.
 *
 * Served via template_include — the theme is never modified. Booking stays
 * theme-native: each room card links to its room page.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

use StaySuite\Companion\Hotel\Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();
    $hotel_id   = get_the_ID();
    $city_term  = Repository::get_city_term( $hotel_id );
    $address    = get_post_meta( $hotel_id, '_ssc_address', true );
    $phone      = get_post_meta( $hotel_id, '_ssc_phone', true );
    $min_price  = Repository::get_min_price( $hotel_id );
    $rooms      = Repository::get_rooms( $hotel_id, 100 );
    $room_count = Repository::get_room_count( $hotel_id );
    $gallery    = Repository::get_gallery( $hotel_id, 5 );
    $facilities = Repository::get_facilities( $hotel_id );
    $coords     = Repository::ensure_coords( $hotel_id );

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- public date filter, no state change.
    $check_in  = isset( $_GET['check_in'] ) ? sanitize_text_field( wp_unslash( $_GET['check_in'] ) ) : '';
    $check_out = isset( $_GET['check_out'] ) ? sanitize_text_field( wp_unslash( $_GET['check_out'] ) ) : '';
    $guests    = 2;
    if ( isset( $_GET['guest_no'] ) ) {
        $guests = max( 1, intval( $_GET['guest_no'] ) );
    } elseif ( isset( $_GET['guests'] ) ) {
        $guests = max( 1, intval( $_GET['guests'] ) );
    }
    // phpcs:enable
    $has_dates = ( $check_in !== '' && $check_out !== '' );

    $location_bits = array();
    if ( $city_term ) {
        $location_bits[] = $city_term->name;
    }
    if ( $address !== '' ) {
        $location_bits[] = $address;
    }
    ?>

    <div class="container ssc-hotel-page">
        <?php if ( ! empty( $gallery ) ) : ?>
            <div class="ssc-hotel-gallery">
                <?php
                $first_id = array_shift( $gallery );
                $first_src = wp_get_attachment_image_src( $first_id, 'large' );
                ?>
                <div class="ssc-hotel-gallery-main"<?php echo $first_src ? ' style="background-image:url(' . esc_url( $first_src[0] ) . ')"' : ''; ?>></div>
                <?php if ( ! empty( $gallery ) ) : ?>
                    <div class="ssc-hotel-gallery-side">
                        <?php
                        foreach ( array_slice( $gallery, 0, 4 ) as $attachment_id ) :
                            $src = wp_get_attachment_image_src( $attachment_id, 'medium' );
                            if ( ! $src ) {
                                continue;
                            }
                            ?>
                            <div class="ssc-hotel-gallery-thumb" style="background-image:url(<?php echo esc_url( $src[0] ); ?>)"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="ssc-hotel-head">
            <div class="ssc-hotel-head-main">
                <h1 class="ssc-hotel-name"><?php the_title(); ?></h1>
                <?php if ( ! empty( $location_bits ) ) : ?>
                    <div class="ssc-hotel-location"><?php echo esc_html( implode( ' · ', $location_bits ) ); ?></div>
                <?php endif; ?>
                <?php if ( $phone !== '' ) : ?>
                    <div class="ssc-hotel-phone"><?php echo esc_html( $phone ); ?></div>
                <?php endif; ?>
            </div>
            <div class="ssc-hotel-head-side">
                <?php if ( $min_price > 0 ) : ?>
                    <div class="ssc-hotel-min-price">
                        <?php
                        /* translators: %s: starting price */
                        printf( esc_html__( 'Rooms from %s / night', 'staysuite-companion' ), Repository::format_price( $min_price ) );
                        ?>
                    </div>
                <?php endif; ?>
                <div class="ssc-hotel-count">
                    <?php
                    /* translators: %d: number of rooms */
                    printf( esc_html( _n( '%d room', '%d rooms', $room_count, 'staysuite-companion' ) ), intval( $room_count ) );
                    ?>
                </div>
            </div>
        </div>

        <?php if ( get_the_content() !== '' ) : ?>
            <div class="ssc-hotel-description">
                <?php the_content(); ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $facilities ) ) : ?>
            <div class="ssc-hotel-facilities">
                <h2 class="ssc-hotel-section-title"><?php esc_html_e( 'Facilities', 'staysuite-companion' ); ?></h2>
                <ul class="ssc-facility-list">
                    <?php
                    foreach ( $facilities as $facility ) :
                        $icon = Repository::get_feature_icon( $facility );
                        ?>
                        <li class="ssc-facility">
                            <?php if ( $icon !== '' ) : ?>
                                <img class="ssc-facility-icon" src="<?php echo esc_url( $icon ); ?>" alt="" loading="lazy">
                            <?php else : ?>
                                <span class="ssc-facility-dot" aria-hidden="true"></span>
                            <?php endif; ?>
                            <span><?php echo esc_html( $facility->name ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php
        echo \StaySuite\Companion\Blocks\Renderer::render_hotel_search( get_permalink( $hotel_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer.
        ?>

        <h2 class="ssc-hotel-rooms-title">
            <?php
            if ( $has_dates ) {
                /* translators: 1: check-in, 2: check-out */
                printf( esc_html__( 'Rooms (%1$s → %2$s)', 'staysuite-companion' ), esc_html( $check_in ), esc_html( $check_out ) );
            } else {
                esc_html_e( 'Rooms', 'staysuite-companion' );
            }
            ?>
        </h2>

        <?php if ( $rooms->have_posts() ) : ?>
            <div class="ssc-room-cards">
                <?php
                // Context for the theme's card slider (see property_unit.php).
                $ssc_currency       = function_exists( 'wprentals_get_option' ) ? esc_html( wprentals_get_option( 'wp_estate_currency_label_main', '' ) ) : '';
                $ssc_where_currency = function_exists( 'wprentals_get_option' ) ? esc_html( wprentals_get_option( 'wp_estate_where_currency_symbol', '' ) ) : '';
                $ssc_listing_type   = function_exists( 'wprentals_get_option' ) ? wprentals_get_option( 'wp_estate_listing_unit_type', '' ) : '';

                while ( $rooms->have_posts() ) {
                    $rooms->the_post();
                    $room = Repository::get_room_data( get_the_ID() );
                    $available = $has_dates ? Repository::is_available( $room['id'], $check_in, $check_out ) : null;
                    $room_url = add_query_arg(
                        array_filter(
                            array(
								'check_in'       => $check_in,
								'check_out'      => $check_out,
								'guests'         => $guests,
								'check_in_prop'  => Repository::to_display_date( $check_in ),
								'check_out_prop' => Repository::to_display_date( $check_out ),
								'guest_no_prop'  => $guests,
                            )
                        ),
                        $room['url']
                    );
                    ?>
                    <article class="ssc-room-card<?php echo $available === false ? ' ssc-room-unavailable' : ''; ?>">
                        <div class="ssc-room-media">
                            <?php
                            if ( function_exists( 'wpestate_print_property_unit_slider' ) ) {
                                wpestate_print_property_unit_slider( $room['id'], 'yes', $ssc_listing_type, $ssc_currency, $ssc_where_currency, $room_url );
                            }
                            ?>
                        </div>
                        <div class="ssc-room-body">
                            <h3 class="ssc-room-title">
                                <a href="<?php echo esc_url( $room_url ); ?>"><?php echo esc_html( $room['title'] ); ?></a>
                            </h3>
                            <div class="ssc-room-meta">
                                <?php if ( $room['capacity'] > 0 ) : ?>
                                    <span class="ssc-room-capacity">
                                        <?php
                                        /* translators: %d: guest capacity */
                                        printf( esc_html( _n( 'Sleeps %d', 'Sleeps %d', $room['capacity'], 'staysuite-companion' ) ), intval( $room['capacity'] ) );
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ( $available === true ) : ?>
                                    <span class="ssc-room-badge ssc-room-free"><?php esc_html_e( 'Available for your dates', 'staysuite-companion' ); ?></span>
                                <?php elseif ( $available === false ) : ?>
                                    <span class="ssc-room-badge ssc-room-taken"><?php esc_html_e( 'Booked for your dates', 'staysuite-companion' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ( ! empty( $room['features'] ) ) : ?>
                                <ul class="ssc-room-features">
                                    <?php
                                    foreach ( array_slice( $room['features'], 0, 5 ) as $feature ) :
                                        $icon = Repository::get_feature_icon( $feature );
                                        ?>
                                        <li>
                                            <?php if ( $icon !== '' ) : ?>
                                                <img src="<?php echo esc_url( $icon ); ?>" alt="" loading="lazy">
                                            <?php endif; ?>
                                            <span><?php echo esc_html( $feature->name ); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="ssc-room-side">
                            <?php
                            $original = Repository::get_original_price( $room['id'] );
                            if ( $original > $room['price'] ) :
                                ?>
                                <div class="ssc-room-was"><?php echo Repository::format_price( $original ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in formatter. ?></div>
                            <?php endif; ?>
                            <?php if ( $room['price'] > 0 ) : ?>
                                <div class="ssc-room-price"><?php echo Repository::format_price( $room['price'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in formatter. ?></div>
                                <div class="ssc-room-per"><?php esc_html_e( '/ night', 'staysuite-companion' ); ?></div>
                            <?php endif; ?>
                            <a class="ssc-room-cta" href="<?php echo esc_url( $room_url ); ?>"><?php esc_html_e( 'View & Book', 'staysuite-companion' ); ?></a>
                            <?php
                            /**
                             * Extra actions per room card (Pro: Add Room to selection).
                             *
                             * @param int $room_id Room post ID.
                             */
                            do_action( 'ssc_room_card_actions', $room['id'] );
                            ?>
                        </div>
                    </article>
                    <?php
                }
                wp_reset_postdata();
                ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'No rooms are published for this hotel yet.', 'staysuite-companion' ); ?></p>
        <?php endif; ?>

        <?php if ( $coords['lat'] !== 0.0 && $coords['lng'] !== 0.0 ) : ?>
            <div class="ssc-hotel-map-wrap">
                <h2 class="ssc-hotel-section-title"><?php esc_html_e( 'On the Map', 'staysuite-companion' ); ?></h2>
                <div
                    class="ssc-hotel-map"
                    data-lat="<?php echo esc_attr( $coords['lat'] ); ?>"
                    data-lng="<?php echo esc_attr( $coords['lng'] ); ?>"
                    data-title="<?php echo esc_attr( get_the_title( $hotel_id ) ); ?>"
                ></div>
            </div>
        <?php endif; ?>
    </div>

	<?php
endwhile;

get_footer();
