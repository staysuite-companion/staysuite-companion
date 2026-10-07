<?php
/**
 * Branded invoice template.
 *
 * Selected per page via Page Attributes → Template → StaySuite Invoice.
 * Mockup: shows sample invoice data in our layout so the design can be
 * reviewed before the quote-to-invoice pipeline maps real data.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$ssc_logo = '';
if ( function_exists( 'wprentals_get_option' ) ) {
    $ssc_logo = wprentals_get_option( 'wp_estate_logo_image', 'url' );
}
if ( empty( $ssc_logo ) ) {
    $ssc_logo = SSC_URL . 'assets/images/staysuite-logo.svg';
}
$ssc_brand = get_bloginfo( 'name' );
$ssc_brand_mode = \StaySuite\Companion\Admin\Settings::get( 'invoice_brand' );
?>
    <main class="ssc-invoice-page">
        <article class="ssc-invoice">
            <header class="ssc-invoice__header">
                <div class="ssc-invoice__brand">
                    <?php if ( $ssc_brand_mode !== 'name' ) : ?>
                        <img class="ssc-invoice__logo" src="<?php echo esc_url( $ssc_logo ); ?>" alt="<?php echo esc_attr( $ssc_brand ); ?>" />
                    <?php endif; ?>
                    <?php if ( $ssc_brand_mode !== 'logo' ) : ?>
                        <strong class="ssc-invoice__brand-name"><?php echo esc_html( $ssc_brand ); ?></strong>
                    <?php endif; ?>
                </div>
                <div class="ssc-invoice__title">
                    <h1><?php echo esc_html__( 'Invoice', 'staysuite-companion' ); ?></h1>
                    <p>INV-2026-001</p>
                    <p><?php echo esc_html__( 'Issued: Oct 8, 2026', 'staysuite-companion' ); ?></p>
                </div>
            </header>

            <section class="ssc-invoice__meta">
                <div>
                    <h2><?php echo esc_html__( 'Guest', 'staysuite-companion' ); ?></h2>
                    <p>John Doe<br>john@example.com<br>+1 (555) 012-3456</p>
                </div>
                <div>
                    <h2><?php echo esc_html__( 'Stay', 'staysuite-companion' ); ?></h2>
                    <p>Varsity Surfers Hotel<br>2 nights · 4 guests<br>Oct 10, 2026 – Oct 12, 2026</p>
                </div>
                <div>
                    <h2><?php echo esc_html__( 'Status', 'staysuite-companion' ); ?></h2>
                    <p><span class="ssc-invoice__status">Confirmed</span></p>
                </div>
            </section>

            <table class="ssc-invoice__lines">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Description', 'staysuite-companion' ); ?></th>
                        <th><?php echo esc_html__( 'Qty', 'staysuite-companion' ); ?></th>
                        <th><?php echo esc_html__( 'Rate', 'staysuite-companion' ); ?></th>
                        <th><?php echo esc_html__( 'Amount', 'staysuite-companion' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Deluxe King Room</td><td>2 nights</td><td>$120.00</td><td>$240.00</td></tr>
                    <tr><td>Cleaning fee</td><td>1</td><td>$35.00</td><td>$35.00</td></tr>
                    <tr><td>City fee</td><td>1</td><td>$12.00</td><td>$12.00</td></tr>
                    <tr><td>Extra guests</td><td>2 × 2 nights</td><td>$15.00</td><td>$60.00</td></tr>
                </tbody>
            </table>

            <footer class="ssc-invoice__totals">
                <dl>
                    <div><dt>Subtotal</dt><dd>$347.00</dd></div>
                    <div><dt>Discount (10%)</dt><dd>-$34.70</dd></div>
                    <div><dt><?php echo esc_html__( 'Taxes', 'staysuite-companion' ); ?> (8%)</dt><dd>$25.04</dd></div>
                    <div><dt>Deposit paid</dt><dd>$84.75</dd></div>
                    <div class="ssc-invoice__balance"><dt>Balance due</dt><dd>$252.59</dd></div>
                </dl>
            </footer>

            <p class="ssc-invoice__note"><?php echo esc_html__( 'Deposit is non-refundable after confirmation. Balance is due on arrival.', 'staysuite-companion' ); ?></p>
        </article>
    </main>
<?php
wp_footer();
?>
</body>
</html>
