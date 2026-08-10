<?php
/**
 * Governance Engine (Coordinator) — V1.0.4
 *
 * Central action logging and event coordination.
 * Uses Helpers for type mapping.
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Governance {
	/**
	 * Action types.
	 */
	const ACTION_CLASSIFY      = 'classified';
	const ACTION_RECLASSIFY    = 'reclassified';
	const ACTION_DOCUMENT      = 'documented';
	const ACTION_PUBLISH_AITXT = 'aitxt_published';
	const ACTION_DISCLOSE      = 'label_displayed';
	const ACTION_AUDIT         = 'report_generated';
	const ACTION_VERIFY        = 'verification_updated';
	const ACTION_ALERT         = 'alert_received';
	const ACTION_FIX           = 'issue_fixed';
	const ACTION_SERVICES      = 'services_updated';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'save_post', [ $this, 'on_post_save' ], 20, 3 );
		add_action( 'megagovern_declaration_made', [ $this, 'on_declaration' ], 10, 3 );
		add_action( 'megagovern_history_log', [ $this, 'log_history' ], 10, 4 );
		add_action( 'megagovern_declaration_changed', [ $this, 'on_declaration_changed' ], 10, 4 );
	}

	// ═══════════════════════════════════════
	// TABLE EXISTENCE HELPER
	// ═══════════════════════════════════════

	/**
	 * Check if a table exists.
	 *
	 * @param string $table Full table name with prefix.
	 * @return bool
	 */
	private static function table_exists( string $table ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		) === $table;
	}

	// ═══════════════════════════════════════
	// ACTION LOGGING (Legacy - uses old table)
	// ═══════════════════════════════════════

	/**
	 * Log a governance action.
	 *
	 * @param string $action   Action key.
	 * @param int    $post_id  Post ID (0 for system actions).
	 * @param string $type     Declaration type.
	 * @param int    $user_id  User ID.
	 * @param array  $metadata Extra data (previous_type, note).
	 * @return bool
	 */
	public static function log_action( string $action, int $post_id, string $type, int $user_id, array $metadata = [] ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'mega_declaration_log';

		if ( ! self::table_exists( $table ) ) {
			self::create_log_table();
			if ( ! self::table_exists( $table ) ) {
				return false;
			}
		}

		$type_int = self::map_type( $type );
		$prev_int = isset( $metadata['previous_type'] ) ? self::map_type( $metadata['previous_type'] ) : null;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$table,
			[
				'post_id'          => $post_id,
				'declaration_type' => $type_int,
				'user_id'          => $user_id,
				'action'           => sanitize_key( $action ),
				'previous_type'    => $prev_int,
				'note'             => isset( $metadata['note'] ) ? sanitize_text_field( $metadata['note'] ) : null,
				'logged_at'        => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%d', '%s', '%d', '%s', '%s' ]
		);

		if ( $result && $post_id > 0 ) {
			self::log_history( $post_id, $type, $user_id, $metadata['previous_type'] ?? '' );
		}

		return (bool) $result;
	}

	/**
	 * Get governance actions with optional filters.
	 *
	 * @param array $filters {
	 *     @type int    $post_id  Filter by post ID.
	 *     @type string $action   Filter by action type.
	 *     @type int    $user_id  Filter by user ID.
	 *     @type int    $limit    Max results (default 50).
	 *     @type int    $offset   Offset for pagination.
	 * }
	 * @return array
	 */
	 public static function get_actions( array $filters = [] ): array {
    global $wpdb;
    $table = $wpdb->prefix . 'mega_declaration_log';

    if ( ! self::table_exists( $table ) ) {
        return [];
    }

    $where  = [];
    $limit  = isset( $filters['limit'] ) ? (int) $filters['limit'] : 50;
    $offset = isset( $filters['offset'] ) ? (int) $filters['offset'] : 0;

    if ( isset( $filters['post_id'] ) ) {
        $where[] = $wpdb->prepare( 'post_id = %d', (int) $filters['post_id'] );
    }
    if ( isset( $filters['action'] ) ) {
        $where[] = $wpdb->prepare( 'action = %s', sanitize_key( $filters['action'] ) );
    }
    if ( isset( $filters['user_id'] ) ) {
        $where[] = $wpdb->prepare( 'user_id = %d', (int) $filters['user_id'] );
    }

    $where_clause = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

    $sql = sprintf(
        "SELECT * FROM %s %s ORDER BY logged_at DESC LIMIT %%d OFFSET %%d",
        esc_sql( $table ),
        esc_sql( $where_clause )
    );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $results = $wpdb->get_results(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->prepare( $sql, $limit, $offset ),
        ARRAY_A
    );

    return $results ?: [];
}
	
	/**
	 * Get recent governance actions.
	 *
	 * @param int $limit Max results.
	 * @return array
	 */
	public static function get_recent( int $limit = 10 ): array {
		return self::get_actions( [ 'limit' => $limit ] );
	}

	// ═══════════════════════════════════════
	// NEW HISTORY LOGGING (Dedicated table)
	// ═══════════════════════════════════════

	/**
	 * Log declaration history to dedicated table.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $type     Declaration type.
	 * @param int    $user_id  User ID.
	 * @param string $previous Previous type.
	 * @return bool
	 */
	public static function log_history( int $post_id, string $type, int $user_id = 0, string $previous = '' ): bool {
		global $wpdb;

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$table = $wpdb->prefix . 'megagovern_history';

		if ( ! self::table_exists( $table ) ) {
			self::create_history_table();
			if ( ! self::table_exists( $table ) ) {
				return false;
			}
		}

		$data = [
			'post_id'     => $post_id,
			'declaration' => $type,
			'previous'    => $previous ?: '',
			'user_id'     => $user_id,
			'created_at'  => current_time( 'mysql' ),
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $table, $data );

		return (bool) $result;
	}

	/**
	 * Get history from dedicated table.
	 *
	 * @param int $post_id Post ID.
	 * @param int $limit   Max results.
	 * @return array
	 */
	public static function get_history( int $post_id, int $limit = 20 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'megagovern_history';

		if ( ! self::table_exists( $table ) ) {
			return [];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}megagovern_history WHERE post_id = %d ORDER BY created_at DESC LIMIT %d",
				$post_id,
				$limit
			),
			ARRAY_A
		);

		return $results ?: [];
	}

	/**
	 * Get ALL history from dedicated table (for debugging).
	 *
	 * @param int $limit Max results.
	 * @return array
	 */
	public static function get_all_history( int $limit = 100 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'megagovern_history';

		if ( ! self::table_exists( $table ) ) {
			return [];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}megagovern_history ORDER BY created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return $results ?: [];
	}

	/**
	 * Count history entries for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function count_history( int $post_id ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'megagovern_history';

		if ( ! self::table_exists( $table ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}megagovern_history WHERE post_id = %d",
				$post_id
			)
		);
	}

	/**
	 * Create history table.
	 */
	private static function create_history_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'megagovern_history';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			declaration varchar(50) NOT NULL,
			previous varchar(50) DEFAULT '',
			user_id bigint(20) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY declaration (declaration),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create legacy log table.
	 */
	private static function create_log_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'mega_declaration_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			declaration_type int(2) NOT NULL,
			user_id bigint(20) DEFAULT 0,
			action varchar(50) NOT NULL,
			previous_type int(2) DEFAULT NULL,
			note text DEFAULT NULL,
			logged_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY action (action),
			KEY logged_at (logged_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// ═══════════════════════════════════════
	// EVENT HANDLERS
	// ═══════════════════════════════════════

	/**
	 * Handle post save — trigger declaration event.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether updating.
	 */
	public function on_post_save( int $post_id, \WP_Post $post, bool $update ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( 'revision' === $post->post_type ) {
			return;
		}

		$declaration = get_post_meta( $post_id, '_megagovern_declaration', true );
		if ( empty( $declaration ) ) {
			return;
		}

		do_action( 'megagovern_declaration_made', $post_id, $declaration, get_current_user_id() );
	}

	/**
	 * Handle declaration event — refresh dependent services.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    Declaration type.
	 * @param int    $user_id User ID.
	 */
	public function on_declaration( int $post_id, string $type, int $user_id ): void {
		if ( get_option( 'megagovern_auto_aitxt', true ) && class_exists( '\MegaGovern\Crawler' ) ) {
			$crawler = new Crawler();
			$crawler->regenerate();
		}

		if ( get_option( 'megagovern_auto_verify', true ) && class_exists( '\MegaGovern\Verification' ) ) {
			Verification::refresh();
		}

		self::log_action(
			self::ACTION_SERVICES,
			$post_id,
			$type,
			$user_id,
			[
				'note' => __( 'Dependent services refreshed.', 'megagovern' ),
			]
		);
	}

	/**
	 * Handle declaration changed event — log to history.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $new_type New declaration type.
	 * @param string $old_type Old declaration type.
	 * @param int    $user_id  User ID.
	 */
	public function on_declaration_changed( int $post_id, string $new_type, string $old_type, int $user_id ): void {
		self::log_history( $post_id, $new_type, $user_id, $old_type );
	}

	// ═══════════════════════════════════════
	// PRIVATE HELPERS
	// ═══════════════════════════════════════

	/**
	 * Map string declaration type to integer for storage.
	 *
	 * @param string $type Declaration type.
	 * @return int
	 */
	private static function map_type( string $type ): int {
		$map = [
			'human'        => 0,
			'ai_assisted'  => 1,
			'ai_generated' => 2,
			'deepfake'     => 3,
		];
		return $map[ $type ] ?? 0;
	}

	/**
	 * Map integer declaration type back to string.
	 *
	 * @param int $type_int Integer type.
	 * @return string
	 */
	public static function unmap_type( int $type_int ): string {
		$map = [
			0 => 'human',
			1 => 'ai_assisted',
			2 => 'ai_generated',
			3 => 'deepfake',
		];
		return $map[ $type_int ] ?? 'human';
	}

	/**
	 * Get declaration label from type.
	 *
	 * @param string $type Declaration type.
	 * @return string
	 */
	public static function get_type_label( string $type ): string {
		if ( class_exists( '\MegaGovern\Helpers' ) ) {
			return Helpers::declaration_label( $type );
		}

		$labels = [
			'human'        => __( 'Human Made', 'megagovern' ),
			'ai_assisted'  => __( 'AI Modified', 'megagovern' ),
			'ai_generated' => __( 'Fully AI-Generated', 'megagovern' ),
			'deepfake'     => __( 'Synthetic Media', 'megagovern' ),
		];
		return $labels[ $type ] ?? $type;
	}

	/**
	 * Format type for display (from integer or string).
	 *
	 * @param mixed $type Type value.
	 * @return string
	 */
	public static function format_type( $type ): string {
		if ( is_int( $type ) ) {
			$type = self::unmap_type( $type );
		}
		return self::get_type_label( $type );
	}

	/**
	 * Get all action types.
	 *
	 * @return array
	 */
	public static function get_action_types(): array {
		return [
			self::ACTION_CLASSIFY,
			self::ACTION_RECLASSIFY,
			self::ACTION_DOCUMENT,
			self::ACTION_PUBLISH_AITXT,
			self::ACTION_DISCLOSE,
			self::ACTION_AUDIT,
			self::ACTION_VERIFY,
			self::ACTION_ALERT,
			self::ACTION_FIX,
			self::ACTION_SERVICES,
		];
	}

	/**
	 * Count total declaration actions.
	 *
	 * @param int $post_id Optional filter by post ID.
	 * @return int
	 */
	public static function count_actions( int $post_id = 0 ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'mega_declaration_log';

		if ( ! self::table_exists( $table ) ) {
			return 0;
		}

		if ( $post_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}mega_declaration_log WHERE post_id = %d",
					$post_id
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mega_declaration_log"
		);
	}
}