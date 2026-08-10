<?php
/**
 * Public Verification Page
 *
 * @package MegaGovern
 * @since   1.0.4
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

use MegaGovern\Registry;

// ─── Data ───
$megagovern_stats        = Registry::get_stats();
$megagovern_total        = (int) ( $megagovern_stats['total'] ?? 0 );
$megagovern_human        = (int) ( $megagovern_stats['human'] ?? 0 );
$megagovern_ai_assisted  = (int) ( $megagovern_stats['ai_assisted'] ?? 0 );
$megagovern_ai_generated = (int) ( $megagovern_stats['ai_generated'] ?? 0 );
$megagovern_human_pct    = $megagovern_total > 0 ? round( ( $megagovern_human / $megagovern_total ) * 100 ) : 0;
$megagovern_assisted_pct = $megagovern_total > 0 ? round( ( $megagovern_ai_assisted / $megagovern_total ) * 100 ) : 0;
$megagovern_generated_pct = $megagovern_total > 0 ? round( ( $megagovern_ai_generated / $megagovern_total ) * 100 ) : 0;
$megagovern_last_updated = ! empty( $megagovern_stats['last_updated'] ) ? $megagovern_stats['last_updated'] : __( 'Not available', 'megagovern' );

// ─── Site identity ───
$megagovern_site_id   = get_option( 'megagovern_site_id', '' );
if ( empty( $megagovern_site_id ) ) {
	$megagovern_site_id = 'mg_' . substr( md5( get_home_url() . uniqid( 'mg_', true ) ), 0, 16 );
	update_option( 'megagovern_site_id', $megagovern_site_id );
}
$megagovern_hash       = substr( hash( 'sha256', $megagovern_site_id . ( $megagovern_stats['last_updated'] ?? current_time( 'mysql' ) ) ), 0, 16 );
$megagovern_site_name  = get_bloginfo( 'name' );
$megagovern_site_url   = home_url();
$megagovern_aitxt_url  = home_url( '/ai.txt' );

// ─── Define allowed SVG HTML for escaping ───
function megagovern_get_allowed_svg_html(): array {
	return array(
		'svg' => array(
			'xmlns' => true,
			'width' => true,
			'height' => true,
			'viewBox' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
		),
		'path' => array(
			'd' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
		),
		'polyline' => array(
			'points' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
		),
		'ellipse' => array(
			'cx' => true,
			'cy' => true,
			'rx' => true,
			'ry' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
		),
		'rect' => array(
			'x' => true,
			'y' => true,
			'width' => true,
			'height' => true,
			'rx' => true,
			'ry' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
		),
		'line' => array(
			'x1' => true,
			'y1' => true,
			'x2' => true,
			'y2' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
		),
		'circle' => array(
			'cx' => true,
			'cy' => true,
			'r' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
			'opacity' => true,
		),
	);
}

// ─── Lucide icon helper with proper escaping ───
function megagovern_verify_icon( string $name, string $size = '18' ): string {
	$icons = [
		'shield-check' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
		'database'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>',
		'file-text'    => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
		'calendar'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
		'users'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
		'edit'         => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
		'cpu'          => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>',
		'external-link' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
		'download'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
	];

	return $icons[ $name ] ?? '';
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, follow">
	<title><?php echo esc_html( $megagovern_site_name ); ?> — <?php esc_html_e( 'AI Transparency Verification', 'megagovern' ); ?></title>
	<?php wp_head(); ?>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			background: #f0f0f1; color: #1d2327; line-height: 1.6; font-size: 14px;
		}
		.mgv-container {
			max-width: 720px; margin: 30px auto; background: #fff;
			border: 1px solid #c3c4c7; border-radius: 6px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;
		}
		.mgv-header {
			text-align: center; padding: 32px 32px 20px;
			background: linear-gradient(180deg, #f0f6fc 0%, #fff 100%);
			border-bottom: 1px solid #e5e5e5;
		}
		.mgv-logo { margin-bottom: 16px; }
		.mgv-logo svg { display: inline-block; }
		.mgv-header h1 { font-size: 22px; color: #1d2327; margin-bottom: 4px; }
		.mgv-site { font-size: 14px; color: #50575e; margin-bottom: 12px; word-break: break-all; }
		.mgv-badge {
			display: inline-flex; align-items: center; gap: 8px;
			padding: 8px 20px; background: #e8f5e9; border: 1px solid #00a32a;
			border-radius: 24px; font-size: 13px; font-weight: 600; color: #00a32a;
		}
		.mgv-badge-dot { width: 8px; height: 8px; border-radius: 50%; background: #00a32a; flex-shrink: 0; }
		.mgv-status {
			display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #e5e5e5;
			border-bottom: 1px solid #e5e5e5;
		}
		.mgv-status-card { background: #fff; padding: 16px 12px; text-align: center; }
		.mgv-status-card svg { color: #2271b1; margin-bottom: 4px; }
		.mgv-status-label { font-size: 9px; color: #646970; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 4px; }
		.mgv-status-value { font-size: 11px; font-weight: 600; color: #00a32a; }
		.mgv-section { padding: 20px 32px; border-bottom: 1px solid #f0f0f1; }
		.mgv-section h2 { font-size: 15px; color: #1d2327; margin-bottom: 8px; }
		.mgv-section p { font-size: 12px; color: #50575e; margin-bottom: 8px; line-height: 1.5; }
		.mgv-table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 12px; }
		.mgv-table td { padding: 8px 12px; border-bottom: 1px solid #f0f0f1; }
		.mgv-table td:last-child { text-align: right; }
		.mgv-table .mgv-total { font-weight: 700; font-size: 16px; color: #2271b1; }
		.mgv-resources { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px; }
		.mgv-resource-card {
			display: flex; flex-direction: column; gap: 3px; padding: 12px;
			background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px;
			text-decoration: none; color: #1d2327; font-size: 11px; transition: box-shadow 0.2s;
		}
		.mgv-resource-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
		.mgv-resource-card svg { color: #2271b1; flex-shrink: 0; }
		.mgv-resource-card strong { font-size: 12px; }
		.mgv-hash {
			padding: 10px 14px; background: #f0f0f1; border-radius: 4px;
			font-size: 10px; color: #646970; word-break: break-all; margin-top: 10px;
		}
		.mgv-hash code { color: #1d2327; font-size: 10px; }
		.mgv-footer {
			text-align: center; padding: 16px 32px; background: #f0f0f1;
			border-top: 1px solid #c3c4c7; font-size: 10px; color: #a7aaad;
		}
		.mgv-icon-inline { display: inline-flex; vertical-align: middle; margin-right: 4px; }
		@media (max-width: 768px) {
			.mgv-container { margin: 0; border-radius: 0; }
			.mgv-status { grid-template-columns: 1fr; }
			.mgv-section { padding: 16px; }
			.mgv-header { padding: 24px 16px; }
			.mgv-resources { grid-template-columns: 1fr; }
		}
	</style>
</head>
<body <?php body_class(); ?>>

<div class="mgv-container">

	<!-- Header -->
	<div class="mgv-header">
		<div class="mgv-logo">
			<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M24 4L6 12V22C6 31.5 13.5 40.2 24 44C34.5 40.2 42 31.5 42 22V12L24 4Z" fill="#2271b1" stroke="#1d2327" stroke-width="1.5" stroke-linejoin="round"/>
				<path d="M16 24L21 29L32 18" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
				<circle cx="29" cy="30" r="1.5" fill="#ffffff" opacity="0.7"/>
				<circle cx="33" cy="28" r="1" fill="#ffffff" opacity="0.5"/>
				<circle cx="35" cy="32" r="1.2" fill="#ffffff" opacity="0.6"/>
			</svg>
		</div>
		<h1><?php echo esc_html( $megagovern_site_name ); ?></h1>
		<p class="mgv-site"><?php echo esc_url( $megagovern_site_url ); ?></p>
		<div class="mgv-badge">
			<span class="mgv-badge-dot"></span>
			<?php esc_html_e( 'Verified Transparent Site', 'megagovern' ); ?>
		</div>
	</div>

	<!-- Status Row -->
	<div class="mgv-status">
		<div class="mgv-status-card">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wp_kses( megagovern_verify_icon( 'database', '18' ), megagovern_get_allowed_svg_html() );
			?>
			<div class="mgv-status-label"><?php esc_html_e( 'Registry', 'megagovern' ); ?></div>
			<div class="mgv-status-value"><?php esc_html_e( 'Active', 'megagovern' ); ?></div>
		</div>
		<div class="mgv-status-card">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wp_kses( megagovern_verify_icon( 'file-text', '18' ), megagovern_get_allowed_svg_html() );
			?>
			<div class="mgv-status-label"><?php esc_html_e( 'AI.txt', 'megagovern' ); ?></div>
			<div class="mgv-status-value"><?php esc_html_e( 'Published', 'megagovern' ); ?></div>
		</div>
		<div class="mgv-status-card">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wp_kses( megagovern_verify_icon( 'calendar', '18' ), megagovern_get_allowed_svg_html() );
			?>
			<div class="mgv-status-label"><?php esc_html_e( 'Last Review', 'megagovern' ); ?></div>
			<div class="mgv-status-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $megagovern_last_updated ) ) ); ?></div>
		</div>
	</div>

	<!-- About -->
	<div class="mgv-section">
		<h2><?php esc_html_e( 'About This Page', 'megagovern' ); ?></h2>
		<p><?php esc_html_e( 'This website voluntarily maintains an AI Content Transparency Registry and publishes AI disclosure information. All content sources are declared and recorded in a verifiable transparency registry, providing proof of responsible AI governance.', 'megagovern' ); ?></p>
	</div>

	<!-- Statistics -->
	<div class="mgv-section">
		<h2><?php esc_html_e( 'Content Declaration Statistics', 'megagovern' ); ?></h2>
		<table class="mgv-table">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Total Declared Articles', 'megagovern' ); ?></strong></td>
					<td class="mgv-total"><?php echo esc_html( (string) $megagovern_total ); ?></td>
				</tr>
				<tr>
					<td>
						<span class="mgv-icon-inline" style="color:#2271b1;">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo wp_kses( megagovern_verify_icon( 'users', '14' ), megagovern_get_allowed_svg_html() );
							?>
						</span>
						<?php esc_html_e( 'Human Written', 'megagovern' ); ?>
					</td>
					<td><?php echo esc_html( (string) $megagovern_human ); ?> (<?php echo esc_html( (string) $megagovern_human_pct ); ?>%)</td>
				</tr>
				<tr>
					<td>
						<span class="mgv-icon-inline" style="color:#dba617;">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo wp_kses( megagovern_verify_icon( 'edit', '14' ), megagovern_get_allowed_svg_html() );
							?>
						</span>
						<?php esc_html_e( 'AI Assisted', 'megagovern' ); ?>
					</td>
					<td><?php echo esc_html( (string) $megagovern_ai_assisted ); ?> (<?php echo esc_html( (string) $megagovern_assisted_pct ); ?>%)</td>
				</tr>
				<tr>
					<td>
						<span class="mgv-icon-inline" style="color:#d63638;">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo wp_kses( megagovern_verify_icon( 'cpu', '14' ), megagovern_get_allowed_svg_html() );
							?>
						</span>
						<?php esc_html_e( 'AI Generated', 'megagovern' ); ?>
					</td>
					<td><?php echo esc_html( (string) $megagovern_ai_generated ); ?> (<?php echo esc_html( (string) $megagovern_generated_pct ); ?>%)</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Resources -->
	<div class="mgv-section">
		<h2><?php esc_html_e( 'Resources', 'megagovern' ); ?></h2>
		<div class="mgv-resources">
			<a href="<?php echo esc_url( $megagovern_aitxt_url ); ?>" target="_blank" rel="noopener" class="mgv-resource-card">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo wp_kses( megagovern_verify_icon( 'external-link', '16' ), megagovern_get_allowed_svg_html() );
				?>
				<strong><?php esc_html_e( 'View AI.txt', 'megagovern' ); ?></strong>
				<span><?php echo esc_url( $megagovern_aitxt_url ); ?></span>
			</a>
			<a href="<?php echo esc_url( $megagovern_aitxt_url ); ?>" download class="mgv-resource-card">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo wp_kses( megagovern_verify_icon( 'download', '16' ), megagovern_get_allowed_svg_html() );
				?>
				<strong><?php esc_html_e( 'Download AI.txt', 'megagovern' ); ?></strong>
				<span><?php esc_html_e( 'Machine-readable file', 'megagovern' ); ?></span>
			</a>
		</div>
	</div>

	<!-- Verification -->
	<div class="mgv-section">
		<h2><?php esc_html_e( 'Verification', 'megagovern' ); ?></h2>
		<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:11px;">
			<div>
				<div style="color:#646970; font-size:9px; text-transform:uppercase;"><?php esc_html_e( 'Registry ID', 'megagovern' ); ?></div>
				<code style="font-size:10px; word-break:break-all;"><?php echo esc_html( $megagovern_site_id ); ?></code>
			</div>
			<div>
				<div style="color:#646970; font-size:9px; text-transform:uppercase;"><?php esc_html_e( 'Generated', 'megagovern' ); ?></div>
				<span style="font-size:10px;"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $megagovern_last_updated ) ) ); ?></span>
			</div>
		</div>
		<div class="mgv-hash">
			<strong><?php esc_html_e( 'Verification Hash:', 'megagovern' ); ?></strong>
			<code><?php echo esc_html( $megagovern_hash ); ?></code>
		</div>
	</div>

	<!-- Footer -->
	<div class="mgv-footer">
		<?php esc_html_e( 'Generated automatically from the website\'s Transparency Registry by megagovern.com.', 'megagovern' ); ?>
	</div>

</div>

<?php wp_footer(); ?>
</body>
</html>