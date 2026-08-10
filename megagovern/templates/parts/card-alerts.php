<?php
/**
 * Alerts Widget Template Part
 *
 * @var array $alerts Array of alert objects (max 3)
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

use MegaGovern\Alerts;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable passed from parent.
if ( empty( $alerts ) ) {
	return;
}
?>

<div class="postbox" style="margin-bottom:20px;">
	<div class="postbox-header">
		<h2>
			<?php esc_html_e( 'Regulatory Alerts', 'megagovern' ); ?>
		</h2>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=megagovern-settings' ) ); ?>" style="font-size:12px;">
			<?php esc_html_e( 'View All', 'megagovern' ); ?> →
		</a>
	</div>
	<div class="inside" style="padding:0;">
		<?php foreach ( $alerts as $alert ) : ?>
			<div style="padding:12px 16px; border-bottom:1px solid #f0f0f1;">
				<div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
					<span class="megagovern-badge-jurisdiction">
						<?php echo esc_html( Alerts::get_jurisdiction_label( $alert['jurisdiction'] ?? 'global' ) ); ?>
					</span>
					<span style="font-size:12px; color:#646970;">
						<?php echo esc_html( $alert['date'] ?? '' ); ?>
					</span>
				</div>
				<div style="font-weight:600; color:#1d2327; margin-bottom:2px;">
					<?php echo esc_html( $alert['title'] ?? '' ); ?>
				</div>
				<?php if ( ! empty( $alert['description'] ) ) : ?>
					<div style="font-size:12px; color:#646970;">
						<?php echo esc_html( $alert['description'] ); ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $alert['url'] ) ) : ?>
					<a href="<?php echo esc_url( $alert['url'] ); ?>" target="_blank" style="font-size:12px;">
						<?php esc_html_e( 'Read more', 'megagovern' ); ?> →
					</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>