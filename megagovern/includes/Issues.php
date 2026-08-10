<?php
/**
 * Service: Governance Issues & Fixer (Monitor) — V1.0.4
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// PREVENT DUPLICATE CLASS DECLARATION
if ( class_exists( 'MegaGovern\Issues' ) ) {
	return;
}

class Issues {

	/**
	 * Issue icon mapping.
	 *
	 * @return array
	 */
	public static function issue_icons(): array {
		return [
			'undeclared_posts'    => 'lucide-file-question',
			'undeclared_media'    => 'lucide-image',
			'aitxt_stale'         => 'lucide-file-text',
			'verify_stale'        => 'lucide-shield-off',
			'unread_alerts'       => 'lucide-bell',
			'no_activity'         => 'lucide-clock',
			'score_drop'          => 'lucide-trending-down',
			'word_limit'          => 'lucide-gauge',
			'health_check_failed' => 'lucide-server-off',
		];
	}

	/**
	 * Get icon for an issue.
	 *
	 * @param string $issue_id
	 * @return string
	 */
	public static function get_issue_icon( string $issue_id ): string {
		$icons = self::issue_icons();
		return $icons[ $issue_id ] ?? 'lucide-alert-circle';
	}

	/**
	 * Check all governance issues.
	 *
	 * @return array
	 */
	public static function check_all(): array {
		$issues = [];
		$cache_key = 'megagovern_issues_' . md5( wp_hash( 'megagovern_issues' ) );
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$issues = array_merge( $issues, self::check_undeclared_media() );

		// Undeclared content.
		$undeclared_total = Registry::count_undeclared();

		if ( $undeclared_total > 0 ) {
			$undeclared_posts = self::count_undeclared_by_type( 'post' );
			$undeclared_pages = self::count_undeclared_by_type( 'page' );

			$parts = [];

			if ( $undeclared_posts > 0 ) {
				$parts[] = sprintf(
					/* translators: %d: number of posts */
					_n( '%d post', '%d posts', $undeclared_posts, 'megagovern' ),
					$undeclared_posts
				);
			}
			if ( $undeclared_pages > 0 ) {
				$parts[] = sprintf(
					/* translators: %d: number of pages */
					_n( '%d page', '%d pages', $undeclared_pages, 'megagovern' ),
					$undeclared_pages
				);
			}

			if ( empty( $parts ) ) {
				$parts[] = sprintf(
					/* translators: %d: number of items */
					_n( '%d item', '%d items', $undeclared_total, 'megagovern' ),
					$undeclared_total
				);
			}

			$issues[] = [
				'id'       => 'undeclared_posts',
				'severity' => 'high',
				'icon'     => self::get_issue_icon( 'undeclared_posts' ),
				'title'    => sprintf(
					/* translators: %s: list of post types with undeclared content (e.g., "3 posts, 2 pages") */
					__( '%s without declaration', 'megagovern' ),
					implode( ', ', $parts )
				),
				'problem'  => __( 'Published content has no source classification.', 'megagovern' ),
				'fix_type' => 'guided',
				'action'   => __( 'Declare Now', 'megagovern' ),
				'link'     => admin_url( 'admin.php?page=mega-govern-governance&tab=content&declaration_filter=undeclared' ),
			];
		}

		// AI.txt stale.
		$last_aitxt     = get_option( 'megagovern_aitxt_updated', 0 );
		$declared_count = Registry::count_total();
		if ( $declared_count > 0 && ( time() - $last_aitxt > self::get_aitxt_refresh_interval() ) ) {
			$issues[] = [
				'id'       => 'aitxt_stale',
				'severity' => 'medium',
				'icon'     => self::get_issue_icon( 'aitxt_stale' ),
				'title'    => __( 'AI.txt not updated recently', 'megagovern' ),
				'problem'  => __( 'Your AI.txt file may be stale.', 'megagovern' ),
				'fix_type' => 'auto',
				'action'   => __( 'Regenerate Now', 'megagovern' ),
				'link'     => admin_url( 'admin.php?page=mega-govern-governance&tab=transparency' ),
			];
		}

		// Verification page stale.
		$last_verify = get_option( 'megagovern_verify_updated', 0 );
		if ( $declared_count > 0 && ( time() - $last_verify > self::get_verify_refresh_interval() ) ) {
			$issues[] = [
				'id'       => 'verify_stale',
				'severity' => 'medium',
				'icon'     => self::get_issue_icon( 'verify_stale' ),
				'title'    => __( 'Verification page may be outdated', 'megagovern' ),
				'problem'  => __( 'Your public verification page may not reflect the latest data.', 'megagovern' ),
				'fix_type' => 'auto',
				'action'   => __( 'Refresh Now', 'megagovern' ),
				'link'     => admin_url( 'admin.php?page=mega-govern-governance&tab=transparency' ),
			];
		}

		// Unread alerts.
		if ( class_exists( '\MegaGovern\Alerts' ) ) {
			$unread = Alerts::count_unread();
			if ( $unread > 0 ) {
				$issues[] = [
					'id'       => 'unread_alerts',
					'severity' => 'low',
					'icon'     => self::get_issue_icon( 'unread_alerts' ),
					'title'    => sprintf(
						/* translators: %d: number of unread alerts */
						_n( '%d unread regulatory alert', '%d unread regulatory alerts', $unread, 'megagovern' ),
						$unread
					),
					'problem'  => __( 'You have regulatory updates to review.', 'megagovern' ),
					'fix_type' => 'guided',
					'action'   => __( 'View Alerts', 'megagovern' ),
					'link'     => admin_url( 'admin.php?page=mega-govern-governance&tab=compliance' ),
				];
			}
		}

		// No recent activity.
		$recent = Governance::get_recent( 1 );
		if ( empty( $recent ) || ( time() - strtotime( $recent[0]['logged_at'] ) > self::get_activity_threshold() ) ) {
			$issues[] = [
				'id'       => 'no_activity',
				'severity' => 'low',
				'icon'     => self::get_issue_icon( 'no_activity' ),
				'title'    => __( 'No recent transparency activity', 'megagovern' ),
				'problem'  => __( 'Your site hasn\'t declared any content sources in over 30 days.', 'megagovern' ),
				'fix_type' => 'manual',
				'action'   => __( 'Declare Content', 'megagovern' ),
				'link'     => admin_url( 'admin.php?page=mega-govern-governance&tab=content' ),
			];
		}

		// Apply filters and cache.
		$issues = apply_filters( 'megagovern_issues', $issues );
		set_transient( $cache_key, $issues, HOUR_IN_SECONDS );

		return $issues;
	}

	/**
	 * Check for undeclared media attachments.
	 *
	 * @return array
	 */
	public static function check_undeclared_media(): array {
		$issues = [];
		$cache_key = 'megagovern_undeclared_media_count';
		$undeclared = get_transient( $cache_key );

		if ( false === $undeclared ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$undeclared = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) 
					 FROM {$wpdb->posts} p
					 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
					 WHERE p.post_type = 'attachment' 
					 AND p.post_mime_type LIKE %s
					 AND pm.meta_id IS NULL",
					'_megagovern_declaration',
					'image/%'
				)
			);

			set_transient( $cache_key, $undeclared, 12 * HOUR_IN_SECONDS );
		}

		if ( $undeclared > 0 ) {
			$issues[] = [
				'id'          => 'undeclared_media',
				'icon'        => self::get_issue_icon( 'undeclared_media' ),
				'title'       => sprintf(
					/* translators: %d: number of undeclared media files */
					__( '%d media files lack AI declaration', 'megagovern' ),
					$undeclared
				),
				'severity'    => 'medium',
				'category'    => 'declaration',
				'status'      => 'open',
				'action'      => __( 'Review Media', 'megagovern' ),
				'link'        => admin_url( 'upload.php' ),
				'fix_type'    => 'manual',
				'detected_at' => current_time( 'mysql' ),
			];
		}

		return $issues;
	}

	/**
	 * Get governance score.
	 * Delegates to Score class.
	 *
	 * @return int 0-100
	 */
	public static function get_score(): int {
		return Score::calculate();
	}

	/**
	 * Get score color.
	 * Delegates to Helpers.
	 *
	 * @param int|null $score Score value.
	 * @return string
	 */
	public static function get_score_color( ?int $score = null ): string {
		return Helpers::score_color( $score ?? self::get_score() );
	}

	/**
	 * Get score label.
	 * Delegates to Helpers.
	 *
	 * @param int|null $score Score value.
	 * @return string
	 */
	public static function get_score_label( ?int $score = null ): string {
		return Helpers::score_label( $score ?? self::get_score() );
	}

	/**
	 * Get AI.txt refresh interval in seconds.
	 *
	 * @return int
	 */
	private static function get_aitxt_refresh_interval(): int {
		return 7 * DAY_IN_SECONDS;
	}

	/**
	 * Get verification refresh interval in seconds.
	 *
	 * @return int
	 */
	private static function get_verify_refresh_interval(): int {
		return 24 * HOUR_IN_SECONDS;
	}

	/**
	 * Get activity threshold in seconds.
	 *
	 * @return int
	 */
	private static function get_activity_threshold(): int {
		return 30 * DAY_IN_SECONDS;
	}

	// ═══════════════════════════════════════
	// PRIVATE HELPERS
	// ═══════════════════════════════════════

	/**
	 * Count undeclared items for a specific post type.
	 *
	 * @param string $post_type Post type.
	 * @return int
	 */
	private static function count_undeclared_by_type( string $post_type ): int {
		$declared_ids = Registry::get_declared_post_ids();

		$counts = wp_count_posts( $post_type );
		if ( ! $counts ) {
			return 0;
		}

		$total  = 0;
		$total += (int) ( $counts->publish ?? 0 );
		$total += (int) ( $counts->draft ?? 0 );
		$total += (int) ( $counts->pending ?? 0 );
		$total += (int) ( $counts->future ?? 0 );
		$total += (int) ( $counts->private ?? 0 );

		$declared_in_type = 0;
		if ( ! empty( $declared_ids ) ) {
			global $wpdb;

			// Build IN clause with proper placeholders.
			$placeholders = implode( ',', array_fill( 0, count( $declared_ids ), '%d' ) );

			// Build query with placeholders.
			$query = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID IN ($placeholders) AND post_type = %s";

			// Build parameters array.
			$args = array_merge( array_map( 'intval', $declared_ids ), [ $post_type ] );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$declared_in_type = (int) $wpdb->get_var(
				$wpdb->prepare( $query, $args ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			);
		}

		return max( 0, $total - $declared_in_type );
	}

	/**
	 * Clear issue cache.
	 */
	public static function clear_cache(): void {
		$cache_key = 'megagovern_issues_' . md5( wp_hash( 'megagovern_issues' ) );
		delete_transient( $cache_key );
		delete_transient( 'megagovern_undeclared_media_count' );
	}

	/**
	 * Get severity icon from Helpers.
	 *
	 * @param string $level high|medium|low
	 * @return string
	 */
	public static function get_severity_icon( string $level ): string {
		$levels = Helpers::severity_levels();
		return $levels[ $level ]['icon'] ?? 'lucide-info';
	}

	/**
	 * Get severity color from Helpers.
	 *
	 * @param string $level high|medium|low
	 * @return string
	 */
	public static function get_severity_color( string $level ): string {
		$levels = Helpers::severity_levels();
		return $levels[ $level ]['color'] ?? '#646970';
	}
}