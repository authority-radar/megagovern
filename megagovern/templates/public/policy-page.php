<?php
/**
 * Policy Page
 *
 * @package MegaGovern
 * @since   1.0.4
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, follow">
	<title><?php bloginfo( 'name' ); ?> — <?php esc_html_e( 'AI Policy', 'megagovern' ); ?></title>
	<?php wp_head(); ?>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			background: #f0f0f1; color: #1d2327; line-height: 1.6; font-size: 14px;
		}
		.mg-policy-container {
			max-width: 780px; margin: 30px auto; background: #fff;
			border: 1px solid #c3c4c7; border-radius: 6px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;
		}
		.mg-policy-header {
			text-align: center; padding: 32px 32px 20px;
			background: linear-gradient(180deg, #f0f6fc 0%, #fff 100%);
			border-bottom: 1px solid #e5e5e5;
		}
		.mg-policy-shield { font-size: 36px; margin-bottom: 8px; }
		.mg-policy-header h1 { font-size: 22px; color: #1d2327; margin-bottom: 4px; }
		.mg-policy-site { font-size: 14px; color: #50575e; margin-bottom: 10px; }
		.mg-policy-meta { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; font-size: 11px; color: #646970; }
		.mg-policy-stats {
			display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
			padding: 16px 32px; border-bottom: 1px solid #e5e5e5;
		}
		.mg-stat-card {
			text-align: center; padding: 12px 8px; border-radius: 6px;
			background: #f0f6fc; border: 1px solid #c3c4c7;
		}
		.mg-stat-number { font-size: 24px; font-weight: 700; color: #2271b1; }
		.mg-stat-label { font-size: 10px; color: #50575e; margin-top: 2px; }
		.mg-stat-pct { font-size: 9px; color: #646970; margin-top: 1px; }
		.mg-policy-section { padding: 20px 32px; border-bottom: 1px solid #f0f0f1; }
		.mg-policy-section h2 { font-size: 16px; color: #1d2327; margin-bottom: 8px; }
		.mg-policy-section p, .mg-policy-section li { margin-bottom: 6px; color: #3c434a; font-size: 13px; }
		.mg-policy-section ul { margin-left: 18px; }
		.mg-policy-live { font-size: 10px; color: #00a32a; font-weight: 500; margin-bottom: 8px; }
		.mg-policy-table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 12px; }
		.mg-policy-table th { background: #f0f0f1; padding: 8px 12px; text-align: left; font-weight: 600; border: 1px solid #c3c4c7; }
		.mg-policy-table td { padding: 8px 12px; border: 1px solid #c3c4c7; }
		.mg-verify-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
		.mg-verify-card {
			display: flex; flex-direction: column; gap: 3px; padding: 12px;
			background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px;
			text-decoration: none; color: #1d2327; transition: box-shadow 0.2s; font-size: 11px;
		}
		.mg-verify-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
		.mg-verify-icon { font-size: 18px; }
		.mg-verify-card strong { font-size: 11px; }
		.mg-verify-card span:last-child { font-size: 9px; color: #646970; word-break: break-all; }
		.mg-policy-disclaimer {
			margin: 20px 32px; padding: 16px; background: #fcf9e8;
			border: 1px solid #dba617; border-radius: 6px; font-size: 11px;
		}
		.mg-policy-disclaimer h3 { color: #dba617; margin-bottom: 6px; font-size: 13px; }
		.mg-policy-footer {
			text-align: center; padding: 16px 32px; background: #f0f0f1;
			border-top: 1px solid #c3c4c7; font-size: 11px; color: #50575e;
		}
		@media (max-width: 768px) {
			.mg-policy-container { margin: 0; border-radius: 0; }
			.mg-policy-stats { grid-template-columns: 1fr; padding: 12px 16px; }
			.mg-policy-section { padding: 16px; }
			.mg-verify-cards { grid-template-columns: 1fr; }
			.mg-policy-header { padding: 24px 16px; }
			.mg-policy-disclaimer { margin: 16px; }
		}
	</style>
</head>
<body <?php body_class(); ?>>
	<div class="mg-policy-container">
		<?php
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
		$policy = new \MegaGovern\Policy();
		$policy->render_policy_content();
		?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>