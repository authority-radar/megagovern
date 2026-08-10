<?php
/**
 * License — LITE Dummy — V1.0.4 — WordPress.org Compliant
 *
 * @package MegaGovern
 */
namespace MegaGovern;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class License {
    /**
     * Check if license is Pro.
     *
     * @return bool
     */
    public static function is_pro(): bool {
        return false;
    }
    /**
     * Check if license is Agency.
     *
     * @return bool
     */
    public static function is_agency(): bool {
        return false;
    }
    /**
     * Check if license is Free.
     *
     * @return bool
     */
    public static function is_free(): bool {
        return true;
    }
    /**
     * Get plan slug.
     *
     * @return string
     */
    public static function get_plan(): string {
        return 'free';
    }
    /**
     * Get plan name.
     *
     * @return string
     */
    public static function get_plan_name(): string {
        return 'Free';
    }
    /**
     * Get plan label.
     *
     * @return string
     */
    public static function get_plan_label(): string {
        return 'Free';
    }
    /**
     * Get license flags.
     *
     * @return array
     */
    public static function get_flags(): array {
        return [
            'is_pro'    => false,
            'is_agency' => false,
            'is_free'   => true,
        ];
    }
    /**
     * Check if feature is available (dummy always true).
     *
     * @param string $feature Feature identifier.
     * @return bool
     */
    public static function can_use( string $feature = '' ): bool {
        return true;
    }
    /**
     * Alias for can_use().
     *
     * @param string $feature Feature identifier.
     * @return bool
     */
    public static function can( string $feature = '' ): bool {
        return true;
    }
    /**
     * Check if feature is enabled (dummy always true).
     *
     * @param string $feature Feature identifier.
     * @return bool
     */
    public static function has_feature( string $feature = '' ): bool {
        return true;
    }
    /**
     * Alias for has_feature().
     *
     * @param string $feature Feature identifier.
     * @return bool
     */
    public static function is_feature_enabled( string $feature = '' ): bool {
        return true;
    }
    /**
     * Get upgrade URL.
     *
     * @param string $context Optional context.
     * @return string
     */
    public static function get_upgrade_url( string $context = '' ): string {
        return 'https://megagovern.com/pricing/';
    }
    /**
     * Get Pro upgrade URL.
     *
     * @param string $context Optional context.
     * @return string
     */
    public static function get_pro_url( string $context = '' ): string {
        return 'https://megagovern.com/pricing/';
    }
    /**
     * Magic fallback – prevents fatal errors for any undefined static calls.
     *
     * @param string $name      Method name.
     * @param array  $arguments Arguments.
     * @return bool|string|array
     */
    public static function __callStatic( string $name, array $arguments ) {
        if ( str_contains( $name, 'flags' ) ) {
            return [ 'is_pro' => false, 'is_agency' => false, 'is_free' => true ];
        }
        if ( str_contains( $name, 'pro' ) || str_contains( $name, 'agency' ) ) {
            return false;
        }
        if ( str_contains( $name, 'free' ) ) {
            return true;
        }
        if ( str_contains( $name, 'plan' ) || str_contains( $name, 'name' ) || str_contains( $name, 'label' ) ) {
            return 'Free';
        }
        if ( str_contains( $name, 'url' ) ) {
            return 'https://megagovern.com/pricing/';
        }
        return false;
    }
}