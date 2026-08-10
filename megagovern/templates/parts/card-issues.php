<?php
/**
 * Issues Panel Template Part
 *
 * @package MegaGovern
 * @since   3.2.0
 * @var array $issues Array of issue objects
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

use MegaGovern\Helpers;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable passed from parent.
if ( empty( $issues ) ) {
	return;
}
?>

<div class="postbox" style="margin-bottom:20px;">
	<div class="postbox-header">
		<h2>
			<?php esc_html_e( 'Attention Required', 'megagovern' ); ?>
			<span style="font-weight:normal; color:#646970; margin-left:8px;">
				<?php echo count( $issues ); ?> <?php esc_html_e( 'issues', 'megagovern' ); ?>
			</span>
		</h2>
	</div>
	<div class="inside" style="padding:0;">
		<?php foreach ( $issues as $issue ) :
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
			$severity = Helpers::severity_levels()[ $issue['severity'] ] ?? Helpers::severity_levels()['low'];
		?>
			<div style="display:flex; align-items:center; gap:12px; padding:12px 16px; border-bottom:1px solid #f0f0f1;">
				<div style="flex-shrink:0; font-size:16px;">
					<span style="color:<?php echo esc_attr( $severity['color'] ); ?>;"><?php echo esc_html( $severity['icon'] ); ?></span>
				</div>
				<div style="flex:1; min-width:0;">
					<div style="font-weight:500; color:#1d2327;">
						<?php echo esc_html( $issue['title'] ); ?>
						<?php if ( 'high' === $issue['severity'] ) : ?>
							<span style="background:#fcf0f1; color:#d63638; padding:2px 8px; border-radius:3px; font-size:10px; font-weight:600; margin-left:6px;">
								<?php echo esc_html( $severity['label'] ); ?>
							</span>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $issue['problem'] ) ) : ?>
						<div style="font-size:12px; color:#646970; margin-top:2px;">
							<?php echo esc_html( $issue['problem'] ); ?>
						</div>
					<?php endif; ?>
				</div>
				<div style="flex-shrink:0;">
					<a href="<?php echo esc_url( $issue['link'] ?? '#' ); ?>" class="button<?php echo 'auto' === ( $issue['fix_type'] ?? '' ) ? ' button-primary' : ''; ?>">
						<?php if ( 'auto' === ( $issue['fix_type'] ?? '' ) ) : ?>
							<span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px;"></span>
						<?php endif; ?>
						<?php echo esc_html( $issue['action'] ?? __( 'Fix', 'megagovern' ) ); ?>
					</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>