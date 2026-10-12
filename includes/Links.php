<?php
/**
 * Canonical external URLs for the plugin.
 *
 * Single source of truth for every outbound link (Pro landing page,
 * docs, support, repository). PHP and JS both read from here — JS
 * through wp_localize_script() — so an address change touches one file.
 * Plain links, no UTM parameters.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Holds the plugin's outbound URLs.
 */
final class Links {

    /**
     * Plugin product page.
     *
     * @var string
     */
    const PLUGIN = 'https://jktanmay.com/products/staysuite-companion';

    /**
     * Documentation.
     *
     * @var string
     */
    const DOCS = 'https://jktanmay.com/products/staysuite-companion/docs';

    /**
     * Support.
     *
     * @var string
     */
    const SUPPORT = 'https://jktanmay.com/products/staysuite-companion/support';

    /**
     * Pro landing page.
     *
     * @var string
     */
    const PRO = 'https://jktanmay.com/products/staysuite-companion/pro';

    /**
     * Source repository and issue tracker.
     *
     * @var string
     */
    const GITHUB = 'https://github.com/staysuite-companion/staysuite-companion';

    /**
     * Plugin author site.
     *
     * @var string
     */
    const AUTHOR = 'https://jktanmay.com';

    /**
     * URLs for wp_localize_script().
     *
     * Includes the Pro presence flag, read from Pro's version constant
     * (never from folder names) so the editor can hide Pro upsells when
     * Pro is already installed.
     *
     * @return array<string,string|int> Link list keyed for JS.
     */
    public static function all() {
        return array(
            'plugin'     => self::PLUGIN,
            'docs'       => self::DOCS,
            'support'    => self::SUPPORT,
            'pro'        => self::PRO,
            'github'     => self::GITHUB,
            'author'     => self::AUTHOR,
            'pro_active' => defined( 'SSC_PRO_VERSION' ) ? 1 : 0,
        );
    }
}
