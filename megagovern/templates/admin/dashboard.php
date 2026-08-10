<?php
/**
 * MegaGovern Dashboard — Mission Control V1.0.4
 *
 * @package MegaGovern
 * @since   1.0.4
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use MegaGovern\License;
use MegaGovern\Registry;
use MegaGovern\Governance;
use MegaGovern\Issues;
use MegaGovern\Alerts;
use MegaGovern\Helpers;
use MegaGovern\Score;
use MegaGovern\Crawler;
use MegaGovern\Verification;
// ═══════════════════════════════════════════
// 1. HELPER FUNCTIONS
// ═══════════════════════════════════════════
if ( ! function_exists( 'megagovern_get_local_site_id' ) ) {
    /**
     * Get or generate the local site ID.
     *
     * @return string Site ID.
     */
    function megagovern_get_local_site_id(): string {
        $megagovern_site_id = get_option( 'megagovern_site_id', '' );
        if ( empty( $megagovern_site_id ) ) {
            if ( function_exists( 'wp_generate_uuid' ) ) {
                $megagovern_uuid = wp_generate_uuid();
            } else {
                $megagovern_uuid = uniqid( 'mg_', true );
            }
            $megagovern_site_id = 'mg_' . substr( md5( get_home_url() . $megagovern_uuid ), 0, 16 );
            update_option( 'megagovern_site_id', $megagovern_site_id );
        }
        return $megagovern_site_id;
    }
}
if ( ! function_exists( 'megagovern_get_icon' ) ) {
    /**
     * Get an inline SVG icon.
     *
     * @param string $name Icon identifier.
     * @param string $size Width/height in pixels.
     * @return string SVG markup.
     */
    function megagovern_get_icon( string $name, string $size = '16' ): string {
        $megagovern_icons = array(
            'shield'           => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            'eye'              => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
            'database'         => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>',
            'file'             => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
            'clock'            => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            'check-circle'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>',
            'settings'         => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'edit'             => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
            'arrow-right'      => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
            'alert-circle'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
            'lock'             => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            'star'             => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            'refresh-cw'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
            'tag'              => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29a1 1 0 0 0 1.41 0l7-7a1 1 0 0 0 0-1.41L12 2z"/><polyline points="7 7 7.01 7"/></svg>',
            'file-text'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'bell'             => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
            'wrench'           => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
            'shield-off'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19.69 14a6.9 6.9 0 0 0 .31-2V5l-8-3-3.16 1.18"/><path d="M4.73 4.73 4 5v7c0 6 8 10 8 10a20.29 20.29 0 0 0 5.62-4.38"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
            'hand'             => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 11V6a2 2 0 0 0-4 0v1"/><path d="M14 10V4a2 2 0 0 0-4 0v2"/><path d="M10 10.5V6a2 2 0 0 0-4 0v6.5"/><path d="M18 8a2 2 0 0 1 4 0v6a8 8 0 0 1-8 8h-2c-2.21 0-4.21-.9-5.66-2.34L6 19.5"/><path d="M14 18v-3"/><path d="M10 18v-3"/></svg>',
        );
        return isset( $megagovern_icons[ $name ] ) ? $megagovern_icons[ $name ] : '';
    }
}
if ( ! function_exists( 'megagovern_get_action_icon_key' ) ) {
    /**
     * Get icon key for an action type.
     *
     * @param string $action Action type.
     * @return string Icon key.
     */
    function megagovern_get_action_icon_key( string $action ): string {
        $megagovern_map = array(
            'classified'           => 'tag',
            'reclassified'         => 'refresh-cw',
            'documented'           => 'database',
            'aitxt_published'      => 'file-text',
            'label_displayed'      => 'eye',
            'report_generated'     => 'file',
            'verification_updated' => 'lock',
            'alert_received'       => 'bell',
            'issue_fixed'          => 'check-circle',
            'services_updated'     => 'wrench',
            'crawler_blocked'      => 'shield-off',
            'media_classified'     => 'eye',
            'deepfake_flagged'     => 'alert-circle',
        );
        return isset( $megagovern_map[ $action ] ) ? $megagovern_map[ $action ] : 'file';
    }
}
// ═══════════════════════════════════════════
// 2. Capability Check
// ═══════════════════════════════════════════
if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'megagovern_manage_compliance' ) ) {
    wp_die( esc_html__( 'Access Denied', 'megagovern' ), esc_html__( 'Access Denied', 'megagovern' ), array( 'response' => 403 ) );
}
$megagovern_current_user_id = get_current_user_id();
// ═══════════════════════════════════════════
// 3. License
// ═══════════════════════════════════════════
$megagovern_flags     = License::get_flags();
$megagovern_is_pro    = isset( $megagovern_flags['is_pro'] ) ? (bool) $megagovern_flags['is_pro'] : false;
$megagovern_is_agency = isset( $megagovern_flags['is_agency'] ) ? (bool) $megagovern_flags['is_agency'] : false;
$megagovern_is_free   = isset( $megagovern_flags['is_free'] ) ? (bool) $megagovern_flags['is_free'] : true;
$megagovern_plan_name = License::get_plan_name();
// ═══════════════════════════════════════════
// 4. Cached Data — Refresh handled centrally in Admin.php::maybe_handle_refresh()
// ═══════════════════════════════════════════
$megagovern_cache_key = 'megagovern_dashboard_v5_' . $megagovern_current_user_id;
// ═══════════════════════════════════════════
// 5. Cached Data (NO SCANNER)
// ═══════════════════════════════════════════
$megagovern_cached = get_transient( $megagovern_cache_key );
if ( false === $megagovern_cached ) {
    $megagovern_cached = array(
        'score'     => Score::calculate(),
        'breakdown' => Score::breakdown(),
        'issues'    => Issues::check_all(),
        'alerts'    => Alerts::get_alerts(),
        'actions'   => Registry::get_recent( 8 ),
        'stats'     => Registry::get_stats(),
        'crawler'   => class_exists( '\MegaGovern\Crawler' ) ? ( new Crawler() )->get_health() : array(),
    );
    set_transient( $megagovern_cache_key, $megagovern_cached, 120 );
}
$megagovern_score       = isset( $megagovern_cached['score'] ) ? (int) $megagovern_cached['score'] : Score::calculate();
$megagovern_breakdown   = isset( $megagovern_cached['breakdown'] ) ? $megagovern_cached['breakdown'] : Score::breakdown();
$megagovern_issues      = isset( $megagovern_cached['issues'] ) ? $megagovern_cached['issues'] : array();
$megagovern_alerts      = isset( $megagovern_cached['alerts'] ) ? $megagovern_cached['alerts'] : array();
$megagovern_actions     = isset( $megagovern_cached['actions'] ) ? $megagovern_cached['actions'] : array();
$megagovern_stats       = isset( $megagovern_cached['stats'] ) ? $megagovern_cached['stats'] : array();
$megagovern_crawler     = isset( $megagovern_cached['crawler'] ) ? $megagovern_cached['crawler'] : array();
// ═══════════════════════════════════════════
// 6. Metrics
// ═══════════════════════════════════════════
$megagovern_total        = isset( $megagovern_stats['total'] ) ? (int) $megagovern_stats['total'] : 0;
$megagovern_undeclared   = Registry::count_undeclared();
$megagovern_score_color  = Helpers::score_color( $megagovern_score );
$megagovern_score_label  = Helpers::score_label( $megagovern_score );
$megagovern_last_updated = empty( $megagovern_stats['last_updated'] ) ? __( 'Just Now', 'megagovern' ) : human_time_diff( strtotime( $megagovern_stats['last_updated'] ) ) . ' ' . __( 'ago', 'megagovern' );
$megagovern_label_on   = (bool) get_option( 'megagovern_label_position' );
$megagovern_aitxt_on   = (bool) get_option( 'megagovern_auto_aitxt', true );
$megagovern_verify_on  = (bool) get_option( 'megagovern_auto_verify', true );
$megagovern_crawler_on = (bool) get_option( 'megagovern_crawler_enabled', true );
$megagovern_is_first_run = ( 0 === $megagovern_total && $megagovern_undeclared > 0 );
$megagovern_declarations_last_7_days = ( method_exists( '\MegaGovern\Registry', 'count_recent_by_action' ) )
    ? Registry::count_recent_by_action( 'classified', 7 )
    : 0;
$megagovern_coverage_estimate_text = '';
if ( $megagovern_undeclared > 0 && ! empty( $megagovern_declarations_last_7_days ) && $megagovern_declarations_last_7_days > 0 ) {
    $megagovern_daily_rate = $megagovern_declarations_last_7_days / 7;
    $megagovern_days_left  = $megagovern_daily_rate > 0 ? (int) ceil( $megagovern_undeclared / $megagovern_daily_rate ) : 0;
    if ( $megagovern_days_left > 0 && $megagovern_days_left <= 60 ) {
        $megagovern_coverage_estimate_text = sprintf(
            /* translators: %d: number of days left */
            _n( '~%d day left at your current pace', '~%d days left at your current pace', $megagovern_days_left, 'megagovern' ),
            $megagovern_days_left
        );
    }
}
$megagovern_post_ids = array_unique( array_filter( array_column( $megagovern_actions, 'post_id' ) ) );
$megagovern_titles   = array();
if ( ! empty( $megagovern_post_ids ) ) {
    $megagovern_posts = get_posts( array(
        'post__in'       => $megagovern_post_ids,
        'posts_per_page' => count( $megagovern_post_ids ),
        'fields'         => 'id=>post_title',
        'post_type'      => 'any',
        'post_status'    => 'any',
        'no_found_rows'  => true,
    ) );
    foreach ( $megagovern_posts as $megagovern_p ) {
        $megagovern_titles[ $megagovern_p->ID ] = $megagovern_p->post_title;
    }
}
$megagovern_last_declaration_text = __( 'No declarations yet', 'megagovern' );
foreach ( $megagovern_actions as $megagovern_a ) {
    if ( 'classified' === ( isset( $megagovern_a['action'] ) ? $megagovern_a['action'] : '' ) && ! empty( $megagovern_a['logged_at'] ) ) {
        $megagovern_last_declaration_text = human_time_diff( strtotime( $megagovern_a['logged_at'] ) ) . ' ' . __( 'ago', 'megagovern' );
        break;
    }
}
$megagovern_refresh_url          = wp_nonce_url( add_query_arg( 'refresh', '1', admin_url( 'admin.php?page=megagovern' ) ), 'megagovern_refresh_dashboard' );
$megagovern_upgrade_url          = ( $megagovern_is_free && function_exists( 'mga_fs' ) ) ? mga_fs()->get_upgrade_url() : '';
$megagovern_hash                 = substr( hash( 'sha256', megagovern_get_local_site_id() . ( isset( $megagovern_stats['last_updated'] ) ? $megagovern_stats['last_updated'] : '' ) ), 0, 16 );
$megagovern_gov_url              = admin_url( 'admin.php?page=megagovern-transparency&gtab=catalog' );
$megagovern_transparency_url     = Verification::get_transparency_url();
$megagovern_aitxt_url            = home_url( '/ai.txt' );
$megagovern_reports_url          = admin_url( 'admin.php?page=megagovern-reports' );
$megagovern_first_undeclared_url = admin_url( 'admin.php?page=megagovern-transparency&gtab=catalog' );
$megagovern_severity_levels = Helpers::severity_levels();
$megagovern_auto_fixable_issues = array_values( array_filter( $megagovern_issues, static function ( $issue ) {
    return 'auto' === ( isset( $issue['fix_type'] ) ? $issue['fix_type'] : '' );
} ) );
wp_localize_script( 'megagovern-dashboard', 'megaGovernDashboard', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'megagovern_dashboard_bulk' ),
) );
?>
<div class="wrap megagovern-dashboard">
    <hr class="wp-header-end">
    <!-- First-run onboarding banner -->
    <?php if ( $megagovern_is_first_run ) : ?>
        <div class="mga-row">
            <div class="mga-card mga-onboarding-banner">
                <p style="margin:0 0 10px;font-weight:600;font-size:14px;">
                    <?php echo wp_kses_post( megagovern_get_icon( 'hand', '16' ) ); ?> <?php esc_html_e( 'New here? Three steps to get started:', 'megagovern' ); ?>
                </p>
                <ol style="margin:0 0 12px 18px;padding:0;font-size:13px;line-height:1.8;">
                    <li>
                        <?php esc_html_e( 'Classify your first piece of content', 'megagovern' ); ?>
                        — <a href="<?php echo esc_url( $megagovern_first_undeclared_url ); ?>"><?php esc_html_e( 'Go', 'megagovern' ); ?> →</a>
                    </li>
                    <li>
                        <?php esc_html_e( 'Confirm transparency labels are enabled', 'megagovern' ); ?>
                        — <a href="<?php echo esc_url( admin_url( 'admin.php?page=megagovern-settings&stab=content' ) ); ?>"><?php esc_html_e( 'Go', 'megagovern' ); ?> →</a>
                    </li>
                    <li>
                        <?php esc_html_e( 'View your public transparency page', 'megagovern' ); ?>
                        — <a href="<?php echo esc_url( $megagovern_transparency_url ); ?>" target="_blank"><?php esc_html_e( 'View', 'megagovern' ); ?> →</a>
                    </li>
                </ol>
            </div>
        </div>
    <?php endif; ?>
    <!-- Quick Status strip -->
    <div class="mga-row">
        <div class="mga-card mga-quick-status">
            <div class="mga-quick-status-row">
                <span class="mga-quick-status-item <?php echo $megagovern_label_on ? 'on' : 'off'; ?>"><?php echo wp_kses_post( megagovern_get_icon( 'check-circle', '14' ) ); ?> <?php esc_html_e( 'Labels', 'megagovern' ); ?></span>
                <span class="mga-quick-status-item <?php echo $megagovern_aitxt_on ? 'on' : 'off'; ?>"><?php echo wp_kses_post( megagovern_get_icon( 'check-circle', '14' ) ); ?> <?php esc_html_e( 'AI.txt', 'megagovern' ); ?></span>
                <span class="mga-quick-status-item <?php echo $megagovern_verify_on ? 'on' : 'off'; ?>"><?php echo wp_kses_post( megagovern_get_icon( 'check-circle', '14' ) ); ?> <?php esc_html_e( 'Verification', 'megagovern' ); ?></span>
                <span class="mga-quick-status-item <?php echo $megagovern_crawler_on ? 'on' : 'off'; ?>"><?php echo wp_kses_post( megagovern_get_icon( 'check-circle', '14' ) ); ?> <?php esc_html_e( 'Crawler Protection', 'megagovern' ); ?></span>
                <a href="<?php echo esc_url( $megagovern_transparency_url ); ?>" target="_blank" class="mga-quick-status-link">
                    <?php echo wp_kses_post( megagovern_get_icon( 'eye', '14' ) ); ?> <?php esc_html_e( 'View Public Page', 'megagovern' ); ?>
                </a>
            </div>
        </div>
    </div>
    <div class="mga-row mga-row-2col-d">
        <!-- Score Card -->
        <div class="mga-card mga-card-score" style="border-top:4px solid <?php echo esc_attr( $megagovern_score_color ); ?>;">
            <div class="mga-card-score-inner">
                <div class="mga-score-ring" style="--score:<?php echo esc_attr( (string) $megagovern_score ); ?>;--color:<?php echo esc_attr( $megagovern_score_color ); ?>;"><span><?php echo esc_html( (string) $megagovern_score ); ?>%</span></div>
                <h3><?php echo esc_html( $megagovern_score_label ); ?></h3>
                <p><?php esc_html_e( 'Governance Score', 'megagovern' ); ?></p>
                <p class="mga-score-freshness"><?php echo wp_kses_post( megagovern_get_icon( 'clock', '12' ) ); ?> <?php echo esc_html( $megagovern_last_declaration_text ); ?></p>
            </div>
            <div class="mga-card-score-stats">
                <div><strong><?php echo esc_html( number_format_i18n( $megagovern_total ) ); ?></strong><small><?php esc_html_e( 'Declared', 'megagovern' ); ?></small></div>
                <div><strong><?php echo esc_html( number_format_i18n( $megagovern_undeclared ) ); ?></strong><small><?php esc_html_e( 'Pending', 'megagovern' ); ?></small></div>
                <div><strong><?php echo esc_html( (string) count( $megagovern_issues ) ); ?></strong><small><?php esc_html_e( 'Issues', 'megagovern' ); ?></small></div>
            </div>
            <?php if ( $megagovern_coverage_estimate_text ) : ?>
                <p class="mga-coverage-estimate"><?php echo esc_html( $megagovern_coverage_estimate_text ); ?> <?php esc_html_e( 'to full coverage', 'megagovern' ); ?></p>
            <?php endif; ?>
        </div>
        <!-- Content Declaration Status -->
        <div class="mga-card">
            <h3 class="mga-card-title"><?php echo wp_kses_post( megagovern_get_icon( 'database', '16' ) ); ?> <?php esc_html_e( 'Content Status', 'megagovern' ); ?></h3>
            <?php if ( $megagovern_undeclared > 0 ) : ?>
                <p>
                    <?php
                    /* translators: %d: number of undeclared items */
                    printf( esc_html__( '%d items need declaration.', 'megagovern' ), esc_html( $megagovern_undeclared ) );
                    ?>
                </p>
                <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                    <a href="<?php echo esc_url( $megagovern_gov_url . '&declaration_filter=undeclared' ); ?>" class="button button-primary">
                        <?php echo wp_kses_post( megagovern_get_icon( 'edit', '14' ) ); ?>
                        <?php esc_html_e( 'Declare Now', 'megagovern' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $megagovern_gov_url ); ?>" class="button button-secondary">
                        <?php echo wp_kses_post( megagovern_get_icon( 'database', '14' ) ); ?>
                        <?php esc_html_e( 'Full Content Hub', 'megagovern' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <p style="color:var(--mga-success);">
                    <?php echo wp_kses_post( megagovern_get_icon( 'check-circle', '16' ) ); ?>
                    <?php esc_html_e( 'All content declared. Great job!', 'megagovern' ); ?>
                </p>
            <?php endif; ?>
        </div>
        <!-- Action Center -->
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title"><?php esc_html_e( 'Action Center', 'megagovern' ); ?></h3>
                <?php if ( ! empty( $megagovern_issues ) ) : ?><span class="mga-badge-count"><?php echo esc_html( (string) count( $megagovern_issues ) ); ?></span><?php endif; ?>
            </div>
            <?php if ( count( $megagovern_auto_fixable_issues ) > 1 ) : ?>
                <button type="button" class="button button-secondary" id="mga-fix-all-btn" style="margin-bottom:10px;">
                    <?php echo wp_kses_post( megagovern_get_icon( 'check-circle', '14' ) ); ?>
                    <?php
                    /* translators: %d: number of auto-fixable issues */
                    printf( esc_html__( 'Fix All Auto-Fixable (%d)', 'megagovern' ), esc_html( count( $megagovern_auto_fixable_issues ) ) );
                    ?>
                </button>
            <?php endif; ?>
            <?php if ( ! empty( $megagovern_issues ) ) : ?>
                <div class="mga-issues-list">
                    <?php foreach ( $megagovern_issues as $megagovern_issue ) :
                        $megagovern_sev = isset( $megagovern_severity_levels[ isset( $megagovern_issue['severity'] ) ? $megagovern_issue['severity'] : 'low' ] )
                            ? $megagovern_severity_levels[ $megagovern_issue['severity'] ]
                            : $megagovern_severity_levels['low'];
                        $megagovern_issue_link = '#';
                        $megagovern_issue_id   = isset( $megagovern_issue['id'] ) ? $megagovern_issue['id'] : '';
                        if ( strpos( $megagovern_issue_id, 'undeclared' ) !== false || strpos( $megagovern_issue_id, 'declaration' ) !== false || strpos( $megagovern_issue_id, 'post' ) !== false ) {
                            $megagovern_issue_link = admin_url( 'admin.php?page=megagovern-transparency&gtab=catalog' );
                        } elseif ( strpos( $megagovern_issue_id, 'alert' ) !== false || strpos( $megagovern_issue_id, 'regulatory' ) !== false ) {
                            $megagovern_issue_link = admin_url( 'admin.php?page=megagovern-alerts' );
                        } elseif ( strpos( $megagovern_issue_id, 'media' ) !== false ) {
                            $megagovern_issue_link = admin_url( 'admin.php?page=megagovern-transparency&gtab=catalog' );
                        }
                    ?>
                        <div class="mga-issue-row">
                            <span class="mga-issue-dot" style="background:<?php echo esc_attr( isset( $megagovern_sev['color'] ) ? $megagovern_sev['color'] : '#dba617' ); ?>;"></span>
                            <span class="mga-issue-text"><?php echo isset( $megagovern_issue['title'] ) ? esc_html( $megagovern_issue['title'] ) : ''; ?></span>
                            <?php if ( 'auto' === ( isset( $megagovern_issue['fix_type'] ) ? $megagovern_issue['fix_type'] : '' ) ) : ?>
                                <button class="button button-small auto-fix-btn" data-issue-id="<?php echo esc_attr( $megagovern_issue_id ); ?>"><?php esc_html_e( 'Fix', 'megagovern' ); ?></button>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $megagovern_issue_link ); ?>" class="button button-small"><?php esc_html_e( 'Go', 'megagovern' ); ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="mga-ok"><?php echo wp_kses_post( megagovern_get_icon( 'check-circle', '16' ) ); ?> <?php esc_html_e( 'All systems in good standing.', 'megagovern' ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="mga-row mga-row-3col">
        <!-- Compliance Journey -->
        <div class="mga-card">
            <h3 class="mga-card-title"><?php esc_html_e( 'Transparency Journey', 'megagovern' ); ?></h3>
            <?php if ( isset( $megagovern_breakdown['components'] ) && is_array( $megagovern_breakdown['components'] ) ) : ?>
                <?php foreach ( $megagovern_breakdown['components'] as $megagovern_item ) : ?>
                    <div class="mga-journey-row">
                        <div class="mga-journey-label"><span><?php echo isset( $megagovern_item['label'] ) ? esc_html( $megagovern_item['label'] ) : ''; ?></span><span><?php echo isset( $megagovern_item['pct'] ) ? esc_html( (string) $megagovern_item['pct'] ) : '0'; ?>%</span></div>
                        <div class="mga-journey-track"><div class="mga-journey-fill" style="width:<?php echo isset( $megagovern_item['pct'] ) ? esc_attr( (string) $megagovern_item['pct'] ) : '0'; ?>%;"></div></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <!-- Recent Activity -->
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title"><?php esc_html_e( 'Recent Activity', 'megagovern' ); ?></h3>
                <span class="mga-hash" title="<?php esc_attr_e( 'Verification hash', 'megagovern' ); ?>">
                    <?php echo wp_kses_post( megagovern_get_icon( 'lock', '14' ) ); ?>
                    <code><?php echo esc_html( $megagovern_hash ); ?></code>
                </span>
            </div>
            <?php if ( ! empty( $megagovern_actions ) ) : ?>
                <div class="mga-timeline">
                    <?php foreach ( array_slice( $megagovern_actions, 0, 6 ) as $megagovern_a ) : ?>
                        <?php
                            $megagovern_action_type = isset( $megagovern_a['action'] ) ? $megagovern_a['action'] : '';
                            $megagovern_icon_key    = megagovern_get_action_icon_key( $megagovern_action_type );
                        ?>
                        <div class="mga-timeline-item">
                            <span><?php echo esc_html( Helpers::action_label( $megagovern_action_type ) ); ?></span>
                            <?php if ( ! empty( $megagovern_a['post_id'] ) && isset( $megagovern_titles[ $megagovern_a['post_id'] ] ) ) : ?>
                                <em>"<?php echo esc_html( $megagovern_titles[ $megagovern_a['post_id'] ] ); ?>"</em>
                            <?php endif; ?>
                            <time>
                                <?php
                                    echo ! empty( $megagovern_a['logged_at'] )
                                        ? esc_html( human_time_diff( strtotime( $megagovern_a['logged_at'] ) ) ) . ' ' . esc_html__( 'ago', 'megagovern' )
                                        : '';
                                ?>
                            </time>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="mga-empty-text"><?php esc_html_e( 'No activity yet.', 'megagovern' ); ?></p>
            <?php endif; ?>
        </div>
        <!-- Quick Links -->
        <div class="mga-card">
            <h3 class="mga-card-title"><?php esc_html_e( 'Quick Links', 'megagovern' ); ?></h3>
            <div class="mga-links-grid">
                <a href="<?php echo esc_url( $megagovern_gov_url ); ?>"><?php echo wp_kses_post( megagovern_get_icon( 'database', '16' ) ); ?><?php esc_html_e( 'Content Hub', 'megagovern' ); ?></a>
                <a href="<?php echo esc_url( $megagovern_transparency_url ); ?>" target="_blank"><?php echo wp_kses_post( megagovern_get_icon( 'eye', '16' ) ); ?><?php esc_html_e( 'Verification', 'megagovern' ); ?></a>
                <a href="<?php echo esc_url( $megagovern_aitxt_url ); ?>" target="_blank"><?php echo wp_kses_post( megagovern_get_icon( 'file', '16' ) ); ?><?php esc_html_e( 'AI.txt', 'megagovern' ); ?></a>
                <a href="<?php echo esc_url( $megagovern_reports_url ); ?>"><?php echo wp_kses_post( megagovern_get_icon( 'file', '16' ) ); ?><?php esc_html_e( 'Reports', 'megagovern' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=megagovern-settings' ) ); ?>"><?php echo wp_kses_post( megagovern_get_icon( 'settings', '16' ) ); ?><?php esc_html_e( 'Settings', 'megagovern' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"><?php echo wp_kses_post( megagovern_get_icon( 'edit', '16' ) ); ?><?php esc_html_e( 'New Post', 'megagovern' ); ?></a>
            </div>
        </div>
    </div>
    <div class="mga-row mga-row-1col"></div>
    <?php if ( isset( $megagovern_is_free ) && $megagovern_is_free ) : ?>
        <?php
            $megagovern_upgrade_pro_url    = ! empty( $megagovern_upgrade_url ) ? $megagovern_upgrade_url : 'https://megagovern.com/pricing/?utm_source=plugin_admin&utm_medium=banner&utm_campaign=upgrade_pro';
            $megagovern_upgrade_agency_url = 'https://megagovern.com/pricing/?utm_source=plugin_admin&utm_medium=banner&utm_campaign=upgrade_agency';
        ?>
        <div class="mga-row">
            <div class="mga-card mga-card-upgrade">
                <div class="mga-upgrade-header">
                    <div class="mga-upgrade-title-group">
                        <span class="mga-star-icon">
                            <?php echo function_exists( 'megagovern_get_icon' ) ? wp_kses_post( megagovern_get_icon( 'star', '22' ) ) : '★'; ?>
                        </span>
                        <h3><?php esc_html_e( 'Unlock MegaGovern Pro & Agency Power', 'megagovern' ); ?></h3>
                    </div>
                    <span class="mga-upgrade-badge"><?php esc_html_e( '80% EU AI Act Coverage', 'megagovern' ); ?></span>
                </div>
                <p class="mga-upgrade-desc">
                    <?php esc_html_e( 'Automate EU AI Act Article 50 compliance, generate cryptographically signed PDF audit bundles, and scan image metadata locally with zero third-party cloud tracking.', 'megagovern' ); ?>
                </p>
                <ul class="mga-upgrade-features">
                    <li><span class="mga-check-icon">✓</span> <?php esc_html_e( 'Custom AI Policy & Modal Editor', 'megagovern' ); ?></li>
                    <li><span class="mga-check-icon">✓</span> <?php esc_html_e( 'PDF Reports & Cryptographic Evidence Bundles', 'megagovern' ); ?></li>
                    <li><span class="mga-check-icon">✓</span> <?php esc_html_e( 'EXIF & C2PA Image Provenance Scanner', 'megagovern' ); ?></li>
                    <li><span class="mga-check-icon">✓</span> <?php esc_html_e( 'Automatic AI.txt & LLMs.txt Generation', 'megagovern' ); ?></li>
                    <li><span class="mga-check-icon">✓</span> <?php esc_html_e( 'Custom Label Text & Brand Controls', 'megagovern' ); ?></li>
                    <li><span class="mga-check-icon">✓</span> <?php esc_html_e( '365-Day Database Evidence Preservation', 'megagovern' ); ?></li>
                    <li><span class="mga-check-icon">✓</span> <?php esc_html_e( 'Sentence-Level AI Probability Smart Scan', 'megagovern' ); ?></li>
                    <li><span class="mga-check-icon">✓</span> <?php esc_html_e( 'Priority B2B & Developer Support', 'megagovern' ); ?></li>
                </ul>
                <div class="mga-upgrade-action-wrapper">
                    <div class="mga-upgrade-primary-action">
                        <a href="<?php echo esc_url( $megagovern_upgrade_pro_url ); ?>" target="_blank" rel="noopener noreferrer" class="mga-btn-upgrade">
                            <span><?php esc_html_e( 'Upgrade to Pro ($89/yr)', 'megagovern' ); ?></span>
                            <span class="mga-btn-arrow">
                                <?php echo function_exists( 'megagovern_get_icon' ) ? wp_kses_post( megagovern_get_icon( 'arrow-right', '16' ) ) : '→'; ?>
                            </span>
                        </a>
                        <a href="<?php echo esc_url( $megagovern_upgrade_agency_url ); ?>" target="_blank" rel="noopener noreferrer" class="mga-btn-agency-link">
                            <?php esc_html_e( 'Managing 20+ Client Sites? View Agency License ($499/yr)', 'megagovern' ); ?> →
                        </a>
                    </div>
                    <div class="mga-trust-reassurance">
                        <span class="mga-trust-item">🔒 <?php esc_html_e( '100% Local-First Architecture', 'megagovern' ); ?></span>
                        <span class="mga-trust-divider">•</span>
                        <span class="mga-trust-item">⚡ <?php esc_html_e( 'Instant License Key Delivery', 'megagovern' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
(function($) {
    'use strict';
    $('#mga-fix-all-btn').on('click', function() {
        var $btn = $(this);
        var $fixButtons = $('.auto-fix-btn');
        if ($fixButtons.length === 0) { return; }
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Fixing...', 'megagovern' ) ); ?>');
        var index = 0;
        function fixNext() {
            if (index >= $fixButtons.length) {
                window.location.reload();
                return;
            }
            $($fixButtons[index]).trigger('click');
            index++;
            setTimeout(fixNext, 400);
        }
        fixNext();
    });
})(jQuery);
</script>