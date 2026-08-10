<?php
/**
 * Recent Activity Template Part
 *
 * @package MegaGovern
 * @since   1.0.4
 * @var array $actions Array of governance action objects (max 5)
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

use MegaGovern\Helpers;

if ( empty( $actions ) ) {
	return;
}
?>

<div class="postbox" style="margin-bottom:20px;">
	<div class="postbox-header">
		<h2><?php esc_html_e( 'Recent Activity', 'megagovern' ); ?></h2>
	</div>
	<div class="inside" style="padding:0;">
		<?php foreach ( $actions as $action ) : ?>
			<div style="display:flex; align-items:center; gap:12px; padding:10px 16px; border-bottom:1px solid #f0f0f1;">

				<span class="dashicons <?php echo esc_attr( Helpers::action_icon( $action['action'] ) ); ?>"
					  style="color:#2271b1; font-size:16px; width:16px; height:16px; flex-shrink:0;">
				</span>

				<div style="flex:1; min-width:0;">
					<span style="font-weight:500; color:#1d2327;">
						<?php echo esc_html( Helpers::action_label( $action['action'] ) ); ?>
					</span>
					<?php if ( ! empty( $action['post_id'] ) ) : ?>
						<span style="color:#646970; font-size:12px;">
							· <?php echo esc_html( get_the_title( $action['post_id'] ) ?: 'Post #' . $action['post_id'] ); ?>
						</span>
					<?php endif; ?>
				</div>

				<span style="color:#a7aaad; font-size:12px; flex-shrink:0;">
					<?php echo esc_html( human_time_diff( strtotime( $action['logged_at'] ) ) ); ?> <?php esc_html_e( 'ago', 'megagovern' ); ?>
				</span>

			</div>
		<?php endforeach; ?>
	</div>
</div>