<?php
/**
 * Signup extras: phone and gender on theme registration forms.
 *
 * The theme owns registration (wpestate_ajax_register_form + the booking
 * variant, both closed die()-based handlers with no filters), so this
 * class enforces the admin's Accounts settings from the outside:
 *
 * - Server-side: hooks the same wp_ajax_* actions at priority 1 and
 *   rejects invalid signups with the theme's own response shape before
 *   the theme handler runs. Never modifies a theme file.
 * - Saving: gender persists on user_register; phone keeps flowing into
 *   the theme's own mobile meta (filled in only when the theme left it
 *   empty, e.g. the booking flow which never saves a phone).
 * - Profile: gender is injected into the theme dashboard profile page
 *   (unless the mode is off) and saved ahead of the theme's own
 *   profile-update handler; the signup form only shows it when the
 *   mode is optional, or always when required.
 *
 * @package StaySuite\Companion\Accounts
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Accounts;

use StaySuite\Companion\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Validates and stores signup phone + gender.
 */
class Signup {

    /**
     * Theme user meta key for the phone number (theme-owned, reused).
     *
     * @var string
     */
    const PHONE_META = 'mobile';

    /**
     * Plugin user meta key for gender (plugin-owned).
     *
     * @var string
     */
    const GENDER_META = '_ssc_gender';

    /**
     * POST field carrying gender from the injected signup inputs.
     *
     * @var string
     */
    const GENDER_FIELD = 'ssc_gender';

    /**
     * POST field carrying the phone number (theme convention).
     *
     * @var string
     */
    const PHONE_FIELD = 'user_phone';

    /**
     * Gender option slugs.
     *
     * @return string[] Allowed slugs.
     */
    public static function gender_slugs() {
        return array( 'male', 'female', 'other' );
    }

    /**
     * Gender options for the signup select (slug => label).
     *
     * @return array<string,string> Options.
     */
    public static function genders() {
        $genders = array(
            'male'   => esc_html__( 'Male', 'staysuite-companion' ),
            'female' => esc_html__( 'Female', 'staysuite-companion' ),
            'other'  => esc_html__( 'Other', 'staysuite-companion' ),
        );
        /**
         * Filter signup gender options (slug => label).
         *
         * @param array<string,string> $genders Gender options.
         */
        return apply_filters( 'ssc_signup_genders', $genders );
    }

    /**
     * Stored gender slug for a user.
     *
     * @param int $user_id User ID.
     * @return string Slug or empty string.
     */
    public static function gender_slug( $user_id ) {
        $slug = (string) get_user_meta( intval( $user_id ), self::GENDER_META, true );
        return in_array( $slug, self::gender_slugs(), true ) ? $slug : '';
    }

    /**
     * Stored gender label for a user.
     *
     * @param int $user_id User ID.
     * @return string Label or empty string.
     */
    public static function gender_label( $user_id ) {
        $genders = self::genders();
        $slug = self::gender_slug( $user_id );
        return isset( $genders[ $slug ] ) ? $genders[ $slug ] : '';
    }

    /**
     * Whether gender appears on the signup forms.
     *
     * Optional shows it skippable; required forces it on and enforces
     * it. Profile-only and off keep it off the signup form (profile
     * stays editable unless off).
     *
     * @return bool True for optional/required modes.
     */
    public static function show_gender_on_signup() {
        return in_array( Settings::get( 'signup_gender' ), array( 'optional', 'required' ), true );
    }

    /**
     * Wire up hooks.
     */
    public function __construct() {
        add_action( 'wp_ajax_nopriv_wpestate_ajax_register_form', array( $this, 'validate_main' ), 1 );
        add_action( 'wp_ajax_wpestate_ajax_register_form', array( $this, 'validate_main' ), 1 );
        add_action( 'wp_ajax_nopriv_wpestate_ajax_register_form_booking', array( $this, 'validate_booking' ), 1 );
        add_action( 'wp_ajax_wpestate_ajax_register_form_booking', array( $this, 'validate_booking' ), 1 );
        add_action( 'wp_ajax_wpestate_ajax_update_profile', array( $this, 'save_profile_gender' ), 1 );
        add_action( 'user_register', array( $this, 'save_fields' ) );
    }

    /**
     * Validate the main registration flow before the theme handler runs.
     *
     * Replies with the theme's own JSON shape so the modal shows the
     * error exactly like a theme-side rejection.
     *
     * @return void
     */
    public function validate_main() {
        $error = $this->validate_request();
        if ( $error !== '' ) {
            echo wp_json_encode(
                array(
                    'register' => false,
                    'message'  => $error,
                )
            );
            die();
        }
    }

    /**
     * Validate the booking registration flow before the theme handler runs.
     *
     * The booking handler prints a plain-text message, so this mirrors
     * that shape instead of JSON.
     *
     * @return void
     */
    public function validate_booking() {
        $error = $this->validate_request();
        if ( $error !== '' ) {
            print esc_html( $error );
            die();
        }
    }

    /**
     * Check phone/gender against the admin's Accounts settings.
     *
     * Phone numbers are required-but-never-format-checked, matching the
     * group quote form convention. Gender must be an allowlisted slug.
     *
     * @return string Error message or empty string when valid.
     */
    private function validate_request() {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Read-only validation of the visitor's own signup payload; the theme handler verifies its nonce right after.
        $phone = isset( $_POST[ self::PHONE_FIELD ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ self::PHONE_FIELD ] ) ) ) : '';
        $gender = isset( $_POST[ self::GENDER_FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::GENDER_FIELD ] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        if ( Settings::get( 'signup_phone' ) === 'required' && $phone === '' ) {
            /**
             * Filter the signup phone-required error.
             *
             * @param string $message Error shown on the register form.
             */
            return (string) apply_filters( 'ssc_signup_phone_error', esc_html__( 'Please enter your phone number.', 'staysuite-companion' ) );
        }
        if ( Settings::get( 'signup_gender' ) === 'required' && ! in_array( $gender, self::gender_slugs(), true ) ) {
            /**
             * Filter the signup gender-required error.
             *
             * @param string $message Error shown on the register form.
             */
            return (string) apply_filters( 'ssc_signup_gender_error', esc_html__( 'Please select your gender.', 'staysuite-companion' ) );
        }
        return '';
    }

    /**
     * Persist signup fields on user creation.
     *
     * Gender always saves when allowlisted. Phone only fills the theme's
     * mobile meta when empty — the main flow already saved it, while the
     * booking flow never does.
     *
     * @param int $user_id New user ID.
     * @return void
     */
    public function save_fields( $user_id ) {
        $user_id = intval( $user_id );
        if ( $user_id <= 0 ) {
            return;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Persists the visitor's own just-submitted signup payload during user_register.
        $gender = isset( $_POST[ self::GENDER_FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::GENDER_FIELD ] ) ) : '';
        $phone = isset( $_POST[ self::PHONE_FIELD ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ self::PHONE_FIELD ] ) ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        if ( in_array( $gender, self::gender_slugs(), true ) ) {
            update_user_meta( $user_id, self::GENDER_META, $gender );
        }
        if ( $phone !== '' && (string) get_user_meta( $user_id, self::PHONE_META, true ) === '' ) {
            update_user_meta( $user_id, self::PHONE_META, $phone );
        }
    }

    /**
     * Persist gender from the dashboard profile page.
     *
     * Runs ahead of the theme's own update-profile handler (which only
     * touches its own meta keys) and saves nothing else, so the theme
     * flow continues untouched. Skipped entirely when gender is off.
     *
     * @return void
     */
    public function save_profile_gender() {
        if ( Settings::get( 'signup_gender' ) === 'off' ) {
            return;
        }
        $user_id = get_current_user_id();
        if ( $user_id <= 0 ) {
            return;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Persists the account owner's own profile payload; the theme handler verifies its nonce right after.
        $gender = isset( $_POST[ self::GENDER_FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::GENDER_FIELD ] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        if ( in_array( $gender, self::gender_slugs(), true ) ) {
            update_user_meta( $user_id, self::GENDER_META, $gender );
        }
    }
}
