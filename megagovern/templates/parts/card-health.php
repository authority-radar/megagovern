<?php
/**
 * Health Card Template Part
 *
 * @package MegaGovern
 * @since   1.0.4
 * @var string $title   Card title
 * @var string $status  Status text
 * @var string $state   good|warning|critical
 * @var string $metric  Metric value
 * @var string $icon    Dashicon class
 * @var string $url     Optional link URL
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

use MegaGovern\Helpers;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable passed from parent.
$state_data = Helpers::health_states()[ $state ] ?? Helpers::health_states()['good'];
?>

<div class="megagovern-health-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:20px; display:flex; align-items:center; gap:16px; box-shadow:0 1px 2px rgba(0,0,0,0.04);">
	<div style="flex-shrink:0; width:48px; height:48px; border-radius:50%; background:<?php echo esc_attr( $state_data['bg'] ); ?>; display:flex; align-items:center; justify-content:center;">
		<span class="dashicons <?php echo esc_attr( $icon ); ?>" style="font-size:24px; width:24px; height:24px; color:<?php echo esc_attr( $state_data['color'] ); ?>;"></span>
	</div>
	<div style="flex:1; min-width:0;">
		<div style="font-size:12px; color:#646970; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
			<?php echo esc_html( $title ); ?>
		</div>
		<div style="font-size:20px; font-weight:600; color:#1d2327;">
			<?php echo esc_html( $metric ); ?>
		</div>
		<div style="font-size:12px; color:<?php echo esc_attr( $state_data['color'] ); ?>; font-weight:500;">
			<?php echo esc_html( $status ); ?>
		</div>
	</div>
	<?php if ( ! empty( $url ) ) : ?>
		<a href="<?php echo esc_url( $url ); ?>" style="flex-shrink:0; text-decoration:none; color:#2271b1; font-size:13px;">
			<?php esc_html_e( 'View', 'megagovern' ); ?> →
		</a>
	<?php endif; ?>
</div>