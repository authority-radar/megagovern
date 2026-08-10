<?php
/**
 * Transparency Center — Tab: Content & Labels — V1.0.4
 * - FIXED: Direct save with simple success flag
 * - FIXED: Post type selection saves correctly
 * - Works on all CPTs, WooCommerce, posts, pages, etc.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_is_pro      = $args['is_pro']      ?? false;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_is_agency   = $args['is_agency']   ?? false;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_upgrade_url = $args['upgrade_url'] ?? '';

// ─── Handle Save ───
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_saved = false;
if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	if ( isset( $_POST['megagovern_disclosure_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['megagovern_disclosure_nonce'] ) ), 'megagovern_disclosure_nonce' ) ) {

		// Content label toggle — FREE for all versions
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
		$megagovern_content_on = isset( $_POST['megagovern_content_label_enabled'] ) ? 1 : 0;
		update_option( 'megagovern_content_label_enabled', (bool) $megagovern_content_on );

		// Image label toggle — FREE for all versions
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
		$megagovern_image_on = isset( $_POST['megagovern_image_label_enabled'] ) ? 1 : 0;
		update_option( 'megagovern_image_label_enabled', (bool) $megagovern_image_on );

		// Label position
		if ( isset( $_POST['megagovern_label_position'] ) ) {
			update_option( 'megagovern_label_position', sanitize_text_field( wp_unslash( $_POST['megagovern_label_position'] ) ) );
		}

		// Label style
		if ( isset( $_POST['megagovern_label_style'] ) ) {
			update_option( 'megagovern_label_style', sanitize_text_field( wp_unslash( $_POST['megagovern_label_style'] ) ) );
		}

		// Custom text (Pro/Agency only)
		if ( isset( $_POST['megagovern_custom_label_text'] ) && is_array( $_POST['megagovern_custom_label_text'] ) ) {
			if ( $megagovern_is_pro || $megagovern_is_agency ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
				$megagovern_custom = array_map( 'sanitize_text_field', wp_unslash( $_POST['megagovern_custom_label_text'] ) );
				update_option( 'megagovern_custom_label_text', $megagovern_custom );
			}
		}

		// Post types — FREE for all versions, all CPTs supported
		if ( isset( $_POST['megagovern_declaration_post_types'] ) && is_array( $_POST['megagovern_declaration_post_types'] ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
			$megagovern_types = array_map( 'sanitize_key', wp_unslash( $_POST['megagovern_declaration_post_types'] ) );
			update_option( 'megagovern_declaration_post_types', $megagovern_types );
		} else {
			update_option( 'megagovern_declaration_post_types', [] );
		}

		// Image label position
		if ( isset( $_POST['megagovern_image_label_position'] ) ) {
			update_option( 'megagovern_image_label_position', sanitize_text_field( wp_unslash( $_POST['megagovern_image_label_position'] ) ) );
		}

		// Image label style
		if ( isset( $_POST['megagovern_image_label_style'] ) ) {
			update_option( 'megagovern_image_label_style', sanitize_text_field( wp_unslash( $_POST['megagovern_image_label_style'] ) ) );
		}

		if ( class_exists( '\MegaGovern\Registry' ) && method_exists( '\MegaGovern\Registry', 'clear_cache' ) ) {
			\MegaGovern\Registry::clear_cache();
		}

		$megagovern_saved = true;
	}
}

if ( $megagovern_saved ) {
	echo '<div class="notice notice-success is-dismissible" style="margin:0 0 16px 0;"><p>' . esc_html__( 'Settings saved.', 'megagovern' ) . '</p></div>';
}

// ─── Read current values ───
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_label_pos       = get_option( 'megagovern_label_position', 'top' );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_label_style     = get_option( 'megagovern_label_style', 'eu-icon' );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_content_enabled = (bool) get_option( 'megagovern_content_label_enabled', true );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_image_enabled   = (bool) get_option( 'megagovern_image_label_enabled', true );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_saved_types     = get_option( 'megagovern_declaration_post_types', [ 'post', 'page' ] );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_custom_text     = get_option( 'megagovern_custom_label_text', [ 'human' => '', 'ai_assisted' => '', 'ai_generated' => '', 'deepfake' => '' ] );
if ( ! is_array( $megagovern_custom_text ) ) {
	$megagovern_custom_text = [ 'human' => '', 'ai_assisted' => '', 'ai_generated' => '', 'deepfake' => '' ];
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_label_styles = [
	'eu-icon'  => __( 'Official EU Icon', 'megagovern' ),
	'pill'     => __( 'Pill Style', 'megagovern' ),
	'minimal'  => __( 'Minimal Text', 'megagovern' ),
	'banner'   => __( 'Banner Style', 'megagovern' ),
	'outline'  => __( 'Outline Card', 'megagovern' ),
];

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
$megagovern_text_fields = [
	'human'        => __( 'Human Written', 'megagovern' ),
	'ai_assisted'  => __( 'AI-Assisted', 'megagovern' ),
	'ai_generated' => __( 'AI-Generated', 'megagovern' ),
	'deepfake'     => __( 'Synthetic Media', 'megagovern' ),
];

if ( ! function_exists( 'megagovern_content_labels_icon' ) ) {
	/**
	 * Get Content Labels Icon SVG.
	 *
	 * @param string $name Icon name.
	 * @param string $size Icon size in pixels.
	 * @return string SVG markup.
	 */
	function megagovern_content_labels_icon( string $name, string $size = '16' ): string {
		$icons = [
			'tag' => '<svg width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2H2v10l9.29 9.29a1 1 0 0 0 1.41 0l7-7a1 1 0 0 0 0-1.41L12 2z"/></svg>',
		];
		return $icons[ $name ] ?? '';
	}
}
?>

<div class="mga-content-labels-wrap">
	<form method="post" action="<?php echo esc_url( add_query_arg( [ 'page' => 'megagovern-transparency', 'gtab' => 'content-labels' ], admin_url( 'admin.php' ) ) ); ?>">
		<?php wp_nonce_field( 'megagovern_disclosure_nonce', 'megagovern_disclosure_nonce' ); ?>
		<input type="hidden" name="gtab" value="content-labels" />

		<div class="mga-row mga-row-2col-s">
			<div class="mga-card">
				<div class="mga-card-header">
					<h3 class="mga-card-title"><?php echo wp_kses_post( megagovern_content_labels_icon( 'tag', '16' ) ); ?> <?php esc_html_e( 'Content Labels', 'megagovern' ); ?></h3>
					<label class="mga-toggle-switch">
						<input type="checkbox" name="megagovern_content_label_enabled" value="1" <?php checked( $megagovern_content_enabled, true ); ?>>
						<span class="mga-toggle-slider"></span>
					</label>
				</div>
				<div class="mga-card-body">
					<p class="mga-card-text"><?php esc_html_e( 'Choose how AI transparency labels appear on your content.', 'megagovern' ); ?></p>
					<div class="mga-label-style-grid">
						<?php foreach ( $megagovern_label_styles as $megagovern_k => $megagovern_lbl ) : ?>
							<label class="mga-label-style-item <?php echo $megagovern_label_style === $megagovern_k ? 'mga-label-style-item--active' : ''; ?>">
								<input type="radio" name="megagovern_label_style" value="<?php echo esc_attr( $megagovern_k ); ?>" <?php checked( $megagovern_label_style, $megagovern_k ); ?>>
								<span><?php echo esc_html( $megagovern_lbl ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<div class="mga-settings-field" style="margin-top:12px;">
						<label class="mga-settings-label"><?php esc_html_e( 'Placement', 'megagovern' ); ?></label>
						<select name="megagovern_label_position" class="mga-gov-select">
							<option value="top" <?php selected( $megagovern_label_pos, 'top' ); ?>><?php esc_html_e( 'Top of Content (Recommended)', 'megagovern' ); ?></option>
							<option value="both" <?php selected( $megagovern_label_pos, 'both' ); ?>><?php esc_html_e( 'Both Top & Bottom', 'megagovern' ); ?></option>
							<option value="manual" <?php selected( $megagovern_label_pos, 'manual' ); ?>><?php esc_html_e( 'Manual Only (Shortcode)', 'megagovern' ); ?></option>
						</select>
					</div>
				</div>
			</div>

			<div class="mga-card">
				<div class="mga-card-header"><h3 class="mga-card-title"><?php esc_html_e( 'Custom Label Text', 'megagovern' ); ?></h3></div>
				<div class="mga-card-body">
					<?php foreach ( $megagovern_text_fields as $megagovern_fk => $megagovern_fl ) :
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
						$megagovern_v = $megagovern_custom_text[ $megagovern_fk ] ?? '';
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
						$megagovern_dis = ( ! $megagovern_is_pro && ! $megagovern_is_agency ) ? 'disabled' : '';
					?>
						<div class="mga-settings-field">
							<label class="mga-settings-label"><?php echo esc_html( $megagovern_fl ); ?></label>
							<input type="text" name="megagovern_custom_label_text[<?php echo esc_attr( $megagovern_fk ); ?>]" value="<?php echo esc_attr( $megagovern_v ); ?>" class="mga-gov-input" <?php echo esc_attr( $megagovern_dis ); ?>>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="mga-card">
			<div class="mga-card-header"><h3 class="mga-card-title"><?php esc_html_e( 'Tracked Post Types', 'megagovern' ); ?></h3></div>
			<div class="mga-card-body">
				<p class="mga-card-text"><?php esc_html_e( 'Select which content types display AI transparency labels. Works on posts, pages, WooCommerce products, and all custom post types.', 'megagovern' ); ?></p>
				<div class="mga-post-types-list">
					<?php
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
					$megagovern_all = get_post_types( [ 'public' => true ], 'objects' );
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
					$megagovern_ex  = [ 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset' ];
					foreach ( $megagovern_all as $megagovern_t => $megagovern_o ) {
						if ( in_array( $megagovern_t, $megagovern_ex, true ) ) {
							continue;
						}
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
						$megagovern_ch = in_array( $megagovern_t, (array) $megagovern_saved_types, true );
					?>
						<label class="mga-post-type-item">
							<input type="checkbox" name="megagovern_declaration_post_types[]" value="<?php echo esc_attr( $megagovern_t ); ?>" <?php checked( $megagovern_ch ); ?>>
							<span><?php echo esc_html( $megagovern_o->labels->singular_name ); ?> (<?php echo esc_html( $megagovern_t ); ?>)</span>
						</label>
					<?php } ?>
				</div>
			</div>
		</div>

		<div class="mga-card">
			<div class="mga-card-header">
				<h3 class="mga-card-title"><?php esc_html_e( 'Image Labels', 'megagovern' ); ?></h3>
				<label class="mga-toggle-switch">
					<input type="checkbox" name="megagovern_image_label_enabled" value="1" <?php checked( $megagovern_image_enabled, true ); ?>>
					<span class="mga-toggle-slider"></span>
				</label>
			</div>
			<div class="mga-card-body">
				<div class="mga-settings-field">
					<label class="mga-settings-label"><?php esc_html_e( 'Label Position', 'megagovern' ); ?></label>
					<?php
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
					$megagovern_cp = get_option( 'megagovern_image_label_position', 'bottom-right' );
					?>
					<?php foreach ( [ 'bottom-right' => 'Bottom Right', 'bottom-left' => 'Bottom Left', 'top-right' => 'Top Right', 'top-left' => 'Top Left' ] as $megagovern_k => $megagovern_v ) : ?>
						<label><input type="radio" name="megagovern_image_label_position" value="<?php echo esc_attr( $megagovern_k ); ?>" <?php checked( $megagovern_cp, $megagovern_k ); ?>> <?php echo esc_html( $megagovern_v ); ?></label><br>
					<?php endforeach; ?>
				</div>
				<div class="mga-settings-field">
					<label class="mga-settings-label"><?php esc_html_e( 'Icon Style', 'megagovern' ); ?></label>
					<?php
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used locally.
					$megagovern_cs = get_option( 'megagovern_image_label_style', 'white-50' );
					?>
					<?php foreach ( [ 'white-50' => 'White 50%', 'black-50' => 'Black 50%', 'white' => 'White Solid', 'black' => 'Black Solid' ] as $megagovern_k => $megagovern_v ) : ?>
						<label><input type="radio" name="megagovern_image_label_style" value="<?php echo esc_attr( $megagovern_k ); ?>" <?php checked( $megagovern_cs, $megagovern_k ); ?>> <?php echo esc_html( $megagovern_v ); ?></label><br>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="mga-card" style="margin-top:16px;">
			<div class="mga-card-footer" style="text-align:center;padding:20px;">
				<button type="submit" class="button button-primary" style="min-width:200px;"><?php esc_html_e( 'Save All Label Settings', 'megagovern' ); ?></button>
			</div>
		</div>
	</form>
</div>