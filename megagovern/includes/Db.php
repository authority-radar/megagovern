<?php
/**
 * Database Layer — V1.0.4
 *
 * Consolidated tables, evidence logs, version tracking, and proper upgrades.
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Db {

	const TABLE_VERSION  = '2.1';
	const VERSION_OPTION = 'megagovern_db_version';

	/**
	 * Create custom tables with version tracking.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$installed_version = get_option( self::VERSION_OPTION, '0' );
		$charset_collate   = $wpdb->get_charset_collate();

		// ── 1. Declarations cache table ──
		$table_declarations = $wpdb->prefix . 'mega_declarations';
		$sql_declarations   = "CREATE TABLE $table_declarations (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT(20) UNSIGNED NOT NULL UNIQUE,
			declaration_type ENUM('human', 'ai_assisted', 'ai_generated', 'legacy') NOT NULL DEFAULT 'human',
			declared_by BIGINT(20) UNSIGNED NOT NULL,
			declared_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			content_hash VARCHAR(64) DEFAULT NULL,
			is_stale TINYINT(1) DEFAULT 0,
			INDEX post_id_idx (post_id),
			INDEX declaration_type_idx (declaration_type)
		) $charset_collate;";

		// ── 2. Declaration log (audit trail) ──
		$table_log = $wpdb->prefix . 'mega_declaration_log';
		$sql_log   = "CREATE TABLE $table_log (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			declaration_type ENUM('human', 'ai_assisted', 'ai_generated', 'legacy') NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			action VARCHAR(50) NOT NULL COMMENT 'classified, reclassified, documented, aitxt_published, label_displayed, report_generated, verification_updated, alert_received, issue_fixed, services_updated',
			previous_type VARCHAR(20) DEFAULT NULL,
			note VARCHAR(255) DEFAULT NULL,
			logged_at DATETIME NOT NULL,
			INDEX post_id_idx (post_id),
			INDEX user_id_idx (user_id),
			INDEX action_idx (action),
			INDEX logged_at_idx (logged_at)
		) $charset_collate;";

		// ── 3. Evidence logs table (Task 6 - Audit & Legal Compliance) ──
		$table_evidence = $wpdb->prefix . 'megagovern_evidence_logs';
		$sql_evidence   = "CREATE TABLE $table_evidence (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			declaration_type ENUM('human', 'ai_assisted', 'ai_generated', 'legacy') NOT NULL,
			content_hash VARCHAR(64) NOT NULL,
			revision_id BIGINT(20) UNSIGNED DEFAULT 0,
			previous_declaration VARCHAR(20) DEFAULT NULL,
			edit_percentage FLOAT DEFAULT 0,
			is_stale TINYINT(1) DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX post_id_idx (post_id),
			INDEX declaration_type_idx (declaration_type),
			INDEX created_at_idx (created_at),
			INDEX is_stale_idx (is_stale)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_declarations );
		dbDelta( $sql_log );
		dbDelta( $sql_evidence );

		// ── 4. Handle upgrades ──
		if ( version_compare( $installed_version, self::TABLE_VERSION, '<' ) ) {
			self::run_upgrades( $installed_version );
		}

		// ── 5. Update version ──
		update_option( self::VERSION_OPTION, self::TABLE_VERSION );

		// ── 6. Clean up legacy history table ──
		self::drop_legacy_history_table();
	}

	/**
	 * Run version-specific upgrades.
	 *
	 * @param string $from_version Previous version.
	 */
	private static function run_upgrades( string $from_version ): void {
		if ( version_compare( $from_version, '2.1', '<' ) ) {
			self::migrate_legacy_history();
		}
	}

	/**
	 * Drop legacy history table (duplicate/deprecated).
	 */
	private static function drop_legacy_history_table(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'megagovern_history';
		if ( self::table_exists( $table ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}
	}

	/**
	 * Drop all tables — only for plugin uninstall.
	 */
	public static function drop_tables(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mega_declarations" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mega_declaration_log" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}megagovern_evidence_logs" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}megagovern_history" );

		delete_option( self::VERSION_OPTION );
	}

	/**
	 * Check if declarations table exists.
	 *
	 * @return bool
	 */
	public static function declarations_table_exists(): bool {
		global $wpdb;
		return self::table_exists( $wpdb->prefix . 'mega_declarations' );
	}

	/**
	 * Check if log table exists.
	 *
	 * @return bool
	 */
	public static function log_table_exists(): bool {
		global $wpdb;
		return self::table_exists( $wpdb->prefix . 'mega_declaration_log' );
	}

	/**
	 * Check if evidence logs table exists.
	 *
	 * @return bool
	 */
	public static function evidence_table_exists(): bool {
		global $wpdb;
		return self::table_exists( $wpdb->prefix . 'megagovern_evidence_logs' );
	}

	/**
	 * Check if a specific table exists.
	 *
	 * @param string $table_name Full table name.
	 * @return bool
	 */
	private static function table_exists( string $table_name ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		) === $table_name;
	}

	/**
	 * Get total rows in evidence log table.
	 *
	 * @param int $post_id Optional filter by post ID.
	 * @return int
	 */
	public static function get_history_count( int $post_id = 0 ): int {
		global $wpdb;

		if ( ! self::evidence_table_exists() ) {
			return 0;
		}

		if ( $post_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}megagovern_evidence_logs WHERE post_id = %d",
					$post_id
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}megagovern_evidence_logs"
		);
	}

	/**
	 * Clean old log records based on retention days (Task 7 - Tiered Retention Cleanup).
	 *
	 * @param int $days Number of days to keep (e.g., 90 for Free, 365 for Pro, 0 for Unlimited).
	 * @return int Number of records deleted.
	 */
	public static function clean_history( int $days = 365 ): int {
		global $wpdb;

		if ( $days <= 0 || ! self::evidence_table_exists() ) {
			return 0;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted_evidence = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}megagovern_evidence_logs WHERE created_at < %s",
				$cutoff
			)
		);

		if ( self::log_table_exists() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}mega_declaration_log WHERE logged_at < %s",
					$cutoff
				)
			);
		}

		return (int) $deleted_evidence;
	}

	/**
	 * Migrate legacy history entries to evidence log table.
	 *
	 * @return int Number of entries migrated.
	 */
	public static function migrate_legacy_history(): int {
		global $wpdb;

		$history_table  = $wpdb->prefix . 'megagovern_history';
		$evidence_table = $wpdb->prefix . 'megagovern_evidence_logs';

		if ( ! self::table_exists( $history_table ) || ! self::evidence_table_exists() ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results(
			"SELECT post_id, declaration, previous, user_id, created_at 
			 FROM {$wpdb->prefix}megagovern_history 
			 WHERE post_id > 0 
			 ORDER BY created_at ASC"
		);

		if ( empty( $entries ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $entries as $entry ) {
			$type = self::map_declaration_to_string( $entry->declaration );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}megagovern_evidence_logs 
					 WHERE post_id = %d AND created_at = %s",
					$entry->post_id,
					$entry->created_at
				)
			);

			if ( $exists ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert(
				$evidence_table,
				array(
					'post_id'              => $entry->post_id,
					'user_id'              => $entry->user_id,
					'declaration_type'     => $type,
					'content_hash'         => '',
					'revision_id'          => 0,
					'previous_declaration' => self::map_declaration_to_string( $entry->previous ),
					'edit_percentage'      => 0,
					'is_stale'             => 0,
					'created_at'           => $entry->created_at,
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%f', '%d', '%s' )
			);

			if ( $result ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Map declaration value to unified ENUM string state.
	 *
	 * @param mixed $type Declaration representation.
	 * @return string
	 */
	public static function map_declaration_to_string( $type ): string {
		if ( is_numeric( $type ) ) {
			$int_map = array(
				0 => 'human',
				1 => 'ai_assisted',
				2 => 'ai_generated',
				3 => 'legacy',
			);
			return $int_map[ (int) $type ] ?? 'human';
		}

		$str_map = array(
			'human'        => 'human',
			'human_made'   => 'human',
			'ai_assisted'  => 'ai_assisted',
			'ai_modified'  => 'ai_assisted',
			'ai_generated' => 'ai_generated',
			'deepfake'     => 'ai_generated',
			'legacy'       => 'legacy',
		);

		return $str_map[ strtolower( (string) $type ) ] ?? 'human';
	}

	/**
	 * Get database version.
	 *
	 * @return string
	 */
	public static function get_version(): string {
		return get_option( self::VERSION_OPTION, '0' );
	}
}