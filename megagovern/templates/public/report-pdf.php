<?php
/**
 * PDF Report Template — V1.0.4
 *
 * Variables available:
 * $data — Report data array from Reports::generate_data()
 *
 * @package MegaGovern
 * @since   1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$total_declared = isset( $data['stats']['total'] ) ? (int) $data['stats']['total'] : 0;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$human          = isset( $data['stats']['human'] ) ? (int) $data['stats']['human'] : 0;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$assisted       = isset( $data['stats']['ai_assisted'] ) ? (int) $data['stats']['ai_assisted'] : 0;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$generated      = isset( $data['stats']['ai_generated'] ) ? (int) $data['stats']['ai_generated'] : 0;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$deepfake       = isset( $data['stats']['deepfake'] ) ? (int) $data['stats']['deepfake'] : 0;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$undeclared     = 0;

if ( class_exists( '\MegaGovern\Registry' ) ) {
	$undeclared = \MegaGovern\Registry::count_undeclared();
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$all_content     = $total_declared + $undeclared;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$coverage        = $all_content > 0 ? round( ( $total_declared / $all_content ) * 100 ) : 100;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$has_deepfake    = $deepfake > 0;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$is_white_label = false;
if ( class_exists( '\MegaGovern\License' ) && method_exists( '\MegaGovern\License', 'is_agency' ) ) {
	$is_white_label = \MegaGovern\License::is_agency();
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php esc_html_e( 'MegaGovern Compliance Report', 'megagovern' ); ?></title>
	<style>
		body {
			font-family: 'Helvetica', 'Arial', sans-serif;
			font-size: 11px;
			color: #1d2327;
			line-height: 1.6;
			margin: 30px;
		}
		.header {
			text-align: center;
			margin-bottom: 30px;
			border-bottom: 2px solid #2271b1;
			padding-bottom: 20px;
		}
		.header h1 {
			font-size: 22px;
			color: #2271b1;
			margin: 0 0 4px;
		}
		.header .meta {
			color: #646970;
			font-size: 10px;
		}
		h2 {
			font-size: 14px;
			color: #1d2327;
			border-bottom: 1px solid #c3c4c7;
			padding-bottom: 6px;
			margin: 24px 0 12px;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			margin: 12px 0;
			font-size: 10px;
		}
		th {
			background: #f0f0f1;
			padding: 8px 10px;
			text-align: left;
			font-weight: 600;
			border: 1px solid #c3c4c7;
		}
		td {
			padding: 6px 10px;
			border: 1px solid #c3c4c7;
		}
		.stat-row {
			text-align: center;
			margin: 16px 0;
		}
		.stat-box {
			display: inline-block;
			width: 18%;
			text-align: center;
			padding: 10px 4px;
			margin: 0 0.5%;
			background: #f0f6fc;
			border-radius: 4px;
			border: 1px solid #c3c4c7;
			vertical-align: top;
		}
		.stat-box .stat-value {
			font-size: 20px;
			font-weight: 600;
			color: #2271b1;
		}
		.stat-box .stat-label {
			font-size: 8px;
			color: #646970;
			margin-top: 4px;
			line-height: 1.3;
		}
		.stat-box.stat-human .stat-value { color: #2271b1; }
		.stat-box.stat-assisted .stat-value { color: #d63638; }
		.stat-box.stat-generated .stat-value { color: #8552cb; }
		.stat-box.stat-deepfake .stat-value { color: #d63638; }
		.stat-box.stat-undeclared .stat-value { color: #dba617; }
		.footer {
			margin-top: 40px;
			font-size: 9px;
			color: #646970;
			border-top: 1px solid #c3c4c7;
			padding-top: 12px;
		}
		.disclaimer {
			font-style: italic;
			color: #dba617;
			margin-top: 8px;
		}
	</style>
</head>
<body>
	<div class="header">
		<h1><?php echo esc_html( $data['site_name'] ); ?></h1>
		<div class="meta">
			<?php esc_html_e( 'AI Content Governance & Transparency Compliance Report', 'megagovern' ); ?><br>
			<?php
			/* translators: %1$s: generation timestamp, %2$s: start date, %3$s: end date */
			echo esc_html( sprintf( __( 'Generated: %1$s | Date Range: %2$s to %3$s', 'megagovern' ), $data['generated_at'], $data['date_range']['from'], $data['date_range']['to'] ) );
			?>
		</div>
	</div>

	<h2><?php esc_html_e( 'Executive Summary & Coverage', 'megagovern' ); ?></h2>
	<div class="stat-row">
		<div class="stat-box stat-human">
			<div class="stat-value"><?php echo esc_html( (string) $human ); ?></div>
			<div class="stat-label"><?php esc_html_e( 'Human Made', 'megagovern' ); ?></div>
		</div>
		<div class="stat-box stat-assisted">
			<div class="stat-value"><?php echo esc_html( (string) $assisted ); ?></div>
			<div class="stat-label"><?php esc_html_e( 'AI Assisted', 'megagovern' ); ?></div>
		</div>
		<div class="stat-box stat-generated">
			<div class="stat-value"><?php echo esc_html( (string) $generated ); ?></div>
			<div class="stat-label"><?php esc_html_e( 'AI Generated', 'megagovern' ); ?></div>
		</div>
		<div class="stat-box stat-deepfake">
			<div class="stat-value"><?php echo esc_html( (string) $deepfake ); ?></div>
			<div class="stat-label"><?php esc_html_e( 'Deepfake / Synthetic', 'megagovern' ); ?></div>
		</div>
		<div class="stat-box stat-undeclared">
			<div class="stat-value"><?php echo esc_html( (string) $undeclared ); ?></div>
			<div class="stat-label"><?php esc_html_e( 'Undeclared Items', 'megagovern' ); ?></div>
		</div>
	</div>

	<table>
		<tr>
			<th><?php esc_html_e( 'Metric', 'megagovern' ); ?></th>
			<th><?php esc_html_e( 'Value', 'megagovern' ); ?></th>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Total Declared Posts / Pages', 'megagovern' ); ?></td>
			<td><strong><?php echo esc_html( (string) $total_declared ); ?></strong></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Total Published Content', 'megagovern' ); ?></td>
			<td><strong><?php echo esc_html( (string) $all_content ); ?></strong></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Transparency Coverage Rate', 'megagovern' ); ?></td>
			<td><strong><?php echo esc_html( (string) $coverage ); ?>%</strong></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Verification Page URL', 'megagovern' ); ?></td>
			<td><a href="<?php echo esc_url( $data['verification'] ); ?>"><?php echo esc_html( $data['verification'] ); ?></a></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'AI.txt Compliance File', 'megagovern' ); ?></td>
			<td><a href="<?php echo esc_url( $data['aitxt'] ); ?>"><?php echo esc_html( $data['aitxt'] ); ?></a></td>
		</tr>
	</table>

	<?php if ( $has_deepfake ) : ?>
		<h2><?php esc_html_e( 'Regulatory Notice: Synthetic Media Detected', 'megagovern' ); ?></h2>
		<p style="color: #d63638;"><strong><?php esc_html_e( 'Warning:', 'megagovern' ); ?></strong> <?php esc_html_e( 'This site currently publishes synthetic or deepfake media items. Ensure appropriate disclosures and watermarking comply with regional regulatory frameworks (EU AI Act, FTC guidelines).', 'megagovern' ); ?></p>
	<?php endif; ?>

	<div class="footer">
		<p class="disclaimer">
			<strong><?php esc_html_e( 'LEGAL DISCLAIMER:', 'megagovern' ); ?></strong>
			<?php esc_html_e( 'This compliance report is generated by MegaGovern for transparency and documentation purposes only. It does not constitute legal advice, legal compliance certification, or a guarantee of regulatory compliance. Consult qualified legal counsel for compliance determinations specific to your jurisdiction.', 'megagovern' ); ?>
		</p>
		<?php if ( ! $is_white_label ) : ?>
			<?php
			/* translators: %s: generation timestamp */
			$megagovern_footer_text = sprintf( __( 'Generated by MegaGovern — AI Content Governance & Transparency Platform on %s', 'megagovern' ), $data['generated_at'] );
			?>
			<p><?php echo esc_html( $megagovern_footer_text ); ?></p>
		<?php endif; ?>
	</div>
</body>
</html>