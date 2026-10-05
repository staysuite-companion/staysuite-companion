<?php
/**
 * Hotel results for the native WordPress search (?s=).
 *
 * Served instead of the theme's search.php when the search_result
 * setting is 'hotels'. Renders the same hotel cards used by the
 * homepage blocks. No property-unit markup, no map pins.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

use StaySuite\Companion\Blocks\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="ssc-hotel-search">
    <h1 class="ssc-hotel-search-title">
        <?php
        printf(
            /* translators: %s: search query. */
            esc_html__( 'Results for: "%s"', 'staysuite-companion' ),
            esc_html( get_search_query() )
        );
        ?>
    </h1>

    <?php if ( have_posts() ) : ?>
        <?php Renderer::setup_card_context(); ?>
        <div class="ssc-hotel-grid">
            <?php
            while ( have_posts() ) :
                the_post();
                echo Renderer::render_hotel_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Card HTML is escaped at build time.
            endwhile;
            ?>
        </div>
        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p><?php esc_html_e( 'No hotels found. Try a different search.', 'staysuite-companion' ); ?></p>
    <?php endif; ?>
</main>
<?php
get_footer();
