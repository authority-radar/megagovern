<?php
/**
 * Transparency Badge — V1.0.8
 *
 * FIXED: enqueue_frontend_styles() now hooked
 * FIXED: Duplicate register_setting() calls removed
 * FIXED: Text domain unified to 'megagovern'
 * FIXED: CSS handle consistency
 * FIXED: Color fallbacks in get_settings()
 *
 * @package MegaGovern
 * @since   1.0.8
 */
namespace MegaGovern;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class TransparencyBadge {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ], 20 );
        add_action( 'wp_footer', [ $this, 'render_floating_badge' ], 9999 );
        add_action( 'wp_ajax_megagovern_transparency_dismiss', [ $this, 'ajax_dismiss' ] );
        add_action( 'wp_ajax_nopriv_megagovern_transparency_dismiss', [ $this, 'ajax_dismiss' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'megagovern_declaration_changed', [ $this, 'clear_cache' ] );
        add_action( 'megagovern_declaration_saved', [ $this, 'clear_cache' ] );
        add_action( 'megagovern_bulk_declaration', [ $this, 'clear_cache' ] );
    }
    public function get_settings(): array {
        $is_pro    = class_exists( '\MegaGovern\License' ) ? License::is_pro() : false;
        $is_agency = class_exists( '\MegaGovern\License' ) ? License::is_agency() : false;
        return [
            'enabled'       => (bool) get_option( 'megagovern_notice_enabled', true ),
            'position'      => get_option( 'megagovern_notice_position', 'bottom' ),
            'bg_color'      => get_option( 'megagovern_notice_bg_color' ) ?: '#0052CC',
            'text_color'    => get_option( 'megagovern_notice_text_color' ) ?: '#ffffff',
            'link_color'    => get_option( 'megagovern_notice_link_color' ) ?: '#93c5fd',
            'show_powered'  => (bool) get_option( 'megagovern_notice_show_powered', true ),
            'policy_page'   => (int) get_option( 'megagovern_notice_policy_page', 0 ),
            'custom_text'   => get_option( 'megagovern_notice_custom_text', '' ),
            'powered_text'  => get_option( 'megagovern_notice_powered_text', __( 'Powered by MegaGovern', 'megagovern' ) ),
            'white_label'   => $is_agency,
            'is_pro'        => $is_pro,
            'is_agency'     => $is_agency,
        ];
    }
    public function get_summary(): array {
        $cached = get_transient( 'megagovern_transparency_summary' );
        if ( false !== $cached ) {
            return $cached;
        }
        $stats     = class_exists( '\MegaGovern\Registry' ) ? Registry::get_stats() : [];
        $total     = (int) ( $stats['total'] ?? 0 );
        $human     = (int) ( $stats['human'] ?? 0 );
        $assisted  = (int) ( $stats['ai_assisted'] ?? 0 );
        $generated = (int) ( $stats['ai_generated'] ?? 0 );
        $deepfake  = (int) ( $stats['deepfake'] ?? 0 );
        $summary = [
            'total'         => $total,
            'human'         => $human,
            'assisted'      => $assisted,
            'generated'     => $generated,
            'deepfake'      => $deepfake,
            'human_pct'     => $total > 0 ? round( ( $human / $total ) * 100 ) : 0,
            'assisted_pct'  => $total > 0 ? round( ( $assisted / $total ) * 100 ) : 0,
            'generated_pct' => $total > 0 ? round( ( $generated / $total ) * 100 ) : 0,
            'deepfake_pct'  => $total > 0 ? round( ( $deepfake / $total ) * 100 ) : 0,
            'last_updated'  => $stats['last_updated'] ?? current_time( 'mysql' ),
            'hash'          => substr( hash( 'sha256', get_option( 'megagovern_site_id', '' ) . ( $stats['last_updated'] ?? '' ) ), 0, 8 ),
        ];
        set_transient( 'megagovern_transparency_summary', $summary, HOUR_IN_SECONDS );
        return $summary;
    }
    public function clear_cache(): void {
        delete_transient( 'megagovern_transparency_summary' );
    }
    public function register_settings(): void {
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_position', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'bottom',
        ] );
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_bg_color', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#0052CC',
        ] );
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_text_color', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#ffffff',
        ] );
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_link_color', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#93c5fd',
        ] );
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_enabled', [
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => true,
        ] );
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_show_powered', [
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => true,
        ] );
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_policy_page', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ] );
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_custom_text', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );
        register_setting( 'megagovern_notice_settings', 'megagovern_notice_powered_text', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => __( 'Powered by MegaGovern', 'megagovern' ),
        ] );
    }
    public function enqueue_assets(): void {
        if ( is_admin() ) {
            return;
        }
        $s = $this->get_settings();
        if ( ! $s['enabled'] ) {
            return;
        }
        wp_enqueue_style(
            'megagovern-transparency-badge',
            MEGAGOVERN_URL . 'assets/css/transparency-badge.css',
            [],
            MEGAGOVERN_VERSION
        );
        wp_enqueue_script(
            'megagovern-transparency-badge',
            MEGAGOVERN_URL . 'assets/js/transparency-badge.js',
            [ 'jquery' ],
            MEGAGOVERN_VERSION,
            true
        );
        wp_localize_script( 'megagovern-transparency-badge', 'megagovernTransparency', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'megagovern_transparency' ),
            'dismissDays' => (int) get_option( 'megagovern_notice_dismiss_days', 30 ),
        ] );
    }
    public function enqueue_frontend_styles() {
        if ( is_admin() ) {
            return;
        }
        wp_enqueue_style( 'megagovern-transparency-badge' );
        $bg_color   = sanitize_hex_color( get_option( 'megagovern_notice_bg_color', '#0052CC' ) );
        $text_color = sanitize_hex_color( get_option( 'megagovern_notice_text_color', '#ffffff' ) );
        $link_color = sanitize_hex_color( get_option( 'megagovern_notice_link_color', '#93c5fd' ) );
        $custom_css = "
            :root {
                --mga-bg-color: {$bg_color} !important;
                --mga-text-color: {$text_color} !important;
                --mga-link-color: {$link_color} !important;
            }
        ";
        wp_add_inline_style( 'megagovern-transparency-badge', $custom_css );
    }
    public function render_floating_badge(): void {
        if ( is_admin() ) {
            return;
        }
        $s = $this->get_settings();
        if ( ! $s['enabled'] ) {
            return;
        }
        if ( isset( $_COOKIE['megagovern_transparency_dismissed'] ) ) {
            return;
        }
        $path = defined( 'MEGAGOVERN_PATH' ) ? MEGAGOVERN_PATH : plugin_dir_path( __DIR__ );
        $tpl = $path . 'templates/public/transparency-drawer.php';
        if ( ! file_exists( $tpl ) ) {
            $tpl = plugin_dir_path( __DIR__ ) . 'templates/public/transparency-drawer.php';
        }
        if ( file_exists( $tpl ) ) {
            include $tpl;
        }
    }
    public function ajax_dismiss(): void {
        check_ajax_referer( 'megagovern_transparency', 'nonce' );
        $days = (int) get_option( 'megagovern_notice_dismiss_days', 30 );
        $days = max( 1, min( 365, $days ) );
        setcookie(
            'megagovern_transparency_dismissed',
            '1',
            time() + ( $days * DAY_IN_SECONDS ),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
        wp_send_json_success( [ 'message' => __( 'Transparency badge dismissed.', 'megagovern' ) ] );
    }
}