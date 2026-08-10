<?php
/**
 * MegaGovern Agency Hub — V1.0.4
 * Tabbed: Dashboard + Sites + White Label + Bulk Reports + Team + Settings
 * LOCAL-FIRST — No external API calls.
 * LUCIDE ICONS — No Dashicons, no emojis.
 *
 * @package MegaGovern
 * @since   1.0.4
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use MegaGovern\Agency;
use MegaGovern\License;
use MegaGovern\Issues;
use MegaGovern\Alerts;
// ─── SECURITY: Master Capability Check ───
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die(
        esc_html__( 'You do not have permission to manage agency settings.', 'megagovern' ),
        esc_html__( 'Access Denied', 'megagovern' ),
        array( 'response' => 403 )
    );
}
// ═══════════════════════════════════════════
// 1. Active Tab with Nonce Verification
// ═══════════════════════════════════════════
$megagovern_tab = isset( $_GET['atab'] ) ? sanitize_text_field( wp_unslash( $_GET['atab'] ) ) : 'dashboard';
// ─── Verify nonce for tab navigation if filters are present ───
if ( isset( $_GET['atab'] ) && isset( $_GET['_wpnonce'] ) ) {
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'megagovern_agency_tab' ) ) {
        $megagovern_tab = 'dashboard';
    }
}
// ═══════════════════════════════════════════
// 2. Local Site ID (No API)
// ═══════════════════════════════════════════
$megagovern_site_id = get_option( 'megagovern_site_id', '' );
if ( empty( $megagovern_site_id ) ) {
    $megagovern_site_id = 'mg_' . substr( md5( get_home_url() . wp_generate_uuid4() ), 0, 16 );
    update_option( 'megagovern_site_id', $megagovern_site_id );
}
// ═══════════════════════════════════════════
// 3. Data
// ═══════════════════════════════════════════
$megagovern_summary   = Agency::get_summary();
$megagovern_sites     = Agency::get_sites();
$megagovern_issues    = Issues::check_all();
$megagovern_alerts    = Alerts::get_alerts();
$megagovern_limit     = Agency::get_max_sites();
$megagovern_count     = count( $megagovern_sites );
$megagovern_is_agency = class_exists( '\MegaGovern\License' ) && License::is_agency();
$megagovern_wl        = Agency::get_white_label();
$megagovern_team      = Agency::get_team();
$megagovern_users     = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author' ) ) );
// ═══════════════════════════════════════════
// 3a. Sort Sites by Score (Worst First)
// ═══════════════════════════════════════════
uasort( $megagovern_sites, function( $a, $b ) {
    $score_a = isset( $a['score'] ) ? (int) $a['score'] : 0;
    $score_b = isset( $b['score'] ) ? (int) $b['score'] : 0;
    return $score_a <=> $score_b;
} );
// ═══════════════════════════════════════════
// 4. Handle Actions with Nonce Verification
// ═══════════════════════════════════════════
$megagovern_msg      = '';
$megagovern_msg_type = 'success';
if ( isset( $_POST['agency_action'] ) ) {
    $megagovern_action = sanitize_text_field( wp_unslash( $_POST['agency_action'] ) );
    if ( 'add_site' === $megagovern_action && isset( $_POST['site_url'] ) ) {
        check_admin_referer( 'agency_add_site' );
        if ( $megagovern_count >= $megagovern_limit ) {
            $megagovern_msg      = __( 'Site limit reached. Upgrade to add more sites.', 'megagovern' );
            $megagovern_msg_type = 'warning';
        } else {
            $megagovern_site_url  = isset( $_POST['site_url'] ) ? esc_url_raw( wp_unslash( $_POST['site_url'] ) ) : '';
            $megagovern_site_name = isset( $_POST['site_name'] ) ? sanitize_text_field( wp_unslash( $_POST['site_name'] ) ) : '';
            if ( empty( $megagovern_site_url ) || ! filter_var( $megagovern_site_url, FILTER_VALIDATE_URL ) ) {
                $megagovern_msg      = __( 'Invalid site URL. Please enter a valid URL.', 'megagovern' );
                $megagovern_msg_type = 'error';
            } else {
                Agency::add_site( $megagovern_site_url, $megagovern_site_name );
                $megagovern_msg      = __( 'Site added.', 'megagovern' );
                $megagovern_msg_type = 'success';
                $megagovern_sites    = Agency::get_sites();
                $megagovern_count    = count( $megagovern_sites );
                uasort( $megagovern_sites, function( $a, $b ) {
                    $score_a = isset( $a['score'] ) ? (int) $a['score'] : 0;
                    $score_b = isset( $b['score'] ) ? (int) $b['score'] : 0;
                    return $score_a <=> $score_b;
                } );
            }
        }
    }
    if ( 'remove_site' === $megagovern_action && isset( $_POST['site_id'] ) ) {
        check_admin_referer( 'agency_remove_site' );
        Agency::remove_site( sanitize_text_field( wp_unslash( $_POST['site_id'] ) ) );
        $megagovern_msg      = __( 'Site removed.', 'megagovern' );
        $megagovern_msg_type = 'success';
        $megagovern_sites    = Agency::get_sites();
        $megagovern_count    = count( $megagovern_sites );
    }
    if ( 'save_wl' === $megagovern_action ) {
        check_admin_referer( 'agency_wl_save' );
        $megagovern_agency_name   = isset( $_POST['agency_name'] ) ? sanitize_text_field( wp_unslash( $_POST['agency_name'] ) ) : '';
        $megagovern_agency_logo   = isset( $_POST['agency_logo'] ) ? esc_url_raw( wp_unslash( $_POST['agency_logo'] ) ) : '';
        $megagovern_agency_color  = isset( $_POST['agency_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['agency_color'] ) ) : '';
        $megagovern_agency_footer = isset( $_POST['agency_footer'] ) ? sanitize_text_field( wp_unslash( $_POST['agency_footer'] ) ) : '';
        $megagovern_hide_branding = isset( $_POST['hide_branding'] ) && ! empty( $_POST['hide_branding'] );
        update_option( 'megagovern_wl_name', $megagovern_agency_name );
        update_option( 'megagovern_wl_logo', $megagovern_agency_logo );
        update_option( 'megagovern_wl_color', $megagovern_agency_color );
        update_option( 'megagovern_wl_footer', $megagovern_agency_footer );
        update_option( 'megagovern_wl_hide', $megagovern_hide_branding );
        $megagovern_wl       = Agency::get_white_label();
        $megagovern_msg      = __( 'White Label saved.', 'megagovern' );
        $megagovern_msg_type = 'success';
    }
    if ( 'add_member' === $megagovern_action && isset( $_POST['user_id'] ) ) {
        check_admin_referer( 'agency_team' );
        $megagovern_user_id = intval( wp_unslash( $_POST['user_id'] ) );
        $megagovern_role    = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : 'manager';
        Agency::add_team_member( $megagovern_user_id, $megagovern_role );
        $megagovern_team     = Agency::get_team();
        $megagovern_msg      = __( 'Member added.', 'megagovern' );
        $megagovern_msg_type = 'success';
    }
    if ( 'remove_member' === $megagovern_action && isset( $_POST['user_id'] ) ) {
        check_admin_referer( 'agency_team' );
        Agency::remove_team_member( intval( wp_unslash( $_POST['user_id'] ) ) );
        $megagovern_team     = Agency::get_team();
        $megagovern_msg      = __( 'Member removed.', 'megagovern' );
        $megagovern_msg_type = 'success';
    }
}
// ═══════════════════════════════════════════
// 5. URL Helper
// ═══════════════════════════════════════════
function megagovern_agency_tab_url( string $atab ): string {
    $megagovern_nonce = wp_create_nonce( 'megagovern_agency_tab' );
    return esc_url( add_query_arg( array( 'page' => 'megagovern-agency', 'atab' => $atab, '_wpnonce' => $megagovern_nonce ), admin_url( 'admin.php' ) ) );
}
// ─── LUCIDE ICON HELPER ───
if ( ! function_exists( 'megagovern_agency_icon' ) ) {
    function megagovern_agency_icon( string $name, string $size = '16' ): string {
        $megagovern_icons = array(
            'dashboard'      => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
            'sites'          => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2"/><line x1="2" y1="9" x2="22" y2="9"/><line x1="9" y1="2" x2="9" y2="22"/></svg>',
            'whitelabel'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
            'reports'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'team'           => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'settings'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'check-circle'   => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>',
            'alert-triangle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            'plus'           => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
            'trash'          => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
        );
        return isset( $megagovern_icons[ $name ] ) ? $megagovern_icons[ $name ] : '';
    }
}
// ─── Usage Warning Banner ───
$megagovern_show_usage_warning = ( $megagovern_count >= ( $megagovern_limit - 2 ) && $megagovern_count < $megagovern_limit );
// Report nonce
$megagovern_report_nonce = wp_create_nonce( 'megagovern_agency_report' );
?>
<div class="wrap megagovern-agency">
    <!-- HEADER -->
    <hr class="wp-header-end">
    <?php if ( ! empty( $megagovern_msg ) ) : ?>
        <div class="mga-notice mga-notice--<?php echo esc_attr( $megagovern_msg_type ); ?>">
            <div class="mga-notice-content"><?php echo esc_html( $megagovern_msg ); ?></div>
        </div>
    <?php endif; ?>
    <?php if ( $megagovern_show_usage_warning ) : ?>
        <div class="mga-notice mga-notice--warning" style="margin-bottom:20px;">
            <div class="mga-notice-content">
                <?php
                $megagovern_remaining = $megagovern_limit - $megagovern_count;
                printf(
                    /* translators: 1: current site count, 2: max site limit, 3: remaining sites */
                    esc_html__( 'You\'re using %1$d/%2$d sites — only %3$d site(s) remaining.', 'megagovern' ),
                    (int) $megagovern_count,
                    (int) $megagovern_limit,
                    (int) $megagovern_remaining
                );
                ?>
            </div>
        </div>
    <?php endif; ?>
    <!-- TAB NAVIGATION -->
    <nav class="mga-tabs">
        <a href="<?php echo esc_url( megagovern_agency_tab_url( 'dashboard' ) ); ?>" class="mga-tab <?php echo 'dashboard' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
            <?php echo wp_kses_post( megagovern_agency_icon( 'dashboard', '16' ) ); ?>
            <?php esc_html_e( 'Dashboard', 'megagovern' ); ?>
        </a>
        <a href="<?php echo esc_url( megagovern_agency_tab_url( 'sites' ) ); ?>" class="mga-tab <?php echo 'sites' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
            <?php echo wp_kses_post( megagovern_agency_icon( 'sites', '16' ) ); ?>
            <?php esc_html_e( 'Sites', 'megagovern' ); ?> (<?php echo (int) $megagovern_count; ?>)
        </a>
        <a href="<?php echo esc_url( megagovern_agency_tab_url( 'whitelabel' ) ); ?>" class="mga-tab <?php echo 'whitelabel' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
            <?php echo wp_kses_post( megagovern_agency_icon( 'whitelabel', '16' ) ); ?>
            <?php esc_html_e( 'White Label', 'megagovern' ); ?>
        </a>
        <a href="<?php echo esc_url( megagovern_agency_tab_url( 'reports' ) ); ?>" class="mga-tab <?php echo 'reports' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
            <?php echo wp_kses_post( megagovern_agency_icon( 'reports', '16' ) ); ?>
            <?php esc_html_e( 'Bulk Reports', 'megagovern' ); ?>
        </a>
        <a href="<?php echo esc_url( megagovern_agency_tab_url( 'team' ) ); ?>" class="mga-tab <?php echo 'team' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
            <?php echo wp_kses_post( megagovern_agency_icon( 'team', '16' ) ); ?>
            <?php esc_html_e( 'Team', 'megagovern' ); ?>
        </a>
        <a href="<?php echo esc_url( megagovern_agency_tab_url( 'settings' ) ); ?>" class="mga-tab <?php echo 'settings' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
            <?php echo wp_kses_post( megagovern_agency_icon( 'settings', '16' ) ); ?>
            <?php esc_html_e( 'Settings', 'megagovern' ); ?>
        </a>
    </nav>
    <!-- ═══════════════════════════════════════ -->
    <!-- TAB: DASHBOARD                          -->
    <!-- ═══════════════════════════════════════ -->
    <?php if ( 'dashboard' === $megagovern_tab ) : ?>
        <!-- Overview Metrics -->
        <div class="mga-row mga-row-4col" style="margin-bottom:20px;">
            <div class="mga-card" style="text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--mga-accent);"><?php echo (int) $megagovern_summary['total']; ?></div>
                <div style="font-size:11px;color:var(--mga-text-muted);"><?php esc_html_e( 'Total Sites', 'megagovern' ); ?></div>
            </div>
            <div class="mga-card" style="text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--mga-success);"><?php echo (int) $megagovern_summary['good']; ?></div>
                <div style="font-size:11px;color:var(--mga-text-muted);"><?php esc_html_e( 'Compliant', 'megagovern' ); ?></div>
            </div>
            <div class="mga-card" style="text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--mga-warning);"><?php echo (int) $megagovern_summary['warning']; ?></div>
                <div style="font-size:11px;color:var(--mga-text-muted);"><?php esc_html_e( 'Attention', 'megagovern' ); ?></div>
            </div>
            <div class="mga-card" style="text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--mga-danger);"><?php echo (int) $megagovern_summary['critical']; ?></div>
                <div style="font-size:11px;color:var(--mga-text-muted);"><?php esc_html_e( 'Critical', 'megagovern' ); ?></div>
            </div>
        </div>
        <!-- Sites List (Sorted by Score — Worst First) -->
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title"><?php esc_html_e( 'Managed Sites', 'megagovern' ); ?></h3>
                <span><?php echo (int) $megagovern_count; ?>/<?php echo (int) $megagovern_limit; ?></span>
            </div>
            <?php if ( ! empty( $megagovern_sites ) ) : ?>
                <table class="mga-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Site', 'megagovern' ); ?></th>
                            <th><?php esc_html_e( 'Score', 'megagovern' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'megagovern' ); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $megagovern_sites as $megagovern_s ) :
                            $megagovern_sc  = isset( $megagovern_s['score'] ) ? (int) $megagovern_s['score'] : 0;
                            $megagovern_clr = $megagovern_sc >= 85 ? '#059669' : ( $megagovern_sc >= 50 ? '#d97706' : '#dc2626' );
                            $megagovern_lbl = $megagovern_sc >= 85 ? __( 'Good', 'megagovern' ) : ( $megagovern_sc >= 50 ? __( 'Fair', 'megagovern' ) : __( 'Poor', 'megagovern' ) );
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $megagovern_s['name'] ); ?></strong>
                                    <div style="font-size:10px;color:var(--mga-text-muted);"><?php echo esc_html( $megagovern_s['url'] ); ?></div>
                                </td>
                                <td style="color:<?php echo esc_attr( $megagovern_clr ); ?>;font-weight:600;"><?php echo (int) $megagovern_sc; ?>%</td>
                                <td>
                                    <span class="mga-pill" style="background:<?php echo esc_attr( $megagovern_clr ); ?>10;color:<?php echo esc_attr( $megagovern_clr ); ?>;">
                                        <?php echo esc_html( $megagovern_lbl ); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( $megagovern_s['url'] . '/wp-admin/admin.php?page=megagovern' ); ?>"
                                       target="_blank" rel="noopener" class="button button-small">
                                        <?php esc_html_e( 'Open', 'megagovern' ); ?> &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="mga-empty-text"><?php esc_html_e( 'No sites added.', 'megagovern' ); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <!-- ═══════════════════════════════════════ -->
    <!-- TAB: SITES                              -->
    <!-- ═══════════════════════════════════════ -->
    <?php if ( 'sites' === $megagovern_tab ) : ?>
        <!-- Usage Bar -->
        <div class="mga-card" style="margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:12px;font-weight:600;"><?php echo (int) $megagovern_count; ?>/<?php echo (int) $megagovern_limit; ?> <?php esc_html_e( 'sites used', 'megagovern' ); ?></span>
                <span style="font-size:11px;color:var(--mga-text-muted);"><?php echo (int) ( $megagovern_limit - $megagovern_count ); ?> <?php esc_html_e( 'remaining', 'megagovern' ); ?></span>
            </div>
            <div class="mga-usage-track">
                <div class="mga-usage-fill" style="width:<?php echo esc_attr( (string) ( ( $megagovern_count / $megagovern_limit ) * 100 ) ); ?>%;"></div>
            </div>
        </div>
        <!-- Add Site -->
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title"><?php echo wp_kses_post( megagovern_agency_icon( 'plus', '16' ) ); ?> <?php esc_html_e( 'Add Site', 'megagovern' ); ?></h3>
            </div>
            <?php if ( $megagovern_count < $megagovern_limit ) : ?>
                <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <?php wp_nonce_field( 'agency_add_site' ); ?>
                    <input type="hidden" name="agency_action" value="add_site">
                    <input type="url" name="site_url" placeholder="https://client.com" required style="flex:1;min-width:200px;" class="mga-gov-input">
                    <input type="text" name="site_name" placeholder="<?php esc_attr_e( 'Site Name', 'megagovern' ); ?>" style="flex:1;min-width:150px;" class="mga-gov-input">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Add', 'megagovern' ); ?></button>
                </form>
            <?php else : ?>
                <p style="color:var(--mga-warning);font-size:12px;"><?php esc_html_e( 'Site limit reached.', 'megagovern' ); ?></p>
            <?php endif; ?>
        </div>
        <!-- Sites List -->
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title"><?php esc_html_e( 'Managed Sites', 'megagovern' ); ?></h3>
            </div>
            <?php if ( ! empty( $megagovern_sites ) ) : ?>
                <table class="mga-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Site', 'megagovern' ); ?></th>
                            <th><?php esc_html_e( 'URL', 'megagovern' ); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $megagovern_sites as $megagovern_id => $megagovern_s ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $megagovern_s['name'] ); ?></strong></td>
                                <td style="font-size:11px;color:var(--mga-text-muted);"><?php echo esc_html( $megagovern_s['url'] ); ?></td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="<?php echo esc_url( $megagovern_s['url'] . '/wp-admin/admin.php?page=megagovern' ); ?>"
                                       target="_blank" rel="noopener" class="button button-small"><?php esc_html_e( 'Open', 'megagovern' ); ?></a>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="agency_action" value="remove_site">
                                        <input type="hidden" name="site_id" value="<?php echo esc_attr( $megagovern_id ); ?>">
                                        <?php wp_nonce_field( 'agency_remove_site' ); ?>
                                        <button type="submit" class="button button-small"
                                                onclick="return confirm('<?php esc_attr_e( 'Remove this site?', 'megagovern' ); ?>');">
                                            <?php echo wp_kses_post( megagovern_agency_icon( 'trash', '14' ) ); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="mga-empty-text"><?php esc_html_e( 'No sites added.', 'megagovern' ); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <!-- ═══════════════════════════════════════ -->
    <!-- TAB: WHITE LABEL                        -->
    <!-- ═══════════════════════════════════════ -->
    <?php if ( 'whitelabel' === $megagovern_tab ) : ?>
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title"><?php esc_html_e( 'White Label Branding', 'megagovern' ); ?></h3>
            </div>
            <form method="post">
                <?php wp_nonce_field( 'agency_wl_save' ); ?>
                <input type="hidden" name="agency_action" value="save_wl">
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e( 'Agency Name', 'megagovern' ); ?></label></th>
                        <td><input type="text" name="agency_name" value="<?php echo esc_attr( $megagovern_wl['agency_name'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Logo URL', 'megagovern' ); ?></label></th>
                        <td><input type="url" name="agency_logo" value="<?php echo esc_attr( $megagovern_wl['agency_logo'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Brand Color', 'megagovern' ); ?></label></th>
                        <td><input type="color" name="agency_color" value="<?php echo esc_attr( $megagovern_wl['agency_color'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Footer Text', 'megagovern' ); ?></label></th>
                        <td><input type="text" name="agency_footer" value="<?php echo esc_attr( $megagovern_wl['agency_footer'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Powered by [Agency]', 'megagovern' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Hide Branding', 'megagovern' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="hide_branding" value="1" <?php checked( $megagovern_wl['hide_branding'] ); ?>>
                                <?php esc_html_e( 'Remove MegaGovern from public pages', 'megagovern' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'megagovern' ); ?></button>
                </p>
            </form>
        </div>
    <?php endif; ?>
    <!-- ═══════════════════════════════════════ -->
    <!-- TAB: BULK REPORTS                       -->
    <!-- ═══════════════════════════════════════ -->
    <?php if ( 'reports' === $megagovern_tab ) : ?>
        <div class="mga-row mga-row-2col">
            <div class="mga-card">
                <div class="mga-card-header">
                    <h3 class="mga-card-title"><?php esc_html_e( 'Generate All Reports', 'megagovern' ); ?></h3>
                </div>
                <p class="mga-card-text">
                    <?php
                    /* translators: %d: number of sites */
                    printf( esc_html__( 'Generate compliance reports for all %d sites.', 'megagovern' ), (int) $megagovern_count );
                    ?>
                </p>
                <button class="button button-primary" style="width:100%;">
                    <?php esc_html_e( 'Generate All Reports', 'megagovern' ); ?>
                </button>
            </div>
            <div class="mga-card">
                <div class="mga-card-header">
                    <h3 class="mga-card-title"><?php esc_html_e( 'Schedule Reports', 'megagovern' ); ?></h3>
                </div>
                <p class="mga-card-text"><?php esc_html_e( 'Auto-generate monthly compliance reports.', 'megagovern' ); ?></p>
                <form method="post" action="options.php">
                    <?php settings_fields( 'megagovern_agency_settings' ); ?>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <input type="checkbox" name="megagovern_agency_scheduled_reports" value="1" <?php checked( get_option( 'megagovern_agency_scheduled_reports', false ) ); ?>>
                        <?php esc_html_e( 'Enable scheduled monthly reports', 'megagovern' ); ?>
                    </label>
                    <button type="submit" class="button button-primary" style="width:100%;">
                        <?php esc_html_e( 'Save Schedule', 'megagovern' ); ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <!-- ═══════════════════════════════════════ -->
    <!-- TAB: TEAM                               -->
    <!-- ═══════════════════════════════════════ -->
    <?php if ( 'team' === $megagovern_tab ) : ?>
        <div class="mga-row mga-row-2col">
            <div class="mga-card">
                <div class="mga-card-header">
                    <h3 class="mga-card-title"><?php esc_html_e( 'Add Team Member', 'megagovern' ); ?></h3>
                </div>
                <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <?php wp_nonce_field( 'agency_team' ); ?>
                    <input type="hidden" name="agency_action" value="add_member">
                    <select name="user_id" style="flex:1;min-width:150px;">
                        <?php foreach ( $megagovern_users as $megagovern_u ) :
                            if ( isset( $megagovern_team[ $megagovern_u->ID ] ) ) {
                                continue;
                            }
                        ?>
                            <option value="<?php echo (int) $megagovern_u->ID; ?>"><?php echo esc_html( $megagovern_u->display_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="role" style="min-width:120px;">
                        <option value="admin"><?php esc_html_e( 'Admin', 'megagovern' ); ?></option>
                        <option value="manager"><?php esc_html_e( 'Manager', 'megagovern' ); ?></option>
                        <option value="editor"><?php esc_html_e( 'Editor', 'megagovern' ); ?></option>
                        <option value="contributor"><?php esc_html_e( 'Contributor', 'megagovern' ); ?></option>
                    </select>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Add', 'megagovern' ); ?></button>
                </form>
            </div>
            <div class="mga-card">
                <div class="mga-card-header">
                    <h3 class="mga-card-title"><?php esc_html_e( 'Team Members', 'megagovern' ); ?> (<?php echo count( $megagovern_team ); ?>)</h3>
                </div>
                <?php if ( ! empty( $megagovern_team ) ) : ?>
                    <table class="mga-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'User', 'megagovern' ); ?></th>
                                <th><?php esc_html_e( 'Role', 'megagovern' ); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $megagovern_team as $megagovern_uid => $megagovern_m ) :
                                $megagovern_u = get_userdata( $megagovern_uid );
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $megagovern_u ? $megagovern_u->display_name : 'User #' . $megagovern_uid ); ?></strong></td>
                                    <td><?php echo esc_html( ucfirst( $megagovern_m['role'] ) ); ?></td>
                                    <td style="text-align:right;">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="agency_action" value="remove_member">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $megagovern_uid; ?>">
                                            <?php wp_nonce_field( 'agency_team' ); ?>
                                            <button type="submit" class="button button-small"
                                                    onclick="return confirm('<?php esc_attr_e( 'Remove this member?', 'megagovern' ); ?>');">
                                                <?php echo wp_kses_post( megagovern_agency_icon( 'trash', '14' ) ); ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p class="mga-empty-text"><?php esc_html_e( 'No team members added.', 'megagovern' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <!-- ═══════════════════════════════════════ -->
    <!-- TAB: SETTINGS                           -->
    <!-- ═══════════════════════════════════════ -->
    <?php if ( 'settings' === $megagovern_tab ) : ?>
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title"><?php esc_html_e( 'Plan Details', 'megagovern' ); ?></h3>
            </div>
            <table class="mga-table">
                <tbody>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Plan', 'megagovern' ); ?></td>
                        <td style="text-align:right;"><?php echo esc_html( License::get_plan_name() ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Site Limit', 'megagovern' ); ?></td>
                        <td style="text-align:right;"><?php echo (int) $megagovern_limit; ?> <?php esc_html_e( 'sites', 'megagovern' ); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'White Label', 'megagovern' ); ?></td>
                        <td style="text-align:right;"><span class="mga-pill mga-pill--ok"><?php esc_html_e( 'Included', 'megagovern' ); ?></span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Bulk Reports', 'megagovern' ); ?></td>
                        <td style="text-align:right;"><span class="mga-pill mga-pill--ok"><?php esc_html_e( 'Included', 'megagovern' ); ?></span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Team Access', 'megagovern' ); ?></td>
                        <td style="text-align:right;"><span class="mga-pill mga-pill--ok"><?php esc_html_e( 'Included', 'megagovern' ); ?></span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;"><?php esc_html_e( 'Site ID', 'megagovern' ); ?></td>
                        <td style="text-align:right;"><code><?php echo esc_html( $megagovern_site_id ); ?></code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>