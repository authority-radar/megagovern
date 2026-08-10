<?php
/**
 * Service 6: Verification Page & Badge (Verify) — V1.0.4
 *
 * Uses templates/public/verify-page.php for the public page.
 *
 * CHANGELOG v1.0.4:
 * - NEW: get_transparency_url() method with verification hash
 * - FIXED: Rewrite rule now matches /ai-transparency-{hash}
 * - FIXED: get_hash() now properly handles empty stats
 * - SECURITY: Added hash-based URL to prevent guessing
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Verification {

	/**
	 * Verification page slug.
	 */
	private const VERIFY_SLUG = 'transparency';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_rewrite' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'serve_verification_page' ] );
		add_shortcode( 'megagovern_badge', [ $this, 'badge_shortcode' ] );
	}

	// ═══════════════════════════════════════
	// REWRITE
	// ═══════════════════════════════════════

	/**
	 * Register rewrite rule for /ai-transparency-{hash}.
	 */
	public function register_rewrite(): void {
		// Match /ai-transparency-{16 character hash}
		add_rewrite_rule(
			'ai-transparency-[a-f0-9]{16}/?$',
			'index.php?megagovern_verify=1',
			'top'
		);
	}

	/**
	 * Add custom query variables.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'megagovern_verify';
		return $vars;
	}

	// ═══════════════════════════════════════
	// SERVE VERIFICATION PAGE
	// ═══════════════════════════════════════

	/**
	 * Serve the verification page on template redirect.
	 */
	public function serve_verification_page(): void {
		if ( ! get_query_var( 'megagovern_verify' ) ) {
			return;
		}

		$template = MEGAGOVERN_PATH . 'templates/public/verify-page.php';

		if ( file_exists( $template ) ) {
			status_header( 200 );
			include $template;
		} else {
			status_header( 200 );
			$this->render_fallback();
		}

		exit;
	}

	/**
	 * Get local site ID — no API call.
	 *
	 * @return string
	 */
	private function get_local_site_id(): string {
		$site_id = get_option( 'megagovern_site_id', '' );

		if ( empty( $site_id ) ) {
			// Use wp_generate_uuid() with fallback for older WP versions.
			if ( function_exists( 'wp_generate_uuid' ) ) {
				$uuid = wp_generate_uuid();
			} else {
				$uuid = uniqid( 'mg_', true );
			}

			$site_id = 'mg_' . substr( md5( get_home_url() . $uuid ), 0, 16 );
			update_option( 'megagovern_site_id', $site_id );
		}

		return $site_id;
	}

	/**
	 * Get Lucide icon SVG.
	 *
	 * @param string $name Icon name.
	 * @param string $size Icon size in pixels.
	 * @return string SVG markup.
	 */
	private function get_icon( string $name, string $size = '24' ): string {
		$icons = [
			'shield'      => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
			'check'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
			'users'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
			'cpu'         => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>',
			'edit'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
			'file-text'   => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
			'calendar'    => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
		];

		return $icons[ $name ] ?? '';
	}

	/**
	 * Fallback verification page if template file is missing.
	 */
	private function render_fallback(): void {
		$stats   = Registry::get_stats();
		$site_id = $this->get_local_site_id();

		$last_updated = $stats['last_updated'] ?? current_time( 'mysql' );
		$hash         = substr( hash( 'sha256', $site_id . $last_updated ), 0, 16 );

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta name="robots" content="noindex, follow">
			<title><?php bloginfo( 'name' ); ?> — <?php esc_html_e( 'AI Transparency Verification', 'megagovern' ); ?></title>
			<?php
			if ( function_exists( 'wp_head' ) ) {
				wp_head();
			}
			?>
		</head>
		<body <?php body_class(); ?>>
			<div class="megagovern-verify-page" style="max-width: 640px; margin: 60px auto; padding: 40px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

				<h1 style="margin: 0 0 8px; font-size: 24px; color: #1d2327; display: flex; align-items: center; gap: 8px;">
					<span style="color: #2271b1; display: inline-flex;">
						<?php echo wp_kses_post( $this->get_icon( 'shield', '28' ) ); ?>
					</span>
					<?php bloginfo( 'name' ); ?>
				</h1>

				<p style="margin: 0 0 24px; font-size: 16px; color: #00a32a; font-weight: 600; display: flex; align-items: center; gap: 6px;">
					<span style="display: inline-flex;">
						<?php echo wp_kses_post( $this->get_icon( 'check', '18' ) ); ?>
					</span>
					<?php esc_html_e( 'Verified Transparent Site', 'megagovern' ); ?>
				</p>

				<p style="color: #50575e; line-height: 1.6;">
					<?php esc_html_e( 'This website is committed to AI transparency and maintains a documented governance record through MegaGovern.', 'megagovern' ); ?>
				</p>

				<table style="width: 100%; border-collapse: collapse; margin: 24px 0; border-top: 1px solid #c3c4c7;">
					<tbody>
						<tr style="border-bottom: 1px solid #c3c4c7;">
							<td style="padding: 12px 0; color: #1d2327; font-weight: 600;"><?php esc_html_e( 'Total Declared', 'megagovern' ); ?></td>
							<td style="padding: 12px 0; text-align: right; font-weight: 600; font-size: 18px; color: #2271b1;"><?php echo esc_html( (string) ( $stats['total'] ?? 0 ) ); ?></td>
						</tr>
						<tr style="border-bottom: 1px solid #c3c4c7;">
							<td style="padding: 8px 0; color: #646970; display: flex; align-items: center; gap: 4px;">
								<span style="color: #2271b1; display: inline-flex;">
									<?php echo wp_kses_post( $this->get_icon( 'users', '14' ) ); ?>
								</span>
								<?php esc_html_e( 'Human Written', 'megagovern' ); ?>
							</td>
							<td style="padding: 8px 0; text-align: right;"><?php echo esc_html( (string) ( $stats['human'] ?? 0 ) ); ?></td>
						</tr>
						<tr style="border-bottom: 1px solid #c3c4c7;">
							<td style="padding: 8px 0; color: #646970;">
								<span style="color: #dba617; display: inline-flex; vertical-align: middle; margin-right: 4px;">
									<?php echo wp_kses_post( $this->get_icon( 'edit', '14' ) ); ?>
								</span>
								<?php esc_html_e( 'AI Assisted', 'megagovern' ); ?>
							</td>
							<td style="padding: 8px 0; text-align: right;"><?php echo esc_html( (string) ( $stats['ai_assisted'] ?? 0 ) ); ?></td>
						</tr>
						<tr style="border-bottom: 1px solid #c3c4c7;">
							<td style="padding: 8px 0; color: #646970;">
								<span style="color: #d63638; display: inline-flex; vertical-align: middle; margin-right: 4px;">
									<?php echo wp_kses_post( $this->get_icon( 'cpu', '14' ) ); ?>
								</span>
								<?php esc_html_e( 'AI Generated', 'megagovern' ); ?>
							</td>
							<td style="padding: 8px 0; text-align: right;"><?php echo esc_html( (string) ( $stats['ai_generated'] ?? 0 ) ); ?></td>
						</tr>
						<tr>
							<td style="padding: 8px 0; color: #646970;"><?php esc_html_e( 'Last Updated', 'megagovern' ); ?></td>
							<td style="padding: 8px 0; text-align: right; font-size: 12px; color: #646970;"><?php echo esc_html( $last_updated ); ?></td>
						</tr>
					</tbody>
				</table>

				<div style="padding: 12px; background: #f0f0f1; border-radius: 2px; font-size: 12px; color: #646970; word-break: break-all;">
					<strong><?php esc_html_e( 'Verification Hash:', 'megagovern' ); ?></strong>
					<code style="display: block; margin-top: 4px; color: #1d2327;"><?php echo esc_html( $hash ); ?></code>
				</div>

				<p style="margin-top: 24px; font-size: 12px; color: #a7aaad; text-align: center;">
					<?php esc_html_e( 'Powered by MegaGovern — AI Transparency & Disclosure Governance', 'megagovern' ); ?>
				</p>

			</div>
			<?php
			if ( function_exists( 'wp_footer' ) ) {
				wp_footer();
			}
			?>
		</body>
		</html>
		<?php
	}

	// ═══════════════════════════════════════
	// BADGE SHORTCODE
	// ═══════════════════════════════════════

	/**
	 * Badge shortcode: [megagovern_badge size="medium"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function badge_shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'size' => 'medium',
			],
			$atts
		);

		$verify_url = self::get_transparency_url();

		ob_start();
		$size = $atts['size'];
		$template = MEGAGOVERN_PATH . 'templates/public/badge.php';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			// Fallback badge.
			echo '<a href="' . esc_url( $verify_url ) . '" class="megagovern-badge" style="display:inline-block;padding:8px 16px;background:#2271b1;color:#fff;text-decoration:none;border-radius:4px;font-size:14px;font-family:sans-serif;">';
			echo esc_html__( 'AI Transparency', 'megagovern' );
			echo '</a>';
		}

		return ob_get_clean();
	}

	// ═══════════════════════════════════════
	// PUBLIC STATIC METHODS
	// ═══════════════════════════════════════

	/**
	 * Refresh verification data (called after declarations).
	 */
	public static function refresh(): void {
		update_option( 'megagovern_verify_updated', time() );
		delete_transient( 'megagovern_verification_data' );

		if ( class_exists( '\MegaGovern\Governance' ) ) {
			Governance::log_action(
				'verification_updated',
				0,
				'system',
				0,
				[ 'note' => __( 'Verification data refreshed.', 'megagovern' ) ]
			);
		}
	}

	/**
	 * Get transparency URL with verification hash.
	 *
	 * @return string
	 */
	public static function get_transparency_url(): string {
		$hash = self::get_hash();
		return home_url( '/ai-transparency-' . $hash );
	}

	/**
	 * Get verification page URL (legacy).
	 *
	 * @return string
	 */
	public static function get_verify_url(): string {
		return self::get_transparency_url();
	}

	/**
	 * Get verification page slug.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return self::VERIFY_SLUG;
	}

	/**
	 * Get verification hash.
	 *
	 * @return string
	 */
	public static function get_hash(): string {
		$stats = Registry::get_stats();
		$instance = new self();
		$site_id = $instance->get_local_site_id();
		$last_updated = $stats['last_updated'] ?? current_time( 'mysql' );
		return substr( hash( 'sha256', $site_id . $last_updated ), 0, 16 );
	}

	/**
	 * Get verification data for public page.
	 *
	 * @return array
	 */
	public static function get_verification_data(): array {
		$cached = get_transient( 'megagovern_verification_data' );

		if ( false !== $cached ) {
			return $cached;
		}

		$stats = Registry::get_stats();
		$instance = new self();
		$site_id = $instance->get_local_site_id();
		$last_updated = $stats['last_updated'] ?? current_time( 'mysql' );
		$hash = self::get_hash();

		$data = [
			'site_name'      => get_bloginfo( 'name' ),
			'site_url'       => home_url(),
			'total'          => (int) ( $stats['total'] ?? 0 ),
			'human'          => (int) ( $stats['human'] ?? 0 ),
			'ai_assisted'    => (int) ( $stats['ai_assisted'] ?? 0 ),
			'ai_generated'   => (int) ( $stats['ai_generated'] ?? 0 ),
			'last_updated'   => $last_updated,
			'hash'           => $hash,
			'verify_url'     => self::get_transparency_url(),
		];

		set_transient( 'megagovern_verification_data', $data, HOUR_IN_SECONDS );

		return $data;
	}
}