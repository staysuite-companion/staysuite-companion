<?php
/**
 * REST preview endpoint for block editor previews.
 *
 * Replaces @wordpress/server-side-render with a first-party preview that
 * POSTs attributes and returns Renderer HTML. Keeps all iframe/ref
 * machinery out of the editor canvas.
 *
 * @package StaySuite\Companion\Blocks
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Blocks;

use StaySuite\Companion\Hotel\HotelCPT;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Serves rendered block HTML to the editor.
 */
class PreviewEndpoint {

    /**
     * REST namespace.
     *
     * @var string
     */
    const NAMESPACE = 'ssc/v1';

    /**
     * Preview route.
     *
     * @var string
     */
    const ROUTE = '/preview';

    /**
     * Carousel options route.
     *
     * @var string
     */
    const OPTIONS_ROUTE = '/carousel-options';

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register the preview route (editors only).
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            self::NAMESPACE, self::ROUTE, array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'render_preview' ),
				'permission_callback' => array( $this, 'can_preview' ),
            )
        );
        register_rest_route(
            self::NAMESPACE, self::OPTIONS_ROUTE, array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_carousel_options' ),
				'permission_callback' => array( $this, 'can_preview' ),
            )
        );
    }

    /**
     * Check preview capability.
     *
     * @return bool True for users who can edit posts.
     */
    public function can_preview() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Render block HTML for the given attributes.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error Response with html or error.
     */
    public function render_preview( $request ) {
        // NOTE: no sanitize_key() here — it strips the "/" all block names
        // contain. Strict allowlist comparison instead.
        $block = (string) $request->get_param( 'block' );
        $attributes = $request->get_param( 'attributes' );
        if ( ! is_array( $attributes ) ) {
            $attributes = array();
        }
        switch ( $block ) {
            case 'ssc/term-tablets':
                $html = Renderer::render_term_tablets( $this->coerce_tablets( $attributes ) );
                break;
            case 'ssc/listing-carousel':
                $html = Renderer::render_listing_carousel( $this->coerce_carousel( $attributes ) );
                break;
            case 'ssc/hero-search':
                $hero = $this->coerce_hero( $attributes );
                // The REST request has no query, so the cover fallback needs
                // the post being edited.
                $hero['post_id'] = $this->request_post_id( $request );
                $html = Renderer::render_hero( $hero );
                break;
            case 'ssc/group-booking':
                $html = $this->render_booking( $attributes );
                break;
            case 'ssc/payment-strip':
                $html = Renderer::render_payment_strip(
                    array(
						'title'    => isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '',
						'image_id' => isset( $attributes['image_id'] ) ? intval( $attributes['image_id'] ) : 0,
                    )
                );
                break;
            default:
                return new WP_Error( 'ssc_unknown_block', esc_html__( 'Unknown block.', 'staysuite-companion' ), array( 'status' => 400 ) );
        }
        // Marker class lets preview-only CSS (e.g. hiding the JS-driven
        // guest dropdown) apply without touching the frontend.
        return new WP_REST_Response( array( 'html' => '<div class="ssc-preview">' . $html . '</div>' ), 200 );
    }

    /**
     * Carousel editor options: taxonomy terms and source posts.
     *
     * The theme's post type and taxonomies are not REST-exposed, so the
     * editor cannot use /wp/v2 for these dropdowns. This route serves the
     * same data through the plugin's own namespace.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response Response with terms and posts.
     */
    public function get_carousel_options( $request ) {
        $taxonomy = sanitize_key( (string) $request->get_param( 'taxonomy' ) );
        if ( ! in_array( $taxonomy, Renderer::ALLOWED_TAXONOMIES, true ) ) {
            $taxonomy = '';
        }
        $source = sanitize_key( (string) $request->get_param( 'source' ) );
        if ( 'hotels' !== $source ) {
            $source = 'rooms';
        }
        return new WP_REST_Response(
            array(
				'terms' => self::carousel_terms( $taxonomy, $source ),
				'posts' => self::carousel_posts( $source ),
            ), 200
        );
    }

    /**
     * Terms for a carousel filter taxonomy.
     *
     * Counts are scoped to the selected source: term->count mixes every
     * post type, so hotel rows would otherwise show room counts.
     *
     * @param string $taxonomy Taxonomy slug, empty for none.
     * @param string $source   Carousel source (rooms|hotels).
     * @return array<int,array{slug:string,name:string,count:int}> Term options.
     */
    private static function carousel_terms( $taxonomy, $source ) {
        if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
            return array();
        }
        $terms = get_terms(
            array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 200,
				'orderby'    => 'name',
				'order'      => 'ASC',
            )
        );
        if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
            return array();
        }
        $post_type = 'hotels' === $source ? HotelCPT::POST_TYPE : 'estate_property';
        $counts = self::term_post_counts( $taxonomy, $post_type );
        $options = array();
        foreach ( $terms as $term ) {
            if ( ! $term instanceof \WP_Term ) {
                continue;
            }
            $options[] = array(
				'slug'  => $term->slug,
				'name'  => $term->name,
				'count' => isset( $counts[ intval( $term->term_id ) ] ) ? intval( $counts[ intval( $term->term_id ) ] ) : 0,
            );
        }
        usort(
            $options, function ( $a, $b ) {
				if ( $a['count'] !== $b['count'] ) {
					return $b['count'] - $a['count'];
				}
				return strcasecmp( $a['name'], $b['name'] );
			}
        );
        return $options;
    }

    /**
     * Published post counts per term for one post type.
     *
     * No WP API returns per-term counts scoped to a single post type, so
     * one grouped query does it for every term at once.
     *
     * @param string $taxonomy  Taxonomy slug.
     * @param string $post_type Post type slug.
     * @return array<int,int> Term ID => published post count.
     */
    private static function term_post_counts( $taxonomy, $post_type ) {
        global $wpdb;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Single grouped count query; get_terms() counts mix every post type.
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT tt.term_id AS term_id, COUNT(*) AS posts_count FROM {$wpdb->term_relationships} AS tr INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id WHERE tt.taxonomy = %s AND p.post_type = %s AND p.post_status = 'publish' GROUP BY tt.term_id",
                $taxonomy,
                $post_type
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
        $counts = array();
        if ( ! is_array( $rows ) ) {
            return $counts;
        }
        foreach ( $rows as $row ) {
            if ( isset( $row['term_id'], $row['posts_count'] ) ) {
                $counts[ intval( $row['term_id'] ) ] = intval( $row['posts_count'] );
            }
        }
        return $counts;
    }

    /**
     * Posts for the carousel hand-picked dropdown.
     *
     * @param string $source Carousel source (rooms|hotels).
     * @return array<int,array{id:int,title:string}> Post options.
     */
    private static function carousel_posts( $source ) {
        $post_type = 'hotels' === $source ? HotelCPT::POST_TYPE : 'estate_property';
        if ( ! post_type_exists( $post_type ) ) {
            return array();
        }
        $ids = get_posts(
            array(
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'posts_per_page'   => 100,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'suppress_filters' => true,
            )
        );
        if ( ! is_array( $ids ) ) {
            return array();
        }
        $options = array();
        foreach ( $ids as $id ) {
            $id = intval( $id );
            if ( $id <= 0 ) {
                continue;
            }
            $title = get_the_title( $id );
            if ( ! is_string( $title ) || '' === trim( $title ) ) {
                // translators: %d: post ID.
                $title = sprintf( esc_html__( 'Untitled #%d', 'staysuite-companion' ), $id );
            }
            $options[] = array(
				'id'    => $id,
				'title' => $title,
            );
        }
        return $options;
    }

    /**
     * Read the post id sent with a preview request.
     *
     * @param WP_REST_Request $request REST request.
     * @return int Post ID, or 0 when absent or not a post.
     */
    private function request_post_id( $request ) {
        $post_id = intval( $request->get_param( 'postId' ) );
        if ( $post_id > 0 && get_post( $post_id ) instanceof WP_Post ) {
            return $post_id;
        }
        return 0;
    }

    /**
     * Coerce tablets attributes to safe types.
     *
     * @param array<string,mixed> $attributes Raw attributes.
     * @return array<string,mixed> Coerced attributes.
     */
    private function coerce_tablets( $attributes ) {
        $slugs = array();
        if ( isset( $attributes['include_slugs'] ) ) {
            $raw = $attributes['include_slugs'];
            if ( ! is_array( $raw ) ) {
                $raw = explode( ',', (string) $raw );
            }
            foreach ( $raw as $part ) {
                $slug = sanitize_title( (string) $part );
                if ( $slug !== '' && ! in_array( $slug, $slugs, true ) ) {
                    $slugs[] = $slug;
                }
            }
        }
        return array(
            'taxonomy'      => isset( $attributes['taxonomy'] ) ? sanitize_key( $attributes['taxonomy'] ) : 'property_city',
            'number'        => isset( $attributes['number'] ) ? intval( $attributes['number'] ) : 6,
            'hide_empty'    => ! empty( $attributes['hide_empty'] ),
            'include_slugs' => $slugs,
            'show_divider'  => ! isset( $attributes['show_divider'] ) || ! empty( $attributes['show_divider'] ),
        );
    }

    /**
     * Coerce carousel attributes to safe types.
     *
     * @param array<string,mixed> $attributes Raw attributes.
     * @return array<string,mixed> Coerced attributes.
     */
    private function coerce_carousel( $attributes ) {
        return array(
            'title'         => isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '',
            'source'        => isset( $attributes['source'] ) ? sanitize_key( $attributes['source'] ) : 'rooms',
            'taxonomy'      => isset( $attributes['taxonomy'] ) ? sanitize_key( $attributes['taxonomy'] ) : '',
            'term'          => isset( $attributes['term'] ) ? sanitize_title( $attributes['term'] ) : '',
            'city'          => isset( $attributes['city'] ) ? sanitize_title( $attributes['city'] ) : '',
            'count'         => isset( $attributes['count'] ) ? intval( $attributes['count'] ) : 8,
            'featured_only' => ! empty( $attributes['featured_only'] ),
            'include_ids'   => isset( $attributes['include_ids'] ) ? sanitize_text_field( $attributes['include_ids'] ) : '',
            'order'         => isset( $attributes['order'] ) ? sanitize_key( $attributes['order'] ) : 'featured',
            'show_divider'  => ! isset( $attributes['show_divider'] ) || ! empty( $attributes['show_divider'] ),
        );
    }

    /**
     * Coerce hero attributes to safe types.
     *
     * @param array<string,mixed> $attributes Raw attributes.
     * @return array<string,mixed> Coerced attributes.
     */
    private function coerce_hero( $attributes ) {
        return array(
            'title'        => isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '',
            'subtitle'     => isset( $attributes['subtitle'] ) ? sanitize_text_field( $attributes['subtitle'] ) : '',
            'image_id'     => isset( $attributes['image_id'] ) ? intval( $attributes['image_id'] ) : 0,
            'show_search'  => ! isset( $attributes['show_search'] ) || ! empty( $attributes['show_search'] ),
            'search_mode'  => isset( $attributes['search_mode'] ) ? sanitize_key( $attributes['search_mode'] ) : 'theme',
            'hero_height'  => isset( $attributes['hero_height'] ) ? intval( $attributes['hero_height'] ) : 75,
            'show_capsule' => ! isset( $attributes['show_capsule'] ) || ! empty( $attributes['show_capsule'] ),
            'animate_form' => ! isset( $attributes['animate_form'] ) || ! empty( $attributes['animate_form'] ),
        );
    }
    /**
     * Render the booking mount node preview.
     *
     * @param array<string,mixed> $attributes Raw attributes.
     * @return string Mount node HTML.
     */
    private function render_booking( $attributes ) {
        $title = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';
        $html = '<div class="ssc-booking" data-ssc-group-booking';
        if ( $title !== '' ) {
            $html .= ' data-title="' . esc_attr( $title ) . '"';
        }
        $html .= '><p>' . esc_html__( 'Group booking form renders on the frontend.', 'staysuite-companion' ) . '</p></div>';
        return $html;
    }
}
