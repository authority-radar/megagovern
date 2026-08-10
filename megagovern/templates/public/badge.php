<?php
/**
 * Frontend Trust Badge Template
 *
 * Variables available:
 * $size       — small, medium, large
 * $verify_url — Verification page URL
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$sizes = [
	'small'  => [
		'container' => 'font-size: 11px; padding: 4px 10px;',
		'icon'      => 'font-size: 14px; width: 14px; height: 14px;',
	],
	'medium' => [
		'container' => 'font-size: 13px; padding: 6px 14px;',
		'icon'      => 'font-size: 16px; width: 16px; height: 16px;',
	],
	'large'  => [
		'container' => 'font-size: 15px; padding: 8px 18px;',
		'icon'      => 'font-size: 20px; width: 20px; height: 20px;',
	],
];

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$size_style = $sizes[ $size ] ?? $sizes['medium'];
?>

<a href="<?php echo esc_url( $verify_url ); ?>"
	class="megagovern-badge megagovern-badge-<?php echo esc_attr( $size ); ?>"
	style="
		display: inline-block;
		<?php echo esc_attr( $size_style['container'] ); ?>
		background: #2271b1;
		color: #fff;
		text-decoration: none;
		border-radius: 2px;
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		font-weight: 600;
		line-height: 1.5;
		transition: background 0.2s ease;
	"
	title="<?php esc_attr_e( 'Verified Transparent Site', 'megagovern' ); ?>"
	onmouseover="this.style.background='#135e96';"
	onmouseout="this.style.background='#2271b1';"
>
	<span class="dashicons dashicons-shield" style="
		<?php echo esc_attr( $size_style['icon'] ); ?>
		color: #fff;
		vertical-align: text-bottom;
	"></span>
	<?php esc_html_e( 'Transparent Site', 'megagovern' ); ?>
</a>