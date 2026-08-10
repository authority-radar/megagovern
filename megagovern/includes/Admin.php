<?php
/**
 * Admin Interface Manager — V1.0.4
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_notice_settings_save' ), 4 );
		add_action( 'admin_init', array( $this, 'maybe_handle_refresh' ), 5 );
		add_action( 'admin_init', array( $this, 'maybe_handle_content_labels_save' ), 6 );
		add_filter( 'wp_redirect', array( $this, 'preserve_gtab_on_save' ), 10, 2 );
	}

	
	/**
	 * Preserve the 'gtab' parameter after form submissions.
	 *
	 * This method reads the 'gtab' parameter from POST or the referer to include it
	 * in the redirect URL, ensuring users stay on the correct tab.
	 *
	 * Note: Nonce verification is not required here because this function does not
	 * modify any data; it only reads and appends a GET parameter for UX purposes.
	 *
	 * @param string $location The redirect URL.
	 * @param int    $status   The HTTP status code.
	 * @return string Modified URL.
	 */
	public function preserve_gtab_on_save( string $location, int $status ): string {
		if ( false === strpos( $location, 'megagovern-transparency' ) ) {
			return $location;
		}
		$gtab = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Not modifying data, only reading to preserve tab.
		if ( isset( $_POST['gtab'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$gtab = sanitize_text_field( wp_unslash( $_POST['gtab'] ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized  
		if ( empty( $gtab ) && isset( $_POST['_wp_http_referer'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$referer = esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) );
			parse_str( wp_parse_url( $referer, PHP_URL_QUERY ) ?? '', $ref );
			$gtab = $ref['gtab'] ?? '';
		}
		if ( $gtab ) {
			$location = add_query_arg( 'gtab', $gtab, $location );
		}
		return $location;
	}
	
	public function maybe_handle_notice_settings_save(): void {
		if ( ! isset( $_POST['option_page'] ) || 'megagovern_notice_settings' !== sanitize_text_field( wp_unslash( $_POST['option_page'] ) ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to save these settings.', 'megagovern' ) );
		}
		check_admin_referer( 'megagovern_notice_settings-options' );

		// Colors
		if ( isset( $_POST['megagovern_notice_bg_color'] ) ) {
			update_option( 'megagovern_notice_bg_color', sanitize_hex_color( wp_unslash( $_POST['megagovern_notice_bg_color'] ) ) );
		}
		if ( isset( $_POST['megagovern_notice_text_color'] ) ) {
			update_option( 'megagovern_notice_text_color', sanitize_hex_color( wp_unslash( $_POST['megagovern_notice_text_color'] ) ) );
		}
		if ( isset( $_POST['megagovern_notice_link_color'] ) ) {
			update_option( 'megagovern_notice_link_color', sanitize_hex_color( wp_unslash( $_POST['megagovern_notice_link_color'] ) ) );
		}

		// Position
		if ( isset( $_POST['megagovern_notice_position'] ) ) {
			update_option( 'megagovern_notice_position', sanitize_text_field( wp_unslash( $_POST['megagovern_notice_position'] ) ) );
		}

		// Text & display
		if ( isset( $_POST['megagovern_notice_custom_text'] ) ) {
			update_option( 'megagovern_notice_custom_text', sanitize_text_field( wp_unslash( $_POST['megagovern_notice_custom_text'] ) ) );
		}
		if ( isset( $_POST['megagovern_notice_powered_text'] ) ) {
			update_option( 'megagovern_notice_powered_text', sanitize_text_field( wp_unslash( $_POST['megagovern_notice_powered_text'] ) ) );
		}
		if ( isset( $_POST['megagovern_notice_show_powered'] ) ) {
			update_option( 'megagovern_notice_show_powered', true );
		} else {
			update_option( 'megagovern_notice_show_powered', false );
		}
		if ( isset( $_POST['megagovern_notice_enabled'] ) ) {
			update_option( 'megagovern_notice_enabled', true );
		} else {
			update_option( 'megagovern_notice_enabled', false );
		}
		if ( isset( $_POST['megagovern_notice_policy_page'] ) ) {
			update_option( 'megagovern_notice_policy_page', absint( wp_unslash( $_POST['megagovern_notice_policy_page'] ) ) );
		}

		// Clear cache
		if ( class_exists( '\MegaGovern\Registry' ) && method_exists( '\MegaGovern\Registry', 'clear_cache' ) ) {
			\MegaGovern\Registry::clear_cache();
		}

		// Redirect back to the AI Notice tab
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'megagovern-transparency',
					'gtab'  => 'ai-notice',
					'saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function maybe_handle_refresh(): void {
		// Sanitize the 'page' parameter before use.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! $page || false === strpos( $page, 'megagovern' ) ) {
			return;
		}
		if ( ! isset( $_GET['refresh'] ) || '1' !== $_GET['refresh'] ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to refresh this page.', 'megagovern' ) );
		}
		check_admin_referer( 'megagovern_refresh_dashboard' );

		$user_id = get_current_user_id();
		delete_transient( 'megagovern_dashboard_v5_' . $user_id );
		delete_transient( 'megagovern_common_data' );
		delete_transient( 'megagovern_registry_stats' );
		delete_transient( 'megagovern_dashboard_stats' );
		delete_transient( 'megagovern_dashboard_health' );
		delete_transient( 'megagovern_transparency_common' );

		if ( class_exists( '\MegaGovern\Registry' ) && method_exists( '\MegaGovern\Registry', 'run_scan' ) ) {
			Registry::run_scan();
		}

		$clean_url = remove_query_arg( array( 'refresh', '_wpnonce', '_wp_http_referer' ) );
		wp_safe_redirect( $clean_url );
		exit;
	}

	public function maybe_handle_content_labels_save(): void {
		if ( ! isset( $_POST['megagovern_disclosure_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['megagovern_disclosure_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'megagovern_disclosure_nonce' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$content_enabled = isset( $_POST['megagovern_content_label_enabled'] );
		update_option( 'megagovern_content_label_enabled', $content_enabled );

		$img_enabled = isset( $_POST['megagovern_image_label_enabled'] );
		update_option( 'megagovern_image_label_enabled', $img_enabled );

		if ( isset( $_POST['megagovern_label_position'] ) ) {
			update_option( 'megagovern_label_position', sanitize_text_field( wp_unslash( $_POST['megagovern_label_position'] ) ) );
		}
		if ( isset( $_POST['megagovern_label_style'] ) ) {
			update_option( 'megagovern_label_style', sanitize_text_field( wp_unslash( $_POST['megagovern_label_style'] ) ) );
		}
		if ( isset( $_POST['megagovern_custom_label_text'] ) && is_array( $_POST['megagovern_custom_label_text'] ) ) {
			$custom = array_map( 'sanitize_text_field', wp_unslash( $_POST['megagovern_custom_label_text'] ) );
			update_option( 'megagovern_custom_label_text', $custom );
		}
		if ( isset( $_POST['megagovern_declaration_post_types'] ) && is_array( $_POST['megagovern_declaration_post_types'] ) ) {
			$types = array_map( 'sanitize_key', wp_unslash( $_POST['megagovern_declaration_post_types'] ) );
		} else {
			$types = array();
		}
		update_option( 'megagovern_declaration_post_types', $types );

		if ( isset( $_POST['megagovern_image_label_position'] ) ) {
			update_option( 'megagovern_image_label_position', sanitize_text_field( wp_unslash( $_POST['megagovern_image_label_position'] ) ) );
		}
		if ( isset( $_POST['megagovern_image_label_style'] ) ) {
			update_option( 'megagovern_image_label_style', sanitize_text_field( wp_unslash( $_POST['megagovern_image_label_style'] ) ) );
		}

		if ( class_exists( '\MegaGovern\Registry' ) && method_exists( '\MegaGovern\Registry', 'clear_cache' ) ) {
			\MegaGovern\Registry::clear_cache();
		}

		set_transient( 'megagovern_content_labels_saved', true, 30 );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'megagovern-transparency',
					'gtab'  => 'content-labels',
					'saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private function get_menu_icon( string $size = '24' ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
	}

	public function register_menu(): void {
		$icon_svg  = $this->get_menu_icon( '20' );
		$icon_data = 'data:image/svg+xml;base64,' . base64_encode( $icon_svg );
		add_menu_page( __( 'MegaGovern', 'megagovern' ), __( 'MegaGovern', 'megagovern' ), 'edit_posts', 'megagovern', array( $this, 'render_dashboard' ), $icon_data, 30 );
		add_submenu_page( 'megagovern', __( 'Dashboard', 'megagovern' ), __( 'Dashboard', 'megagovern' ), 'edit_posts', 'megagovern', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'megagovern', __( 'Transparency Center', 'megagovern' ), __( 'Transparency Center', 'megagovern' ), 'edit_posts', 'megagovern-transparency', array( $this, 'render_transparency_center' ) );
		add_submenu_page( 'megagovern', __( 'Regulatory Alerts', 'megagovern' ), __( 'Regulatory Alerts', 'megagovern' ), 'edit_posts', 'megagovern-alerts', array( $this, 'render_alerts' ) );
		add_submenu_page( 'megagovern', __( 'Reports & Evidence', 'megagovern' ), __( 'Reports & Evidence', 'megagovern' ), 'edit_posts', 'megagovern-reports', array( $this, 'render_reports' ) );
		add_submenu_page( 'megagovern', __( 'Settings', 'megagovern' ), __( 'Settings', 'megagovern' ), 'manage_options', 'megagovern-settings', array( $this, 'render_settings_page' ) );
		if ( $this->is_agency() ) {
			add_submenu_page( 'megagovern', __( 'Agency Hub', 'megagovern' ), __( 'Agency Hub', 'megagovern' ), 'manage_options', 'megagovern-agency', array( $this, 'render_agency' ) );
		}
		add_submenu_page( null, __( 'AI Policy', 'megagovern' ), __( 'AI Policy', 'megagovern' ), 'edit_posts', 'megagovern-policy', array( $this, 'render_policy' ) );
	}

	private function is_agency(): bool {
		if ( class_exists( '\MegaGovern\License' ) && method_exists( '\MegaGovern\License', 'is_agency' ) ) {
			return License::is_agency();
		}
		return false;
	}

	public function register_settings(): void {
		register_setting( 'megagovern_disclosure', 'megagovern_label_position', 'sanitize_text_field' );
		register_setting( 'megagovern_settings', 'megagovern_auto_aitxt', 'rest_sanitize_boolean' );
		register_setting( 'megagovern_settings', 'megagovern_auto_verify', 'rest_sanitize_boolean' );
		register_setting( 'megagovern_content_types', 'megagovern_declaration_post_types', array( $this, 'sanitize_post_types' ) );
		register_setting( 'megagovern_email_settings', 'megagovern_report_schedule', 'sanitize_text_field' );
		register_setting( 'megagovern_email_settings', 'megagovern_report_email', 'sanitize_email' );
		register_setting( 'megagovern_policy_settings', 'megagovern_policy_intro', 'sanitize_textarea_field' );
		register_setting( 'megagovern_policy_settings', 'megagovern_policy_email', 'sanitize_email' );
		register_setting( 'megagovern_policy_settings', 'megagovern_policy_contact_url', 'esc_url_raw' );
		register_setting( 'megagovern_settings', 'megagovern_region', 'sanitize_text_field' );
		register_setting( 'megagovern_agency_settings', 'megagovern_agency_scheduled_reports', 'rest_sanitize_boolean' );
		register_setting( 'megagovern_disclosure', 'megagovern_label_style', 'sanitize_text_field' );
		register_setting( 'megagovern_disclosure', 'megagovern_custom_label_text', array( $this, 'sanitize_custom_label_text' ) );
		register_setting( 'megagovern_archive_settings', 'megagovern_legacy_cutoff_date', 'sanitize_text_field' );
		register_setting( 'megagovern_archive_settings', 'megagovern_edit_threshold', 'absint' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_enabled', 'rest_sanitize_boolean' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_position', 'sanitize_text_field' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_bg_color', 'sanitize_hex_color' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_text_color', 'sanitize_hex_color' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_link_color', 'sanitize_hex_color' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_policy_page', 'absint' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_custom_text', 'sanitize_text_field' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_show_powered', 'rest_sanitize_boolean' );
		register_setting( 'megagovern_notice_settings', 'megagovern_notice_powered_text', 'sanitize_text_field' );
		register_setting( 'megagovern_disclosure', 'megagovern_content_label_enabled', array( 'type' => 'boolean', 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
		register_setting( 'megagovern_disclosure', 'megagovern_image_label_enabled', array( 'type' => 'boolean', 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
		register_setting( 'megagovern_disclosure', 'megagovern_image_label_position', 'sanitize_text_field' );
		register_setting( 'megagovern_disclosure', 'megagovern_image_label_style', 'sanitize_text_field' );
	}

	public function sanitize_post_types( $value ): array {
		if ( ! is_array( $value ) ) {
			return array( 'post', 'page' );
		}
		return array_map( 'sanitize_text_field', $value );
	}

	public function sanitize_custom_label_text( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', wp_unslash( $value ) );
	}

	private function render_topbar( string $title, string $subtitle = '', string $icon = 'shield-check' ): void {
		$plan_name  = class_exists( '\MegaGovern\License' ) ? License::get_plan_name() : __( 'Free', 'megagovern' );
		$plan_class = 'mga-plan--' . strtolower( $plan_name );
		$docs_url   = 'https://megagovern.com/docs';
		$refresh_url = wp_nonce_url( add_query_arg( 'refresh', '1' ), 'megagovern_refresh_dashboard' );
		$icons = array(
			'shield-check' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
			'file-text'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
			'bell'         => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
			'settings'     => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
			'building'     => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/></svg>',
			'database'     => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>',
			'book'         => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
			'refresh-cw'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>',
		);
		$page_icon = $icons[ $icon ] ?? $icons['shield-check'];
		?>
		<div class="mga-topbar">
			<div class="mga-topbar-left">
				<div>
					<h1>
						<span style="display:inline-block;vertical-align:middle;margin-right:10px;"><?php echo wp_kses_post( $page_icon ); ?></span>
						<?php echo esc_html( $title ); ?>
					</h1>
					<?php if ( ! empty( $subtitle ) ) : ?>
						<span class="mga-topbar-sub"><?php echo esc_html( $subtitle ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<div class="mga-topbar-right">
				<span class="mga-plan-badge <?php echo esc_attr( $plan_class ); ?>"><?php echo esc_html( strtoupper( $plan_name ) ); ?></span>
				<a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener" class="mga-topbar-docs"><?php echo wp_kses_post( $icons['book'] ); ?></a>
				<a href="<?php echo esc_url( $refresh_url ); ?>" class="mga-btn-refresh"><?php echo wp_kses_post( $icons['refresh-cw'] ); ?></a>
			</div>
		</div>
		<hr class="wp-header-end">
		<?php
	}

	public function render_dashboard(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'megagovern' ) );
		}
		$this->render_topbar( __( 'Dashboard', 'megagovern' ), __( 'AI transparency governance at a glance.', 'megagovern' ), 'shield-check' );
		$tpl = MEGAGOVERN_PATH . 'templates/admin/dashboard.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
		} else {
			$this->render_fallback( __( 'Mission Control', 'megagovern' ) );
		}
	}

	public function render_transparency_center(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'megagovern' ) );
		}
		$this->render_topbar( __( 'Transparency Center', 'megagovern' ), __( 'Manage content classifications, AI disclosures, and compliance records.', 'megagovern' ), 'database' );
		$tpl = MEGAGOVERN_PATH . 'templates/admin/governance.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
			return;
		}
		$tpl = MEGAGOVERN_PATH . 'templates/admin/transparency-center.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
			return;
		}
		$this->render_fallback( __( 'Transparency Center', 'megagovern' ) );
	}

	public function render_alerts(): void {
		$this->render_topbar( __( 'Regulatory Alerts', 'megagovern' ), __( 'Track AI regulations affecting your content. Updated with each plugin release.', 'megagovern' ), 'bell' );
		$tpl = MEGAGOVERN_PATH . 'templates/admin/tab-alerts.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
		} else {
			$this->render_fallback( __( 'Regulatory Alerts', 'megagovern' ) );
		}
	}

	public function render_reports(): void {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'megagovern_manage_compliance' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'megagovern' ) );
		}
		$this->render_topbar( __( 'Reports & Evidence', 'megagovern' ), __( 'Compliance reports, evidence bundles, and audit trails.', 'megagovern' ), 'file-text' );
		$flags      = class_exists( '\MegaGovern\License' ) ? License::get_flags() : array();
		$is_pro     = (bool) ( $flags['is_pro'] ?? false );
		$is_agency  = (bool) ( $flags['is_agency'] ?? false );
		$is_free    = (bool) ( $flags['is_free'] ?? true );
		$plan_name  = class_exists( '\MegaGovern\License' ) ? License::get_plan_name() : __( 'Free', 'megagovern' );
		$has_pro    = $is_pro || $is_agency;
		$evidence_logs = class_exists( '\MegaGovern\Governance' ) ? Governance::get_actions( array( 'limit' => 20 ) ) : array();
		$registry_total = class_exists( '\MegaGovern\Registry' ) ? Registry::count_total() : 0;
		$last_scan  = get_option( 'megagovern_last_scan', array() );
		$registry_last = is_array( $last_scan ) ? ( $last_scan['scanned_at'] ?? __( 'Active', 'megagovern' ) ) : __( 'Active', 'megagovern' );
		$schedule   = get_option( 'megagovern_report_schedule', 'none' );
		$email      = get_option( 'megagovern_report_email', get_bloginfo( 'admin_email' ) );
		$site_id    = get_option( 'megagovern_site_id', '' );
		$hash       = substr( hash( 'sha256', $site_id . current_time( 'mysql' ) ), 0, 16 );
		$upgrade_url = 'https://megagovern.com/pricing';
		$args       = array(
			'evidence_logs'  => $evidence_logs,
			'registry_total' => $registry_total,
			'registry_last'  => $registry_last,
			'has_pro'        => $has_pro,
			'is_free'        => $is_free,
			'is_agency'      => $is_agency,
			'plan_name'      => $plan_name,
			'schedule'       => $schedule,
			'email'          => $email,
			'hash'           => $hash,
			'site_id'        => $site_id,
			'upgrade_url'    => $upgrade_url,
		);
		$tpl = MEGAGOVERN_PATH . 'templates/admin/tab-reports.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
		} else {
			$this->render_fallback( __( 'Reports & Evidence', 'megagovern' ) );
		}
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'megagovern' ) );
		}
		$this->render_topbar( __( 'Settings', 'megagovern' ), __( 'Configure MegaGovern for your compliance needs.', 'megagovern' ), 'settings' );
		$tpl = MEGAGOVERN_PATH . 'templates/admin/settings.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
			return;
		}
		$legacy = MEGAGOVERN_PATH . 'templates/admin/settings-page.php';
		if ( file_exists( $legacy ) ) {
			include $legacy;
			return;
		}
		$this->render_fallback( __( 'Settings', 'megagovern' ) );
	}

	public function render_agency(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'megagovern' ) );
		}
		$this->render_topbar( __( 'Agency Hub', 'megagovern' ), __( 'Manage multiple sites, white label settings, and team access.', 'megagovern' ), 'building' );
		$tpl = MEGAGOVERN_PATH . 'templates/admin/agency.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
			return;
		}
		$legacy = MEGAGOVERN_PATH . 'templates/admin/agency-dashboard.php';
		if ( file_exists( $legacy ) ) {
			include $legacy;
			return;
		}
		$this->render_fallback( __( 'Agency', 'megagovern' ) );
	}

	public function render_policy(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'megagovern' ) );
		}
		$this->render_topbar( __( 'AI Policy', 'megagovern' ), __( 'Manage your public AI usage policy.', 'megagovern' ), 'file-text' );
		$tpl = MEGAGOVERN_PATH . 'templates/admin/policy.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
		} else {
			$this->render_fallback( __( 'AI Policy', 'megagovern' ) );
		}
	}

	private function render_fallback( string $title ): void {
		echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1><div class="postbox"><div class="inside"><p style="color:#646970;padding:24px;text-align:center;">' . esc_html__( 'This section is coming soon.', 'megagovern' ) . '</p></div></div></div>';
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! is_string( $hook ) || strpos( $hook, 'megagovern' ) === false ) {
			return;
		}
		wp_enqueue_style( 'megagovern-admin', MEGAGOVERN_URL . 'assets/css/admin.css', array(), MEGAGOVERN_VERSION );
		wp_enqueue_script( 'megagovern-admin', MEGAGOVERN_URL . 'assets/js/admin.js', array( 'jquery' ), MEGAGOVERN_VERSION, true );
		wp_localize_script(
			'megagovern-admin',
			'megagovern_vars',
			array(
				'autofix_nonce' => wp_create_nonce( 'megagovern_auto_fix' ),
				'scan_nonce'    => wp_create_nonce( 'megagovern_scan' ),
				'cache_nonce'   => wp_create_nonce( 'megagovern_clear_dashboard_cache' ),
			)
		);
	}
}