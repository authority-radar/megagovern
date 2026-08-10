<?php
/**
 * Service: Local Registry - Document — V1.0.4
 *
 * Central data store for all declarations and media governance.
 * - UPDATED: Added 'deepfake' support to type_icons() and get_local_stats()
 * - FULL COMPATIBILITY: Works smoothly with Media & C2PA reports
 * - FIXED: WordPress.org compliance - queries stay inline inside
 *   $wpdb->prepare() (no %i, no WP 6.2 requirement); every genuinely
 *   dynamic value is passed as a prepared argument; table-name and
 *   placeholder-syntax interpolation is explained and suppressed with
 *   scoped phpcs:ignore comments per WordPress.org convention.
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {
	const MAX_LIMIT = 500;
	const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Icon mapping for declaration types.
	 *
	 * @return array
	 */
	public static function type_icons(): array {
		return [
			'human'        => 'lucide-user',
			'ai_assisted'  => 'lucide-pencil',
			'ai_generated' => 'lucide-brain',
			'deepfake'     => 'lucide-scan',
		];
	}

	/**
	 * Get icon for a declaration type.
	 *
	 * @param string $type
	 * @return string
	 */
	public static function get_type_icon( string $type ): string {
		$icons = self::type_icons();
		return $icons[ $type ] ?? 'lucide-file-question';
	}

	/**
	 * Get all declarations with filters.
	 * LOCAL-FIRST — No API calls.
	 *
	 * @param array $filters
	 * @return array
	 */
	public static function get_all( array $filters = [] ): array {
		return self::get_local( $filters );
	}

	/**
	 * Get declaration for a specific post including content hash.
	 *
	 * @param int $post_id
	 * @return array|null
	 */
	public static function get_for_post( int $post_id ): ?array {
		$data = self::get_all( [ 'post_id' => $post_id ] );
		if ( ! empty( $data['declarations'][0] ) ) {
			$declaration = $data['declarations'][0];
			$declaration['content_hash'] = get_post_meta( $post_id, '_megagovern_content_hash', true );
			return $declaration;
		}
		return null;
	}

	/**
	 * Get declaration with content hash for a post.
	 *
	 * @param int $post_id
	 * @return array|null
	 */
	public static function get_declaration_with_hash( int $post_id ): ?array {
		$declaration = self::get_for_post( $post_id );
		if ( $declaration ) {
			$declaration['content_hash'] = get_post_meta( $post_id, '_megagovern_content_hash', true );
		}
		return $declaration;
	}

	/**
	 * Check if the registry table exists.
	 *
	 * @return bool
	 */
	private static function table_exists(): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'mega_declaration_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		return $result === $table;
	}

	/**
	 * Get statistics.
	 *
	 * @return array
	 */
	public static function get_stats(): array {
		$cache_key = 'megagovern_registry_stats';
		$stats     = get_transient( $cache_key );

		if ( false !== $stats ) {
			return $stats;
		}

		if ( ! self::table_exists() ) {
			$empty_stats = [
				'total'        => 0,
				'human'        => 0,
				'ai_assisted'  => 0,
				'ai_generated' => 0,
				'deepfake'     => 0,
				'last_updated' => '',
			];
			set_transient( $cache_key, $empty_stats, self::CACHE_TTL );
			return $empty_stats;
		}

		$stats = self::get_local_stats();
		set_transient( $cache_key, $stats, self::CACHE_TTL );

		return $stats;
	}

	/**
	 * Get history for a post.
	 *
	 * @param int $post_id
	 * @return array
	 */
	public static function get_history( int $post_id ): array {
		global $wpdb;

		if ( ! class_exists( '\MegaGovern\Db' ) || ! method_exists( '\MegaGovern\Db', 'log_table_exists' ) ) {
			return [];
		}
		if ( ! Db::log_table_exists() ) {
			return [];
		}

		$table = $wpdb->prefix . 'mega_declaration_log';

		// $table is built only from $wpdb->prefix (WordPress core) plus a
		// hardcoded suffix — never from user input. $post_id is the only
		// real variable value and it goes through %d via $wpdb->prepare().
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE post_id = %d ORDER BY logged_at DESC LIMIT 100",
				$post_id
			),
			ARRAY_A
		);

		return $results ?: [];
	}

	/**
	 * Get recent declarations.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function get_recent( int $limit = 10 ): array {
		$data = self::get_all( [ 'limit' => $limit, 'order' => 'desc' ] );
		return $data['declarations'] ?? [];
	}

	/**
	 * Count total declared items.
	 *
	 * @return int
	 */
	public static function count_total(): int {
		$stats = self::get_stats();
		return (int) ( $stats['total'] ?? 0 );
	}

	/**
	 * Count by declaration type.
	 *
	 * @param string $type
	 * @return int
	 */
	public static function count_by_type( string $type ): int {
		$stats = self::get_stats();
		return (int) ( $stats[ $type ] ?? 0 );
	}

	/**
	 * Build a dynamic IN clause with placeholders and merged args.
	 *
	 * @param array $types       Array of post types.
	 * @param array $extra_args  Additional prepared args to merge.
	 * @return array{0: string, 1: array} SQL placeholder string and merged args.
	 */
	private static function build_in_clause( array $types, array $extra_args = [] ): array {
		$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$type_args    = array_map( 'strval', $types );
		return [ $placeholders, array_merge( $type_args, $extra_args ) ];
	}

	/**
	 * Count undeclared content.
	 *
	 * @return int
	 */
	public static function count_undeclared(): int {
		global $wpdb;

		$cache_key = 'megagovern_undeclared_count';
		$count     = get_transient( $cache_key );

		if ( false !== $count ) {
			return (int) $count;
		}

		if ( ! self::table_exists() ) {
			return 0;
		}

		$enabled_types = self::get_enabled_types();
		if ( empty( $enabled_types ) ) {
			$enabled_types = [ 'post', 'page' ];
		}

		list( $placeholders, $all_args ) = self::build_in_clause( $enabled_types );

		// $placeholders is a locally generated "%s,%s,..." string containing
		// only placeholder syntax (never data), sized to match
		// $enabled_types — the standard WordPress Core dynamic IN() clause
		// pattern. $wpdb->posts / $wpdb->postmeta are WordPress-core table
		// name properties, not user input. Every real value ($enabled_types)
		// is passed through $wpdb->prepare() via $all_args.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				 FROM {$wpdb->posts} p
				 WHERE p.post_type IN ({$placeholders})
				 AND p.post_status IN ('publish', 'draft', 'pending', 'future', 'private')
				 AND p.ID NOT IN (
					 SELECT DISTINCT pm.post_id
					 FROM {$wpdb->postmeta} pm
					 WHERE pm.meta_key = '_megagovern_declaration'
					 AND pm.meta_value != ''
				 )",
				...$all_args
			)
		);

		set_transient( $cache_key, $count, 12 * HOUR_IN_SECONDS );

		return (int) $count;
	}

	/**
	 * Count total content (all post types).
	 *
	 * @return int
	 */
	public static function count_total_content(): int {
		global $wpdb;

		$enabled_types = self::get_enabled_types();
		if ( empty( $enabled_types ) ) {
			$enabled_types = [ 'post', 'page' ];
		}

		list( $placeholders, $all_args ) = self::build_in_clause( $enabled_types );

		// See count_undeclared() above for why $placeholders / $wpdb->posts
		// are safe here.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				 WHERE post_type IN ({$placeholders})
				 AND post_status IN ('publish', 'draft', 'pending', 'future', 'private')",
				...$all_args
			)
		);
	}

	/**
	 * Get declared post IDs.
	 *
	 * @return array
	 */
	public static function get_declared_post_ids(): array {
		global $wpdb;

		$cache_key = 'megagovern_declared_ids';
		$ids       = get_transient( $cache_key );

		if ( false !== $ids ) {
			return (array) $ids;
		}

		if ( ! self::table_exists() ) {
			return [];
		}

		$enabled_types = self::get_enabled_types();
		if ( empty( $enabled_types ) ) {
			$enabled_types = [ 'post', 'page' ];
		}

		list( $placeholders, $all_args ) = self::build_in_clause( $enabled_types );

		// See count_undeclared() above for why $placeholders / $wpdb->posts /
		// $wpdb->postmeta are safe here.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.post_type IN ({$placeholders})
				 AND p.post_status IN ('publish', 'draft', 'pending', 'future', 'private')
				 AND pm.meta_key = '_megagovern_declaration'
				 AND pm.meta_value != ''",
				...$all_args
			)
		);

		$ids = array_map( 'intval', (array) $ids );
		set_transient( $cache_key, $ids, 12 * HOUR_IN_SECONDS );

		return $ids;
	}

	/**
	 * Run a scan of all content.
	 *
	 * @return array
	 */
	public static function run_scan(): array {
		$stats         = self::get_local_stats();
		$undeclared    = self::count_undeclared();
		$total_content = self::count_total_content();

		$result = [
			'total_content' => $total_content,
			'declared'      => $stats['total'],
			'undeclared'    => $undeclared,
			'human'         => $stats['human'],
			'ai_assisted'   => $stats['ai_assisted'],
			'ai_generated'  => $stats['ai_generated'],
			'deepfake'      => $stats['deepfake'],
			'scanned_at'    => gmdate( 'Y-m-d H:i:s' ),
		];

		update_option( 'megagovern_last_scan', $result );
		self::clear_cache();

		return $result;
	}

	/**
	 * Get last scan results.
	 *
	 * @return array|null
	 */
	public static function get_last_scan(): ?array {
		$scan = get_option( 'megagovern_last_scan', null );
		return is_array( $scan ) ? $scan : null;
	}

	/**
	 * Format declaration type.
	 *
	 * @param string|int $type
	 * @return string
	 */
	public static function format_type( $type ): string {
		return Helpers::format_type( $type );
	}

	/**
	 * Clear all caches.
	 */
	public static function clear_cache(): void {
		delete_transient( 'megagovern_registry_stats' );
		delete_transient( 'megagovern_undeclared_count' );
		delete_transient( 'megagovern_declared_ids' );
	}

	// ═══════════════════════════════════════
	// PRIVATE HELPERS
	// ═══════════════════════════════════════

	/**
	 * Get enabled post types based on license.
	 * Free: post + page only.
	 * Pro/Agency: all saved types.
	 *
	 * @return array
	 */
	private static function get_enabled_types(): array {
		$saved_types = get_option( 'megagovern_declaration_post_types', [ 'post', 'page' ] );

		if ( empty( $saved_types ) ) {
			return [];
		}

		if ( class_exists( '\MegaGovern\License' ) && License::is_free() ) {
			return array_values( array_intersect( $saved_types, [ 'post', 'page' ] ) ) ?: [ 'post', 'page' ];
		}

		return $saved_types;
	}

	/**
	 * Get local statistics.
	 *
	 * @return array
	 */
	private static function get_local_stats(): array {
		global $wpdb;

		$stats = [
			'total'        => 0,
			'human'        => 0,
			'ai_assisted'  => 0,
			'ai_generated' => 0,
			'deepfake'     => 0,
			'last_updated' => '',
		];

		if ( ! self::table_exists() ) {
			return $stats;
		}

		$enabled_types = self::get_enabled_types();
		if ( empty( $enabled_types ) ) {
			$enabled_types = [ 'post', 'page' ];
		}

		list( $placeholders, $all_args ) = self::build_in_clause( $enabled_types );

		// See count_undeclared() above for why $placeholders / $wpdb->postmeta
		// / $wpdb->posts are safe here.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value, COUNT(DISTINCT p.ID) AS cnt
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				 WHERE pm.meta_key = '_megagovern_declaration'
				 AND pm.meta_value != ''
				 AND p.post_type IN ({$placeholders})
				 AND p.post_status IN ('publish', 'draft', 'pending', 'future', 'private')
				 GROUP BY pm.meta_value",
				...$all_args
			)
		);

		foreach ( (array) $rows as $row ) {
			$type = sanitize_key( $row->meta_value );
			if ( in_array( $type, [ 'human', 'ai_assisted', 'ai_generated', 'deepfake' ], true ) ) {
				$stats[ $type ] = (int) $row->cnt;
				$stats['total'] += (int) $row->cnt;
			}
		}

		// Get last updated timestamp.
		$last_scan = get_option( 'megagovern_last_scan', null );
		if ( is_array( $last_scan ) && ! empty( $last_scan['scanned_at'] ) ) {
			$stats['last_updated'] = $last_scan['scanned_at'];
		} else {
			// No user-supplied values in this query at all, so no
			// $wpdb->prepare() call is needed or appropriate here.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$last = $wpdb->get_var(
				"SELECT meta_value FROM {$wpdb->postmeta}
				 WHERE meta_key = '_megagovern_declared_at'
				 ORDER BY meta_id DESC LIMIT 1"
			);
			if ( $last ) {
				$stats['last_updated'] = $last;
			}
		}

		return $stats;
	}

	/**
	 * Get local declarations with filters.
	 *
	 * @param array $filters
	 * @return array
	 */
	private static function get_local( array $filters ): array {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return [
				'declarations' => [],
				'total'        => 0,
			];
		}

		$limit  = isset( $filters['limit'] ) ? min( (int) $filters['limit'], self::MAX_LIMIT ) : 50;
		$offset = isset( $filters['offset'] ) ? (int) $filters['offset'] : 0;

		// Count total matching without limit.
		if ( ! empty( $filters['post_id'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT pm.post_id)
					 FROM {$wpdb->postmeta} pm
					 WHERE pm.meta_key = '_megagovern_declaration' AND pm.post_id = %d",
					(int) $filters['post_id']
				)
			);
		} else {
			// No user-supplied values, so no prepare() needed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total_count = (int) $wpdb->get_var(
				"SELECT COUNT(DISTINCT pm.post_id)
				 FROM {$wpdb->postmeta} pm
				 WHERE pm.meta_key = '_megagovern_declaration'"
			);
		}

		// Fetch paginated rows.
		if ( ! empty( $filters['post_id'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT pm.post_id, pm.meta_value AS declaration_type,
							COALESCE(by_u.meta_value, '') AS declared_by,
							COALESCE(at_t.meta_value, '') AS declared_at,
							COALESCE(h.meta_value, '') AS content_hash
					 FROM {$wpdb->postmeta} pm
					 LEFT JOIN {$wpdb->postmeta} by_u ON pm.post_id = by_u.post_id AND by_u.meta_key = '_megagovern_declared_by'
					 LEFT JOIN {$wpdb->postmeta} at_t ON pm.post_id = at_t.post_id AND at_t.meta_key = '_megagovern_declared_at'
					 LEFT JOIN {$wpdb->postmeta} h ON pm.post_id = h.post_id AND h.meta_key = '_megagovern_content_hash'
					 WHERE pm.meta_key = '_megagovern_declaration' AND pm.post_id = %d
					 ORDER BY at_t.meta_value DESC LIMIT %d OFFSET %d",
					(int) $filters['post_id'],
					$limit,
					$offset
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT pm.post_id, pm.meta_value AS declaration_type,
							COALESCE(by_u.meta_value, '') AS declared_by,
							COALESCE(at_t.meta_value, '') AS declared_at,
							COALESCE(h.meta_value, '') AS content_hash
					 FROM {$wpdb->postmeta} pm
					 LEFT JOIN {$wpdb->postmeta} by_u ON pm.post_id = by_u.post_id AND by_u.meta_key = '_megagovern_declared_by'
					 LEFT JOIN {$wpdb->postmeta} at_t ON pm.post_id = at_t.post_id AND at_t.meta_key = '_megagovern_declared_at'
					 LEFT JOIN {$wpdb->postmeta} h ON pm.post_id = h.post_id AND h.meta_key = '_megagovern_content_hash'
					 WHERE pm.meta_key = '_megagovern_declaration'
					 ORDER BY at_t.meta_value DESC LIMIT %d OFFSET %d",
					$limit,
					$offset
				),
				ARRAY_A
			);
		}

		return [
			'declarations' => $rows ?: [],
			'total'        => $total_count,
		];
	}
}