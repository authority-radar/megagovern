<?php
/**
 * MegaGovern Reports — Tab: Reports
 *
 * Compliance reports, evidence trail, attestation, and ZIP bundle downloads.
 *
 * @package MegaGovern
 * @since   1.0.4
 * @var array $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use MegaGovern\Helpers;
// ─── Variable Declaration ──────────────────────
$megagovern_evidence_logs   = isset( $args['evidence_logs'] ) ? (array) $args['evidence_logs'] : array();
$megagovern_registry_total  = isset( $args['registry_total'] ) ? (int) $args['registry_total'] : 0;
$megagovern_registry_last   = isset( $args['registry_last'] ) ? $args['registry_last'] : __( 'Active', 'megagovern' );
$megagovern_has_pro         = isset( $args['has_pro'] ) ? (bool) $args['has_pro'] : false;
$megagovern_is_free         = isset( $args['is_free'] ) ? (bool) $args['is_free'] : true;
$megagovern_is_agency       = isset( $args['is_agency'] ) ? (bool) $args['is_agency'] : false;
$megagovern_plan_name       = isset( $args['plan_name'] ) ? $args['plan_name'] : __( 'Free', 'megagovern' );
$megagovern_schedule        = isset( $args['schedule'] ) ? $args['schedule'] : 'none';
$megagovern_email           = isset( $args['email'] ) ? $args['email'] : '';
$megagovern_upgrade_url     = isset( $args['upgrade_url'] ) ? $args['upgrade_url'] : '';
$megagovern_site_id         = isset( $args['site_id'] ) ? $args['site_id'] : get_option( 'megagovern_site_id', '' );
$megagovern_hash            = isset( $args['hash'] ) ? $args['hash'] : '';
$megagovern_report_nonce    = isset( $args['report_nonce'] ) ? $args['report_nonce'] : wp_create_nonce( 'megagovern_generate_report' );
$megagovern_evidence_nonce  = isset( $args['evidence_nonce'] ) ? $args['evidence_nonce'] : wp_create_nonce( 'megagovern_evidence_download' );
// ─── URLs ──────────────────────────────────────
$megagovern_settings_url = esc_url(
	add_query_arg(
		array( 'page' => 'megagovern-settings' ),
		admin_url( 'admin.php' )
	)
);
$megagovern_pdf_url = esc_url(
	add_query_arg(
		array(
			'action'   => 'megagovern_generate_report',
			'format'   => 'pdf',
			'_wpnonce' => $megagovern_report_nonce,
		),
		admin_url( 'admin-post.php' )
	)
);
$megagovern_csv_url = esc_url(
	add_query_arg(
		array(
			'action'   => 'megagovern_generate_report',
			'format'   => 'csv',
			'_wpnonce' => $megagovern_report_nonce,
		),
		admin_url( 'admin-post.php' )
	)
);
$megagovern_refresh_url = esc_url(
	add_query_arg( 'refresh', '1', admin_url( 'admin.php?page=megagovern-reports' ) )
);
// ─── Plan badges ──────────────────────────────
$megagovern_plan_class = $megagovern_is_agency ? 'agency' : ( $megagovern_has_pro ? 'pro' : 'free' );
$megagovern_retention_days = $megagovern_has_pro ? 365 : 90;
// ─── Icon Helper ──────────────────────────────
if ( ! function_exists( 'megagovern_reports_icon' ) ) {
	/**
	 * Render a Lucide inline SVG icon.
	 *
	 * @param string $name Icon identifier.
	 * @param string $size Width/height in pixels.
	 * @return string SVG markup.
	 */
	function megagovern_reports_icon( string $name, string $size = '16' ): string {
		$megagovern_icons = array(
			'file-text'    => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
			'database'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>',
			'clock'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
			'shield-check' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
			'lock'         => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
			'download'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
			'calendar'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
			'check-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>',
			'settings'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
			'book'         => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
			'refresh-cw'   => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
			'alert-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
		);
		return isset( $megagovern_icons[ $name ] ) ? $megagovern_icons[ $name ] : '';
	}
}
?>
<div class="wrap megagovern-reports">
	<hr class="wp-header-end">
	<div class="mga-gov-cols">
		<!-- LEFT COLUMN -->
		<div class="mga-gov-col">
			<!-- 1. Compliance Reports -->
			<div class="mga-card">
				<div class="mga-card-header">
					<h3 class="mga-card-title">
						<?php echo wp_kses_post( megagovern_reports_icon( 'file-text', '16' ) ); ?>
						<?php esc_html_e( 'Compliance Reports', 'megagovern' ); ?>
					</h3>
				</div>
				<div class="mga-card-body">
					<p class="mga-card-text"><?php esc_html_e( 'Generate compliance documentation for your records.', 'megagovern' ); ?></p>
				</div>
				<div style="display: flex; gap: 8px; padding: 0 16px 12px;">
					<?php if ( $megagovern_has_pro ) : ?>
						<a href="<?php echo esc_url( $megagovern_pdf_url ); ?>" class="button button-primary button-small" style="flex: 1; text-align: center;">
							<?php echo wp_kses_post( megagovern_reports_icon( 'file-text', '14' ) ); ?>
							<?php esc_html_e( 'PDF Report', 'megagovern' ); ?>
						</a>
					<?php else : ?>
						<button type="button" class="button button-small" disabled style="flex: 1; text-align: center; opacity: 0.6;">
							<?php echo wp_kses_post( megagovern_reports_icon( 'lock', '14' ) ); ?>
							<?php esc_html_e( 'PDF Report (Pro)', 'megagovern' ); ?>
						</button>
					<?php endif; ?>
					<a href="<?php echo esc_url( $megagovern_csv_url ); ?>" class="button button-secondary button-small" style="flex: 1; text-align: center;">
						<?php echo wp_kses_post( megagovern_reports_icon( 'download', '14' ) ); ?>
						<?php esc_html_e( 'Export CSV', 'megagovern' ); ?>
					</a>
				</div>
				<?php if ( $megagovern_has_pro ) : ?>
					<div style="padding: 0 16px 16px;">
						<label class="mga-card-text" style="display: flex; align-items: flex-start; gap: 8px; cursor: default;">
							<?php echo wp_kses_post( megagovern_reports_icon( 'calendar', '14' ) ); ?>
							<div>
								<strong style="display: block; font-size: 12px;"><?php esc_html_e( 'Scheduled Reports', 'megagovern' ); ?></strong>
								<small style="color: var(--mga-text-muted);">
									<?php
									if ( 'none' !== $megagovern_schedule ) {
										printf(
											/* translators: %1$s: schedule frequency, %2$s: email address */
											esc_html__( '%1$s delivery to %2$s', 'megagovern' ),
											esc_html( ucfirst( $megagovern_schedule ) ),
											esc_html( $megagovern_email )
										);
									} else {
										esc_html_e( 'Not scheduled', 'megagovern' );
									}
									?>
								</small>
							</div>
						</label>
						<a href="<?php echo esc_url( $megagovern_settings_url ); ?>" style="font-size: 11px; color: var(--mga-accent); display: inline-flex; align-items: center; gap: 4px; margin-top: 8px;">
							<?php echo wp_kses_post( megagovern_reports_icon( 'settings', '12' ) ); ?>
							<?php esc_html_e( 'Configure scheduling', 'megagovern' ); ?> &rarr;
						</a>
					</div>
				<?php endif; ?>
			</div>
			<!-- 2. Transparency Registry -->
			<div class="mga-card">
				<div class="mga-card-header">
					<h3 class="mga-card-title">
						<?php echo wp_kses_post( megagovern_reports_icon( 'database', '16' ) ); ?>
						<?php esc_html_e( 'Transparency Registry', 'megagovern' ); ?>
					</h3>
					<span class="mga-pill mga-pill--ok"><?php esc_html_e( 'Local', 'megagovern' ); ?></span>
				</div>
				<table class="mga-table" style="margin: 0;">
					<tbody>
						<tr>
							<td style="font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Total Indexed', 'megagovern' ); ?></td>
							<td style="text-align: right; font-size: 11px; font-weight: 700;"><?php echo esc_html( number_format_i18n( $megagovern_registry_total ) ); ?></td>
						</tr>
						<tr>
							<td style="font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Site ID', 'megagovern' ); ?></td>
							<td style="text-align: right; font-size: 11px;"><code style="font-size: 10px;"><?php echo esc_html( $megagovern_site_id ); ?></code></td>
						</tr>
						<tr>
							<td style="font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Last Updated', 'megagovern' ); ?></td>
							<td style="text-align: right; font-size: 11px;"><?php echo esc_html( $megagovern_registry_last ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<!-- RIGHT COLUMN -->
		<div class="mga-gov-col">
			<!-- 3. Evidence Trail -->
			<div class="mga-card">
				<div class="mga-card-header">
					<h3 class="mga-card-title">
						<?php echo wp_kses_post( megagovern_reports_icon( 'clock', '16' ) ); ?>
						<?php esc_html_e( 'Evidence Trail', 'megagovern' ); ?>
					</h3>
				</div>
				<div class="mga-card-body">
					<?php if ( ! empty( $megagovern_evidence_logs ) ) : ?>
						<div class="mga-timeline" style="max-height: 280px; overflow-y: auto;">
							<?php
							$megagovern_current_date = '';
							foreach ( $megagovern_evidence_logs as $megagovern_log ) :
								$megagovern_log_date = date_i18n( 'F j, Y', strtotime( $megagovern_log['logged_at'] ) );
								if ( $megagovern_log_date !== $megagovern_current_date ) :
									$megagovern_current_date = $megagovern_log_date;
							?>
									<div style="font-size: 10px; text-transform: uppercase; color: var(--mga-text-muted); font-weight: 600; padding: 8px 0 4px; display: flex; align-items: center; gap: 6px;">
										<?php echo wp_kses_post( megagovern_reports_icon( 'calendar', '12' ) ); ?>
										<?php echo esc_html( $megagovern_log_date ); ?>
									</div>
								<?php endif; ?>
								<div class="mga-timeline-item">
									<?php
									$megagovern_action_icon = 'file-text';
									if ( class_exists( '\MegaGovern\Helpers' ) && method_exists( '\MegaGovern\Helpers', 'action_icon_key' ) ) {
										$megagovern_action_icon = Helpers::action_icon_key( $megagovern_log['action'] );
									}
									echo wp_kses_post( megagovern_reports_icon( $megagovern_action_icon, '14' ) );
									?>
									<span style="font-size: 11px;">
										<?php
										if ( class_exists( '\MegaGovern\Helpers' ) && method_exists( '\MegaGovern\Helpers', 'action_label' ) ) {
											echo esc_html( Helpers::action_label( $megagovern_log['action'] ) );
										} else {
											echo esc_html( ucfirst( $megagovern_log['action'] ?? __( 'Action', 'megagovern' ) ) );
										}
										?>
										<?php if ( ! empty( $megagovern_log['note'] ) ) : ?>
											&mdash; <?php echo esc_html( $megagovern_log['note'] ); ?>
										<?php endif; ?>
									</span>
									<time><?php echo esc_html( date_i18n( 'H:i', strtotime( $megagovern_log['logged_at'] ) ) ); ?></time>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p style="font-size: 11px; color: var(--mga-text-muted); text-align: center; padding: 16px 0;">
							<?php esc_html_e( 'No evidence logs yet. Governance actions will appear here.', 'megagovern' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
			<!-- 4. Attestation & Archiving -->
			<div class="mga-card">
				<div class="mga-card-header">
					<h3 class="mga-card-title">
						<?php echo wp_kses_post( megagovern_reports_icon( 'shield-check', '16' ) ); ?>
						<?php esc_html_e( 'Attestation & Archiving', 'megagovern' ); ?>
					</h3>
				</div>
				<table class="mga-table" style="margin: 0;">
					<tbody>
						<tr>
							<td style="font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Registry Integrity', 'megagovern' ); ?></td>
							<td style="text-align: right; font-size: 11px;">
								<span style="display: inline-flex; align-items: center; gap: 4px; color: #059669;">
									<?php echo wp_kses_post( megagovern_reports_icon( 'check-circle', '14' ) ); ?>
									<?php esc_html_e( 'Active & Secure', 'megagovern' ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<td style="font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Anchored Logs', 'megagovern' ); ?></td>
							<td style="text-align: right; font-size: 11px;"><?php echo esc_html( number_format_i18n( $megagovern_registry_total ) ); ?> <?php esc_html_e( 'Records', 'megagovern' ); ?></td>
						</tr>
						<tr>
							<td style="font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Tamper Protection', 'megagovern' ); ?></td>
							<td style="text-align: right; font-size: 11px;">
								<span style="display: inline-flex; align-items: center; gap: 4px; color: #059669;">
									<?php echo wp_kses_post( megagovern_reports_icon( 'shield-check', '14' ) ); ?>
									<?php esc_html_e( 'SHA-256 Enabled', 'megagovern' ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<td style="font-size: 11px; font-weight: 600;"><?php esc_html_e( 'Evidence Retention', 'megagovern' ); ?></td>
							<td style="text-align: right; font-size: 11px;">
								<?php echo esc_html( (string) $megagovern_retention_days ); ?> <?php esc_html_e( 'Days', 'megagovern' ); ?>
							</td>
						</tr>
					</tbody>
				</table>
				<div style="padding: 12px 16px 16px;">
					<button type="button" class="button button-primary button-small download-evidence-btn"
							data-nonce="<?php echo esc_attr( $megagovern_evidence_nonce ); ?>"
							style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px;">
						<?php echo wp_kses_post( megagovern_reports_icon( 'download', '14' ) ); ?>
						<?php esc_html_e( 'Download Evidence Bundle (.zip)', 'megagovern' ); ?>
					</button>
					<p style="font-size: 10px; color: var(--mga-text-muted); text-align: center; margin: 8px 0 0;">
						<?php esc_html_e( 'Includes AI.txt, classification snapshot, governance timeline, and trust report.', 'megagovern' ); ?>
					</p>
					<p style="font-size: 10px; color: #dba617; text-align: center; margin: 4px 0 0; font-style: italic; display: flex; align-items: flex-start; justify-content: center; gap: 4px;">
						<?php echo wp_kses_post( megagovern_reports_icon( 'alert-circle', '12' ) ); ?>
						<?php esc_html_e( 'This evidence bundle is for transparency and documentation purposes only. It does not constitute legal compliance certification.', 'megagovern' ); ?>
					</p>
				</div>
			</div>
		</div>
	</div>
	<!-- UPGRADE BANNER — Free Plan Only -->
	<?php if ( $megagovern_is_free && ! empty( $megagovern_upgrade_url ) ) : ?>
		<div class="mga-row">
			<div class="mga-card mga-card-upgrade">
				<h3>
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
					<?php esc_html_e( 'Unlock Pro — Full Transparency Suite', 'megagovern' ); ?>
				</h3>
				<ul>
					<li><?php esc_html_e( 'PDF Reports & Structured Evidence Bundles', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Content Snapshots at Time of Declaration', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Media Provenance Capture (EXIF/IPTC/C2PA)', 'megagovern' ); ?></li>
					<li><?php esc_html_e( '365-Day Evidence Retention', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Custom AI Policy Editor', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Regulatory Alerts — All Jurisdictions', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Custom Label Text & Colors', 'megagovern' ); ?></li>
					<li><?php esc_html_e( 'Priority Support', 'megagovern' ); ?></li>
				</ul>
				<p class="mga-upgrade-tagline">
					<?php esc_html_e( 'When someone asks you to prove it, this is what you hand them.', 'megagovern' ); ?>
				</p>
				<a href="<?php echo esc_url( $megagovern_upgrade_url ); ?>" class="mga-btn-upgrade">
					<?php esc_html_e( 'Upgrade Now', 'megagovern' ); ?>
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
				</a>
			</div>
		</div>
	<?php endif; ?>
</div>
<script>
( function() {
	var btn = document.querySelector( '.download-evidence-btn' );
	if ( ! btn ) {
		return;
	}
	btn.addEventListener( 'click', function() {
		var nonce = this.dataset.nonce;
		if ( ! nonce ) {
			return;
		}
		var form = document.createElement( 'form' );
		form.method = 'POST';
		form.action = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		form.style.display = 'none';
		var actionInput = document.createElement( 'input' );
		actionInput.type  = 'hidden';
		actionInput.name  = 'action';
		actionInput.value = 'megagovern_download_evidence_bundle';
		form.appendChild( actionInput );
		var nonceInput = document.createElement( 'input' );
		nonceInput.type  = 'hidden';
		nonceInput.name  = 'nonce';
		nonceInput.value = nonce;
		form.appendChild( nonceInput );
		document.body.appendChild( form );
		form.submit();
		document.body.removeChild( form );
	} );
} )();
</script>