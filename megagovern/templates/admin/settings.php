<?php
/**
 * Platform Settings — V1.0.4
 *
 * License + Jurisdiction + Content Types + Labels + Automation + Reports + AI Notice
 *
 * CHANGELOG v1.0.4:
 * - FIX: Live Preview text changed to "AI Transparency Notice"
 * - FIX: Undefined variable $notice_blue_label → $notice_white_label
 *
 * @package MegaGovern
 * @since   1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MegaGovern\License;

// ─── SECURITY: Capability Check ───
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'megagovern' ) );
}

// ─── LICENSE ───
$megagovern_is_pro    = false;
$megagovern_is_agency = false;
$megagovern_is_free   = true;
$megagovern_plan      = __( 'Free', 'megagovern' );

if ( class_exists( '\MegaGovern\License' ) ) {
	$megagovern_is_pro    = License::is_pro();
	$megagovern_is_agency = License::is_agency();
	$megagovern_is_free   = License::is_free();
	$megagovern_plan      = License::get_plan_name();
}

// ─── FREEMIUS STATE ───
$megagovern_fs               = function_exists( 'mga_fs' ) ? mga_fs() : null;
$megagovern_fs_is_registered = ( $megagovern_fs && is_object( $megagovern_fs ) && method_exists( $megagovern_fs, 'is_registered' ) ) ? $megagovern_fs->is_registered() : false;
$megagovern_fs_account_url   = ( $megagovern_fs && is_object( $megagovern_fs ) && $megagovern_fs_is_registered && method_exists( $megagovern_fs, 'get_account_url' ) ) ? $megagovern_fs->get_account_url() : '';
$megagovern_fs_upgrade_url   = '';

if ( $megagovern_is_free && $megagovern_fs && is_object( $megagovern_fs ) && method_exists( $megagovern_fs, 'get_upgrade_url' ) ) {
	$megagovern_fs_upgrade_url = $megagovern_fs->get_upgrade_url();
}

// ─── SITE ID (Local — no external call) ───
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

// ─── OPTIONS ───
$megagovern_region      = get_option( 'megagovern_region', 'global' );
$megagovern_label_pos   = get_option( 'megagovern_label_position', 'top' );
$megagovern_auto_aitxt  = (bool) get_option( 'megagovern_auto_aitxt', true );
$megagovern_auto_verify = (bool) get_option( 'megagovern_auto_verify', true );
$megagovern_schedule    = get_option( 'megagovern_report_schedule', 'none' );
$megagovern_email       = get_option( 'megagovern_report_email', get_bloginfo( 'admin_email' ) );
$megagovern_saved_types = get_option( 'megagovern_declaration_post_types', array( 'post', 'page' ) );

// ─── JURISDICTIONS ───
$megagovern_jurisdictions = array(
	'eu'     => __( 'European Union', 'megagovern' ),
	'us'     => __( 'United States', 'megagovern' ),
	'global' => __( 'Global', 'megagovern' ),
);

// ─── AI NOTICE SETTINGS ───
$megagovern_notice_enabled      = (bool) get_option( 'megagovern_notice_enabled', true );
$megagovern_notice_position     = get_option( 'megagovern_notice_position', 'top' );
$megagovern_notice_dismiss_days = (int) get_option( 'megagovern_notice_dismiss_days', 1 );
$megagovern_notice_bg_color     = get_option( 'megagovern_notice_bg_color', '#1e293b' );
$megagovern_notice_text_color   = get_option( 'megagovern_notice_text_color', '#f1f5f9' );
$megagovern_notice_link_color   = get_option( 'megagovern_notice_link_color', '#60a5fa' );
$megagovern_notice_show_logo    = (bool) get_option( 'megagovern_notice_show_logo', true );
$megagovern_notice_show_icon    = (bool) get_option( 'megagovern_notice_show_icon', true );
$megagovern_notice_policy_page  = get_option( 'megagovern_notice_policy_page', '' );
$megagovern_notice_custom_text  = get_option( 'megagovern_notice_custom_text', '' );
$megagovern_notice_white_label  = $megagovern_is_agency;
$megagovern_pages               = get_pages();

// ─── TAB ROUTING (4 tabs) ───
$megagovern_stab       = isset( $_GET['stab'] ) ? sanitize_text_field( wp_unslash( $_GET['stab'] ) ) : 'general';
$megagovern_valid_tabs = array( 'general', 'content', 'automation', 'notice' );
if ( ! in_array( $megagovern_stab, $megagovern_valid_tabs, true ) ) {
	$megagovern_stab = 'general';
}

// ─── NONCES ───
$megagovern_nonce_disclosure   = wp_create_nonce( 'megagovern_disclosure_nonce' );
$megagovern_nonce_content      = wp_create_nonce( 'megagovern_content_nonce' );
$megagovern_nonce_automation   = wp_create_nonce( 'megagovern_automation_nonce' );
$megagovern_nonce_jurisdiction = wp_create_nonce( 'megagovern_jurisdiction_nonce' );
$megagovern_nonce_email        = wp_create_nonce( 'megagovern_email_nonce' );
$megagovern_nonce_notice       = wp_create_nonce( 'megagovern_notice_nonce' );

// ─── LUCIDE ICON HELPER ───
if ( ! function_exists( 'megagovern_settings_icon' ) ) {
	/**
	 * Get an inline SVG icon for settings.
	 *
	 * @param string $name Icon identifier.
	 * @param string $size Width/height in pixels.
	 * @return string SVG markup.
	 */
	function megagovern_settings_icon( string $name, string $size = '16' ): string {
		$megagovern_icons = array(
			'settings'    => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
			'network'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>',
			'tag'         => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29a1 1 0 0 0 1.41 0l7-7a1 1 0 0 0 0-1.41L12 2z"/><polyline points="7 7 7.01 7"/></svg>',
			'post'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
			'refresh'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
			'globe'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
			'mail'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22 7 12 13 2 7"/></svg>',
			'star'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
			'bell'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
			'shield'      => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
			'database'    => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>',
			'file-text'   => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
			'lock'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
			'check'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
			'arrow-right' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
			'info'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
			'user'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
			'eye'         => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
		);
		return isset( $megagovern_icons[ $name ] ) ? $megagovern_icons[ $name ] : '';
	}
}

// Tab URL helper.
if ( ! function_exists( 'megagovern_settings_tab_url' ) ) {
	/**
	 * Get a settings tab URL.
	 *
	 * @param string $stab Tab identifier.
	 * @return string Escaped URL.
	 */
	function megagovern_settings_tab_url( string $stab ): string {
		return esc_url( add_query_arg( array( 'page' => 'megagovern-settings', 'stab' => $stab ), admin_url( 'admin.php' ) ) );
	}
}

/**
 * Reusable Upgrade reminder banner — renders on every tab for Free users.
 */
function megagovern_render_upgrade_banner( bool $is_free, string $upgrade_url ): void {
	if ( ! $is_free ) {
		return;
	}
	?>
	<div class="mga-row">
		<div>
			<div class="mga-card mga-card-upgrade">
				<h3>
					<?php echo wp_kses_post( megagovern_settings_icon( 'star', '20' ) ); ?>
					<?php esc_html_e( 'Unlock Pro', 'megagovern' ); ?>
				</h3>
				<ul>
					<li><?php esc_html_e( 'Custom AI Policy Editor', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'PDF Reports & Evidence Bundles', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Content Snapshots & Media Provenance', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Regulatory Alerts — all jurisdictions', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Custom Label Text', 'megagovern' ); ?></li>
					<li><?php esc_html_e( '365-Day Evidence Retention', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Priority Support', 'megagovern' ); ?></li>
				</ul>
				<?php if ( ! empty( $upgrade_url ) ) : ?>
					<a href="<?php echo esc_url( $upgrade_url ); ?>" class="mga-btn-upgrade">
						<?php esc_html_e( 'Upgrade Now', 'megagovern' ); ?>
						<?php echo wp_kses_post( megagovern_settings_icon( 'arrow-right', '14' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

$megagovern_plan_class = 'mga-plan--' . strtolower( $megagovern_plan );
?>

<div class="wrap megagovern-settings">

	<hr class="wp-header-end">

	<!-- TABS (4) -->
	<nav class="mga-tabs">
		<a href="<?php echo esc_url( megagovern_settings_tab_url( 'general' ) ); ?>" class="mga-tab <?php echo 'general' === $megagovern_stab ? 'mga-tab--active' : ''; ?>">
			<?php echo wp_kses_post( megagovern_settings_icon( 'settings', '16' ) ); ?>
			<?php esc_html_e( 'General', 'megagovern' ); ?>
		</a>
		<a href="<?php echo esc_url( megagovern_settings_tab_url( 'automation' ) ); ?>" class="mga-tab <?php echo 'automation' === $megagovern_stab ? 'mga-tab--active' : ''; ?>">
			<?php echo wp_kses_post( megagovern_settings_icon( 'refresh', '16' ) ); ?>
			<?php esc_html_e( 'Automation & Reports', 'megagovern' ); ?>
		</a>
	</nav>

	<?php
	// ═══════════════════════════════
	// TAB 1: GENERAL
	// ═══════════════════════════════
	if ( 'general' === $megagovern_stab ) :
	?>
		<div class="mga-row mga-row-2col-s">
			<!-- License -->
			<div>
				<div class="mga-card">
					<div class="mga-card-header">
						<h3 class="mga-card-title"><?php echo wp_kses_post( megagovern_settings_icon( 'network', '16' ) ); ?> <?php esc_html_e( 'License', 'megagovern' ); ?></h3>
						<span class="mga-pill" style="background:var(--mga-accent);color:#fff;"><?php echo esc_html( strtoupper( $megagovern_plan ) ); ?></span>
					</div>
					<div class="mga-card-body">
						<p class="mga-site-id">
							<strong><?php esc_html_e( 'Site ID:', 'megagovern' ); ?></strong>
							<code><?php echo esc_html( $megagovern_site_id ); ?></code>
						</p>

						<?php if ( $megagovern_is_free && $megagovern_fs_upgrade_url ) : ?>
							<a href="<?php echo esc_url( $megagovern_fs_upgrade_url ); ?>" class="button button-primary mga-btn-block">
								<?php echo wp_kses_post( megagovern_settings_icon( 'star', '14' ) ); ?>
								<?php esc_html_e( 'Upgrade License', 'megagovern' ); ?>
							</a>
						<?php endif; ?>

						<?php if ( $megagovern_fs_is_registered && $megagovern_fs_account_url ) : ?>
							<a href="<?php echo esc_url( $megagovern_fs_account_url ); ?>" class="button button-secondary mga-btn-block" style="margin-top:8px;">
								<?php esc_html_e( 'Manage Account', 'megagovern' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Jurisdiction -->
			<div>
				<div class="mga-card">
					<div class="mga-card-header">
						<h3 class="mga-card-title"><?php echo wp_kses_post( megagovern_settings_icon( 'globe', '16' ) ); ?> <?php esc_html_e( 'Jurisdiction', 'megagovern' ); ?></h3>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( 'megagovern_settings' ); ?>
						<?php wp_nonce_field( 'megagovern_jurisdiction_nonce', 'megagovern_jurisdiction_nonce' ); ?>
						<div class="mga-card-body">
							<p class="mga-card-text"><?php esc_html_e( 'Select your primary regulatory jurisdiction for alert filtering and compliance defaults.', 'megagovern' ); ?></p>
							<div class="mga-radio-list">
								<?php foreach ( $megagovern_jurisdictions as $megagovern_key => $megagovern_label ) : ?>
									<label class="mga-radio-item">
										<input type="radio" name="megagovern_region" value="<?php echo esc_attr( $megagovern_key ); ?>" <?php checked( $megagovern_region, $megagovern_key ); ?>>
										<span><?php echo esc_html( $megagovern_label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="mga-card-footer">
							<button type="submit" class="button button-primary mga-btn-block">
								<?php esc_html_e( 'Save Region', 'megagovern' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

	<?php
	// ═══════════════════════════════
	// TAB 3: AUTOMATION & REPORTS
	// ═══════════════════════════════
	elseif ( 'automation' === $megagovern_stab ) :
	?>
		<div class="mga-row mga-row-2col-s">
			<!-- Automation -->
			<div>
				<div class="mga-card">
					<div class="mga-card-header">
						<h3 class="mga-card-title"><?php echo wp_kses_post( megagovern_settings_icon( 'refresh', '16' ) ); ?> <?php esc_html_e( 'Automation', 'megagovern' ); ?></h3>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( 'megagovern_settings' ); ?>
						<?php wp_nonce_field( 'megagovern_automation_nonce', 'megagovern_automation_nonce' ); ?>
						<div class="mga-card-body">
							<label class="mga-toggle-row">
								<input type="checkbox" name="megagovern_auto_aitxt" value="1" <?php checked( $megagovern_auto_aitxt ); ?>>
								<div class="mga-toggle-info">
									<strong><?php esc_html_e( 'Auto-regenerate disclosure files', 'megagovern' ); ?></strong>
									<small><?php esc_html_e( 'Rebuild AI.txt, LLMs.txt, and Robots.txt automatically when a declaration changes.', 'megagovern' ); ?></small>
								</div>
							</label>
							<label class="mga-toggle-row">
								<input type="checkbox" name="megagovern_auto_verify" value="1" <?php checked( $megagovern_auto_verify ); ?>>
								<div class="mga-toggle-info">
									<strong><?php esc_html_e( 'Auto-update verification', 'megagovern' ); ?></strong>
									<small><?php esc_html_e( 'Keep the public verification page in sync with declarations.', 'megagovern' ); ?></small>
								</div>
							</label>

							<div class="mga-automation-pro">
								<strong><?php esc_html_e( 'Automatic on Pro/Agency (always on):', 'megagovern' ); ?></strong>
								<div>
									<?php echo wp_kses_post( megagovern_settings_icon( $megagovern_is_pro || $megagovern_is_agency ? 'check' : 'lock', '14' ) ); ?>
									<?php esc_html_e( 'Content Snapshot captured at the moment of each declaration', 'megagovern' ); ?>
								</div>
								<div>
									<?php echo wp_kses_post( megagovern_settings_icon( $megagovern_is_pro || $megagovern_is_agency ? 'check' : 'lock', '14' ) ); ?>
									<?php esc_html_e( 'Media Provenance metadata captured at upload time', 'megagovern' ); ?>
								</div>
							</div>
						</div>
						<div class="mga-card-footer">
							<button type="submit" class="button button-primary mga-btn-block">
								<?php esc_html_e( 'Save Automation', 'megagovern' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- Reports + Alerts Info -->
			<div>
				<!-- Email & Reports -->
				<div class="mga-card">
					<div class="mga-card-header">
						<h3 class="mga-card-title"><?php echo wp_kses_post( megagovern_settings_icon( 'mail', '16' ) ); ?> <?php esc_html_e( 'Email & Reports', 'megagovern' ); ?></h3>
					</div>
					<form method="post" action="options.php">
						<?php settings_fields( 'megagovern_email_settings' ); ?>
						<?php wp_nonce_field( 'megagovern_email_nonce', 'megagovern_email_nonce' ); ?>
						<div class="mga-card-body">
							<div class="mga-settings-field">
								<label class="mga-settings-label"><?php esc_html_e( 'Report Frequency', 'megagovern' ); ?></label>
								<select name="megagovern_report_schedule" class="mga-gov-select">
									<option value="none" <?php selected( $megagovern_schedule, 'none' ); ?>><?php esc_html_e( 'Disabled', 'megagovern' ); ?></option>
									<option value="weekly" <?php selected( $megagovern_schedule, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'megagovern' ); ?></option>
									<option value="monthly" <?php selected( $megagovern_schedule, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'megagovern' ); ?></option>
									<option value="quarterly" <?php selected( $megagovern_schedule, 'quarterly' ); ?>><?php esc_html_e( 'Quarterly', 'megagovern' ); ?></option>
								</select>
							</div>
							<div class="mga-settings-field">
								<label class="mga-settings-label"><?php esc_html_e( 'Report Email', 'megagovern' ); ?></label>
								<input type="email" name="megagovern_report_email" value="<?php echo esc_attr( $megagovern_email ); ?>" class="mga-gov-input">
							</div>
						</div>
						<div class="mga-card-footer">
							<button type="submit" class="button button-primary mga-btn-block">
								<?php esc_html_e( 'Save Email Settings', 'megagovern' ); ?>
							</button>
						</div>
					</form>
				</div>

				<!-- Regulatory Alerts Info -->
				<div class="mga-card">
					<div class="mga-card-header">
						<h3 class="mga-card-title"><?php echo wp_kses_post( megagovern_settings_icon( 'bell', '16' ) ); ?> <?php esc_html_e( 'Regulatory Alerts', 'megagovern' ); ?></h3>
					</div>
					<div class="mga-card-body">
						<p class="mga-card-text"><?php esc_html_e( 'Regulatory alerts are pulled from a public, non-personal data feed. No site content or data is ever transmitted.', 'megagovern' ); ?></p>
						<table class="mga-table">
							<tbody>
								<tr>
									<td><?php esc_html_e( 'Source', 'megagovern' ); ?></td>
									<td><?php esc_html_e( 'Public Regulatory Feed (read-only)', 'megagovern' ); ?></td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Frequency', 'megagovern' ); ?></td>
									<td><?php esc_html_e( 'Daily', 'megagovern' ); ?></td>
								</tr>
								<tr>
									<td><?php esc_html_e( 'Jurisdiction', 'megagovern' ); ?></td>
									<td><?php echo isset( $megagovern_jurisdictions[ $megagovern_region ] ) ? esc_html( $megagovern_jurisdictions[ $megagovern_region ] ) : esc_html( $megagovern_region ); ?></td>
								</tr>
							</tbody>
						</table>
						<p class="mga-settings-desc" style="margin-top:10px;">
							<?php echo wp_kses_post( megagovern_settings_icon( 'info', '12' ) ); ?>
							<?php esc_html_e( 'This is the only external data fetch in MegaGovern. All other features operate entirely locally.', 'megagovern' ); ?>
						</p>
					</div>
				</div>

				<!-- White Label (Agency only) -->
				<?php if ( $megagovern_is_agency ) : ?>
					<div class="mga-card mga-card-success">
						<div class="mga-card-header">
							<h3 class="mga-card-title" style="color:#059669;">
								<?php echo wp_kses_post( megagovern_settings_icon( 'shield', '16' ) ); ?>
								<?php esc_html_e( 'White Label — Active', 'megagovern' ); ?>
							</h3>
						</div>
						<div class="mga-card-body">
							<p class="mga-card-text"><?php esc_html_e( 'MegaGovern branding is removed from public-facing elements.', 'megagovern' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>

	<?php endif; ?>

	<?php megagovern_render_upgrade_banner( $megagovern_is_free, $megagovern_fs_upgrade_url ); ?>

</div>