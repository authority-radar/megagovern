<?php
/**
 * AI Notice Settings
 *
 * @package MegaGovern
 * @since   1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$settings = $this->get_settings();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$pages = get_pages();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'AI Notice Settings', 'megagovern' ); ?></h1>
	<p><?php esc_html_e( 'Customize the AI transparency notice bar displayed on your website.', 'megagovern' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'megagovern_notice_settings' ); ?>
		<?php do_settings_sections( 'megagovern_notice_settings' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="megagovern_notice_enabled">
						<?php esc_html_e( 'Enable Notice', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" id="megagovern_notice_enabled" name="megagovern_notice_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
					<p class="description"><?php esc_html_e( 'Show AI transparency notice on the frontend.', 'megagovern' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_position">
						<?php esc_html_e( 'Position', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<select id="megagovern_notice_position" name="megagovern_notice_position">
						<option value="top" <?php selected( $settings['position'], 'top' ); ?>>
							<?php esc_html_e( 'Top of page', 'megagovern' ); ?>
						</option>
						<option value="bottom" <?php selected( $settings['position'], 'bottom' ); ?>>
							<?php esc_html_e( 'Bottom of page', 'megagovern' ); ?>
						</option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_dismiss_days">
						<?php esc_html_e( 'Dismiss Duration (Days)', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<input type="number" id="megagovern_notice_dismiss_days" name="megagovern_notice_dismiss_days" value="<?php echo esc_attr( $settings['dismiss_days'] ); ?>" min="1" max="30" step="1" style="width:80px;">
					<p class="description"><?php esc_html_e( 'How many days to hide the notice after dismissal.', 'megagovern' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_bg_color">
						<?php esc_html_e( 'Background Color', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<input type="color" id="megagovern_notice_bg_color" name="megagovern_notice_bg_color" value="<?php echo esc_attr( $settings['bg_color'] ); ?>">
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_text_color">
						<?php esc_html_e( 'Text Color', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<input type="color" id="megagovern_notice_text_color" name="megagovern_notice_text_color" value="<?php echo esc_attr( $settings['text_color'] ); ?>">
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_link_color">
						<?php esc_html_e( 'Link Color', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<input type="color" id="megagovern_notice_link_color" name="megagovern_notice_link_color" value="<?php echo esc_attr( $settings['link_color'] ); ?>">
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_show_logo">
						<?php esc_html_e( 'Show Logo', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" id="megagovern_notice_show_logo" name="megagovern_notice_show_logo" value="1" <?php checked( $settings['show_logo'] ); ?>>
					<p class="description"><?php esc_html_e( 'Display MegaGovern logo in the notice.', 'megagovern' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_show_icon">
						<?php esc_html_e( 'Show Icon', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" id="megagovern_notice_show_icon" name="megagovern_notice_show_icon" value="1" <?php checked( $settings['show_icon'] ); ?>>
					<p class="description"><?php esc_html_e( 'Display AI shield icon in the notice.', 'megagovern' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_policy_page">
						<?php esc_html_e( 'AI Policy Page', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<select id="megagovern_notice_policy_page" name="megagovern_notice_policy_page">
						<option value=""><?php esc_html_e( '— Select page —', 'megagovern' ); ?></option>
						<?php foreach ( $pages as $page ) : ?>
							<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['policy_page'], $page->ID ); ?>>
								<?php echo esc_html( $page->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the page where your AI policy is displayed.', 'megagovern' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="megagovern_notice_custom_text">
						<?php esc_html_e( 'Custom Text', 'megagovern' ); ?>
					</label>
				</th>
				<td>
					<textarea id="megagovern_notice_custom_text" name="megagovern_notice_custom_text" rows="3" style="width:100%;max-width:500px;"><?php echo esc_textarea( $settings['custom_text'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Custom notice text. Leave empty for default.', 'megagovern' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>