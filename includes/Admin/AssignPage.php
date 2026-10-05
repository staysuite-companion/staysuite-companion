<?php
/**
 * Admin tool: bulk-assign rooms to hotels.
 *
 * @package StaySuite\Companion\Admin
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Admin;

use StaySuite\Companion\Hotel\Repository;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Properties submenu page for assigning rooms to hotels.
 */
class AssignPage {

    /**
     * Page slug.
     *
     * @var string
     */
    const PAGE_SLUG = 'ssc-assign-hotels';

    /**
     * Rooms shown per page.
     *
     * @var int
     */
    const PER_PAGE = 50;

    /**
     * Hook suffix of the submenu page, for script loading.
     *
     * @var string
     */
    private $hook_suffix = '';

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
    }

    /**
     * Register the submenu under Properties.
     *
     * @return void
     */
    public function add_menu() {
        if ( ! post_type_exists( 'estate_property' ) ) {
            return;
        }
        $this->hook_suffix = (string) add_submenu_page(
            'edit.php?post_type=ssc_hotel',
            esc_html__( 'Assign Hotels', 'staysuite-companion' ),
            esc_html__( 'Assign Hotels', 'staysuite-companion' ),
            'edit_posts',
            self::PAGE_SLUG,
            array( $this, 'render' )
        );
    }

    /**
     * Hook suffix of the submenu page (empty until the menu registers).
     *
     * @return string Hook suffix or empty string.
     */
    public function hook_suffix() {
        return $this->hook_suffix;
    }

    /**
     * Load the bulk-checkbox script on the assignment screen only.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function assets( $hook ) {
        if ( '' === $this->hook_suffix || $hook !== $this->hook_suffix ) {
            return;
        }
        wp_enqueue_script( 'ssc-assign', SSC_URL . 'assets/js/ssc-assign.js', array(), SSC_VERSION, true );
    }

    /**
     * Process saves and render the assignment table.
     *
     * @return void
     */
    public function render() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'staysuite-companion' ) );
        }

        $hotels = Repository::get_all_hotels();

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- The nonce is verified at the top of each handler before any value is read.
        if ( isset( $_POST['ssc_assign_save'] ) ) {
            $this->process_save();
        }

        if ( isset( $_POST['ssc_bulk_apply'] ) ) {
            $this->process_bulk();
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $this->render_table( $hotels );
    }

    /**
     * Persist the submitted room -> hotel assignments.
     *
     * Affected hotels (new and previous) re-sync price, terms and coords.
     *
     * @return void
     */
    private function process_save() {
        check_admin_referer( 'ssc_assign_hotels', 'ssc_assign_nonce' );
        $saved = 0;
        $assignments = isset( $_POST['ssc_hotel_for'] ) && is_array( $_POST['ssc_hotel_for'] )
            ? array_map( 'intval', wp_unslash( $_POST['ssc_hotel_for'] ) )
            : array();
        $affected = array();
        foreach ( $assignments as $room_id => $hotel_id ) {
            $room_id = intval( $room_id );
            $hotel_id = intval( $hotel_id );
            if ( get_post_type( $room_id ) !== 'estate_property' || ! current_user_can( 'edit_post', $room_id ) ) {
                continue;
            }
            if ( $hotel_id > 0 && ( get_post_type( $hotel_id ) !== \StaySuite\Companion\Hotel\HotelCPT::POST_TYPE || get_post_status( $hotel_id ) !== 'publish' ) ) {
                continue;
            }
            $previous = Repository::get_room_hotel_id( $room_id );
            if ( $previous === $hotel_id ) {
                continue;
            }
            if ( $hotel_id > 0 ) {
                update_post_meta( $room_id, Repository::ROOM_HOTEL_META, $hotel_id );
            } else {
                delete_post_meta( $room_id, Repository::ROOM_HOTEL_META );
            }
            if ( $previous > 0 ) {
                $affected[] = $previous;
            }
            if ( $hotel_id > 0 ) {
                $affected[] = $hotel_id;
            }
            ++$saved;
        }
        foreach ( array_unique( $affected ) as $sync_id ) {
            Repository::sync_hotel_data( intval( $sync_id ) );
        }
        print '<div class="notice notice-success is-dismissible"><p>'
            . sprintf(
                /* translators: %d: number of rooms updated */
                esc_html( _n( '%d room updated.', '%d rooms updated.', $saved, 'staysuite-companion' ) ),
                intval( $saved )
            )
            . '</p></div>';
    }

    /**
     * Apply one hotel to all checked rooms.
     *
     * Affected hotels (new and previous) re-sync price, terms and coords.
     *
     * @return void
     */
    private function process_bulk() {
        check_admin_referer( 'ssc_assign_hotels', 'ssc_assign_nonce' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by check_admin_referer() above.
        if ( ! isset( $_POST['ssc_bulk_hotel'] ) || '' === $_POST['ssc_bulk_hotel'] ) {
            $this->notice( esc_html__( 'Pick a hotel for the bulk action first.', 'staysuite-companion' ), false );
            return;
        }
        $hotel_id = intval( $_POST['ssc_bulk_hotel'] );
        if ( $hotel_id > 0 && ( get_post_type( $hotel_id ) !== \StaySuite\Companion\Hotel\HotelCPT::POST_TYPE || get_post_status( $hotel_id ) !== 'publish' ) ) {
            return;
        }
        $room_ids = isset( $_POST['ssc_bulk_rooms'] ) && is_array( $_POST['ssc_bulk_rooms'] )
            ? array_map( 'intval', wp_unslash( $_POST['ssc_bulk_rooms'] ) )
            : array();
        $saved = 0;
        $affected = array();
        foreach ( $room_ids as $room_id ) {
            $room_id = intval( $room_id );
            if ( get_post_type( $room_id ) !== 'estate_property' || ! current_user_can( 'edit_post', $room_id ) ) {
                continue;
            }
            $previous = Repository::get_room_hotel_id( $room_id );
            if ( $previous === $hotel_id ) {
                continue;
            }
            if ( $hotel_id > 0 ) {
                update_post_meta( $room_id, Repository::ROOM_HOTEL_META, $hotel_id );
            } else {
                delete_post_meta( $room_id, Repository::ROOM_HOTEL_META );
            }
            if ( $previous > 0 ) {
                $affected[] = $previous;
            }
            if ( $hotel_id > 0 ) {
                $affected[] = $hotel_id;
            }
            ++$saved;
        }
        foreach ( array_unique( $affected ) as $sync_id ) {
            Repository::sync_hotel_data( intval( $sync_id ) );
        }
        $this->notice(
            sprintf(
            /* translators: %d: number of rooms updated */
                esc_html( _n( '%d room updated.', '%d rooms updated.', $saved, 'staysuite-companion' ) ),
                intval( $saved )
            ), true
        );
    }

    /**
     * Print an admin notice.
     *
     * @param string $text    Notice text (escaped).
     * @param bool   $success Success or error styling.
     * @return void
     */
    private function notice( $text, $success ) {
        printf(
            '<div class="notice %s is-dismissible"><p>%s</p></div>',
            $success ? 'notice-success' : 'notice-error',
            esc_html( $text )
        );
    }
    /**
     * Build the rooms query for the current filters.
     *
     * @param int    $paged  Current page number.
     * @param string $search Title search string.
     * @param string $filter One of all, assigned, unassigned.
     * @return WP_Query Rooms query.
     */
    private function query_rooms( $paged, $search, $filter ) {
        $args = array(
            'post_type'      => 'estate_property',
            'post_status'    => 'publish',
            'posts_per_page' => self::PER_PAGE,
            'paged'          => $paged,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );
        if ( $search !== '' ) {
            $args['s'] = $search;
        }
        if ( $filter === 'unassigned' ) {
            // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin list, 50 rows per page, indexed meta key.
            $args['meta_query'] = array(
                array(
					'key' => Repository::ROOM_HOTEL_META,
					'compare' => 'NOT EXISTS',
				),
            );
        } elseif ( $filter === 'assigned' ) {
            $args['meta_query'] = array(
                array(
					'key' => Repository::ROOM_HOTEL_META,
					'compare' => 'EXISTS',
				),
            );
        }
        // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        return new WP_Query( $args );
    }

    /**
     * Render filters, table and pagination.
     *
     * @param array<int,string> $hotels Hotel ID => title map.
     * @return void
     */
    private function render_table( $hotels ) {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list filters; nothing is written from these values.
        $paged  = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $filter = isset( $_GET['ssc_filter'] ) ? sanitize_key( $_GET['ssc_filter'] ) : 'all';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $rooms  = $this->query_rooms( $paged, $search, $filter );
        $base_url = admin_url( 'edit.php?post_type=ssc_hotel&page=' . self::PAGE_SLUG );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Assign Rooms to Hotels', 'staysuite-companion' ); ?></h1>
            <?php if ( empty( $hotels ) ) : ?>
                <div class="notice notice-warning"><p>
                    <?php esc_html_e( 'Create at least one Hotel first (Hotels → Add New), then come back to assign rooms.', 'staysuite-companion' ); ?>
                </p></div>
            <?php endif; ?>
            <ul class="subsubsub">
                <li><a href="<?php echo esc_url( $base_url ); ?>" class="<?php echo $filter === 'all' ? 'current' : ''; ?>"><?php esc_html_e( 'All', 'staysuite-companion' ); ?></a> | </li>
                <li><a href="<?php echo esc_url( add_query_arg( 'ssc_filter', 'unassigned', $base_url ) ); ?>" class="<?php echo $filter === 'unassigned' ? 'current' : ''; ?>"><?php esc_html_e( 'Unassigned', 'staysuite-companion' ); ?></a> | </li>
                <li><a href="<?php echo esc_url( add_query_arg( 'ssc_filter', 'assigned', $base_url ) ); ?>" class="<?php echo $filter === 'assigned' ? 'current' : ''; ?>"><?php esc_html_e( 'Assigned', 'staysuite-companion' ); ?></a></li>
            </ul>
            <form method="get" action="">
                <input type="hidden" name="post_type" value="estate_property">
                <input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
                <input type="hidden" name="ssc_filter" value="<?php echo esc_attr( $filter ); ?>">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>">
                    <input type="submit" class="button" value="<?php esc_attr_e( 'Search rooms', 'staysuite-companion' ); ?>">
                </p>
            </form>
            <form method="post" action="">
                <?php wp_nonce_field( 'ssc_assign_hotels', 'ssc_assign_nonce' ); ?>
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label class="screen-reader-text" for="ssc_bulk_hotel"><?php esc_html_e( 'Assign checked rooms to', 'staysuite-companion' ); ?></label>
                        <select id="ssc_bulk_hotel" name="ssc_bulk_hotel">
                            <option value=""><?php esc_html_e( 'Bulk: pick a hotel…', 'staysuite-companion' ); ?></option>
                            <option value="0"><?php esc_html_e( '— Standalone —', 'staysuite-companion' ); ?></option>
                            <?php foreach ( $hotels as $hotel_id => $hotel_title ) : ?>
                                <option value="<?php echo intval( $hotel_id ); ?>"><?php echo esc_html( $hotel_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" name="ssc_bulk_apply" class="button" value="<?php esc_attr_e( 'Apply to checked', 'staysuite-companion' ); ?>">
                    </div>
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(
                            array(
								'base'    => add_query_arg(
                                    array(
										'paged' => '%#%',
										's' => $search,
										'ssc_filter' => $filter,
                                    ), $base_url
                                ),
								'format'  => '',
								'current' => $paged,
								'total'   => max( 1, intval( $rooms->max_num_pages ) ),
                            )
                        );
                        ?>
                    </div>
                </div>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="ssc_select_all" aria-label="<?php esc_attr_e( 'Select all rooms on this page', 'staysuite-companion' ); ?>"></th>
                            <th><?php esc_html_e( 'Room', 'staysuite-companion' ); ?></th>
                            <th><?php esc_html_e( 'City', 'staysuite-companion' ); ?></th>
                            <th><?php esc_html_e( 'Suggested', 'staysuite-companion' ); ?></th>
                            <th><?php esc_html_e( 'Hotel', 'staysuite-companion' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $rooms->have_posts() ) : ?>
                            <?php
                            while ( $rooms->have_posts() ) :
                                $rooms->the_post();
                                $room_id = get_the_ID();
                                $current = Repository::get_room_hotel_id( $room_id );
                                $cities = get_the_term_list( $room_id, 'property_city', '', ', ', '' );
                                $suggested = $current > 0 ? 0 : Repository::suggest_hotel( get_the_title( $room_id ), $hotels );
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="ssc-bulk-check" name="ssc_bulk_rooms[]" value="<?php echo intval( $room_id ); ?>"></td>
                                    <td><a href="<?php echo esc_url( get_edit_post_link( $room_id ) ); ?>"><?php echo esc_html( get_the_title( $room_id ) ); ?></a></td>
                                    <td><?php echo $cities !== '' && ! is_wp_error( $cities ) ? wp_kses_post( $cities ) : '<span aria-hidden="true">—</span>'; ?></td>
                                    <td><?php echo $suggested > 0 ? esc_html( get_the_title( $suggested ) ) : '<span aria-hidden="true">—</span>'; ?></td>
                                    <td>
                                        <select name="ssc_hotel_for[<?php echo intval( $room_id ); ?>]">
                                            <option value="0"><?php esc_html_e( '— Standalone —', 'staysuite-companion' ); ?></option>
                                            <?php foreach ( $hotels as $hotel_id => $hotel_title ) : ?>
                                                <option value="<?php echo intval( $hotel_id ); ?>" <?php selected( $current > 0 ? $current : $suggested, intval( $hotel_id ) ); ?>>
                                                    <?php echo esc_html( $hotel_title ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        <?php else : ?>
                            <tr><td colspan="5"><?php esc_html_e( 'No rooms found.', 'staysuite-companion' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p>
                    <input type="submit" name="ssc_assign_save" class="button button-primary" value="<?php esc_attr_e( 'Save assignments', 'staysuite-companion' ); ?>">
                </p>
            </form>
            <div class="tablenav">
                <?php
                echo paginate_links(
                    array(
						'base'    => add_query_arg(
                            array(
								'paged' => '%#%',
								's' => $search,
								'ssc_filter' => $filter,
                            ), $base_url
                        ),
						'format'  => '',
						'current' => $paged,
						'total'   => max( 1, intval( $rooms->max_num_pages ) ),
                    )
                );
                ?>
            </div>
        </div>
        <?php
    }
}
