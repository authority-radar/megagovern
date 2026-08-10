<?php
/**
 * AI Policy Settings Page
 *
 * @package MegaGovern
 * @since   1.0.4
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

use MegaGovern\License;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$mg_policy_is_pro = License::is_pro();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$mg_policy_intro  = get_option( 'megagovern_policy_intro', '' );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$mg_policy_email  = get_option( 'megagovern_policy_email', get_bloginfo( 'admin_email' ) );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$mg_policy_url    = get_option( 'megagovern_policy_contact_url', home_url( '/contact' ) );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$mg_policy_saved  = false;

if ( isset( $_POST['save_policy'] ) ) {
	check_admin_referer( 'megagovern_policy_save' );

	// Check each field exists before updating.
	if ( isset( $_POST['policy_intro'] ) ) {
		update_option( 'megagovern_policy_intro', wp_kses_post( wp_unslash( $_POST['policy_intro'] ) ) );
	}
	if ( isset( $_POST['policy_email'] ) ) {
		update_option( 'megagovern_policy_email', sanitize_email( wp_unslash( $_POST['policy_email'] ) ) );
	}
	if ( isset( $_POST['policy_contact_url'] ) ) {
		update_option( 'megagovern_policy_contact_url', esc_url_raw( wp_unslash( $_POST['policy_contact_url'] ) ) );
	}
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$mg_policy_intro = get_option( 'megagovern_policy_intro', '' );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$mg_policy_email = get_option( 'megagovern_policy_email', '' );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$mg_policy_url   = get_option( 'megagovern_policy_contact_url', '' );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$mg_policy_saved = true;
}
?>
<div class="wrap megagovern-policy" style="max-width:700px;">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-media-document" style="color:#2271b1; margin-right:8px;"></span>
		<?php esc_html_e( 'AI Policy Settings', 'megagovern' ); ?>
	</h1>
	<a href="<?php echo esc_url( home_url( '/ai-policy' ) ); ?>" target="_blank" class="button" style="margin-left:8px;"><?php esc_html_e( 'View Policy', 'megagovern' ); ?> →</a>
	<hr class="wp-header-end">

	<?php if ( $mg_policy_saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Saved.', 'megagovern' ); ?></p>
		</div>
	<?php endif; ?>

	<div style="background:#fff; border:1px solid #e5e5e5; border-radius:8px; overflow:hidden;">
		<div style="padding:14px 20px; border-bottom:1px solid #f0f0f1;">
			<h2 style="margin:0; font-size:13px; font-weight:600;"><?php esc_html_e( 'Customize Policy', 'megagovern' ); ?></h2>
		</div>
		<div style="padding:14px 20px;">
			<form method="post">
				<?php wp_nonce_field( 'megagovern_policy_save' ); ?>

				<?php if ( $mg_policy_is_pro ) : ?>
					<div style="margin-bottom:12px;">
						<label style="font-size:11px; font-weight:600; display:block; margin-bottom:4px;"><?php esc_html_e( 'Custom Introduction', 'megagovern' ); ?></label>
						<textarea name="policy_intro" rows="5" style="width:100%; font-size:11px;"><?php echo esc_textarea( $mg_policy_intro ); ?></textarea>
						<p style="font-size:9px; color:#646970;"><?php esc_html_e( 'Appears at the top of your AI Policy page.', 'megagovern' ); ?></p>
					</div>
				<?php else : ?>
					<p style="color:#dba617; font-size:11px;">🔒 <?php esc_html_e( 'Custom introduction is a Pro feature.', 'megagovern' ); ?></p>
				<?php endif; ?>

				<div style="margin-bottom:12px;">
					<label style="font-size:11px; font-weight:600; display:block; margin-bottom:4px;"><?php esc_html_e( 'Contact Email', 'megagovern' ); ?></label>
					<input type="email" name="policy_email" value="<?php echo esc_attr( $mg_policy_email ); ?>" style="width:100%; font-size:11px;">
				</div>

				<div style="margin-bottom:12px;">
					<label style="font-size:11px; font-weight:600; display:block; margin-bottom:4px;"><?php esc_html_e( 'Contact Page URL', 'megagovern' ); ?></label>
					<input type="url" name="policy_contact_url" value="<?php echo esc_attr( $mg_policy_url ); ?>" style="width:100%; font-size:11px;">
				</div>

				<button type="submit" name="save_policy" class="button button-primary"><?php esc_html_e( 'Save', 'megagovern' ); ?></button>
			</form>
		</div>
	</div>
</div>