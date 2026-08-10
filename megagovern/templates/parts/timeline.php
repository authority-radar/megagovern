<?php
/**
 * Transparency Timeline Template Part
 *
 * @package MegaGovern
 * @since   1.04
 * @var array $actions Array of governance action objects
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

use MegaGovern\Helpers;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable passed from parent.
if ( empty( $actions ) ) {
	return;
}

// Group by date.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$grouped = [];
foreach ( $actions as $action ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
	$date = date_i18n( get_option( 'date_format' ), strtotime( $action['logged_at'] ) );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$grouped[ $date ][] = $action;
}
?>

<div class="megagovern-timeline" style="position:relative; padding-left:32px;">
	<?php foreach ( $grouped as $date => $day_actions ) : ?>
		<div style="position:relative; margin-bottom:20px;">
			<div style="position:absolute; left:-40px; top:0; width:16px; height:16px; border-radius:50%; background:#2271b1; border:3px solid #fff; box-shadow:0 0 0 2px #2271b1; z-index:1;"></div>
			<div style="font-size:13px; font-weight:600; color:#1d2327; margin-bottom:12px;">
				<?php echo esc_html( $date ); ?>
			</div>
			<div style="display:flex; flex-direction:column; gap:8px;">
				<?php foreach ( $day_actions as $action ) : ?>
					<div style="display:flex; align-items:flex-start; gap:12px; padding:10px 14px; background:#fff; border:1px solid #e5e5e5; border-radius:4px; transition:box-shadow 0.2s;">
						<div style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:#f0f6fc; display:flex; align-items:center; justify-content:center;">
							<span class="dashicons <?php echo esc_attr( Helpers::action_icon( $action['action'] ) ); ?>" style="color:#2271b1; font-size:16px; width:16px; height:16px;"></span>
						</div>
						<div style="flex:1; min-width:0;">
							<div style="font-weight:500; color:#1d2327; font-size:13px;">
								<?php echo esc_html( Helpers::action_label( $action['action'] ) ); ?>
							</div>
							<?php if ( ! empty( $action['post_id'] ) ) : ?>
								<div style="font-size:12px; color:#646970; margin-top:2px;">
									<a href="<?php echo esc_url( get_edit_post_link( $action['post_id'] ) ); ?>" style="color:#2271b1;">
										<?php echo esc_html( get_the_title( $action['post_id'] ) ?: 'Post #' . $action['post_id'] ); ?>
									</a>
									<span style="margin:0 4px;">·</span>
									<?php echo esc_html( Helpers::format_type( $action['declaration_type'] ?? '' ) ); ?>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $action['note'] ) ) : ?>
								<div style="font-size:11px; color:#a7aaad; margin-top:2px; font-style:italic;">
									<?php echo esc_html( $action['note'] ); ?>
								</div>
							<?php endif; ?>
						</div>
						<div style="flex-shrink:0; font-size:11px; color:#a7aaad; padding-top:2px;">
							<?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $action['logged_at'] ) ) ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
	<div style="position:absolute; left:8px; top:8px; bottom:0; width:2px; background:#f0f0f1; z-index:0;"></div>
</div>