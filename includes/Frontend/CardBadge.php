<?php
/**
 * Batched room -> hotel resolution for listing cards.
 *
 * @package StaySuite\Companion\Frontend
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Frontend;

use StaySuite\Companion\Hotel\Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX endpoint feeding the React card badges.
 */
class CardBadge {

    /**
     * Nonce action for badge requests.
     *
     * @var string
     */
    const NONCE_ACTION = 'ssc_cards';

    /**
     * Maximum room IDs resolved per request.
     *
     * @var int
     */
    const MAX_IDS = 50;

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'wp_ajax_ssc_resolve_hotels', array( $this, 'resolve' ) );
        add_action( 'wp_ajax_nopriv_ssc_resolve_hotels', array( $this, 'resolve' ) );
    }

    /**
     * Resolve hotels for a batch of room IDs.
     *
     * Responds with a map of room ID => {name, url} for linked rooms.
     *
     * @return void
     */
    public function resolve() {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        $ids = isset( $_POST['ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['ids'] ) ) : array();
        $ids = array_slice( array_unique( array_filter( $ids ) ), 0, self::MAX_IDS );
        wp_send_json_success( $this->resolve_ids( $ids ) );
    }

    /**
     * Build the room ID => hotel map.
     *
     * @param int[] $room_ids Room post IDs.
     * @return array<int,array{name:string,url:string|false}> Resolution map.
     */
    private function resolve_ids( $room_ids ) {
        $map = array();
        foreach ( $room_ids as $room_id ) {
            if ( get_post_type( $room_id ) !== 'estate_property' ) {
                continue;
            }
            $hotel_id = Repository::get_room_hotel_id( $room_id );
            if ( $hotel_id > 0 && get_post_status( $hotel_id ) === 'publish' ) {
                $map[ $room_id ] = array(
                    // Decoded: React text nodes would show title-filter entities literally.
                    'name' => html_entity_decode( get_the_title( $hotel_id ), ENT_QUOTES, 'UTF-8' ),
                    'url'  => get_permalink( $hotel_id ),
                );
            }
        }
        return $map;
    }
}
