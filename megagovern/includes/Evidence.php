<?php
/**
 * Evidence & Retention Engine — V1.0.4
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Evidence
 *
 * Handles evidence collection, retention, and bundle generation.
 */
class Evidence {

	/**
	 * Constructor — Hook cron, AJAX, declaration events, and media uploads.
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'schedule_retention_cron' ] );
		add_action( 'megagovern_daily_retention_cleanup', [ $this, 'enforce_retention_policy' ] );
		add_action( 'wp_ajax_megagovern_download_evidence_bundle', [ $this, 'build_and_download_bundle' ] );
		add_action( 'megagovern_declaration_changed', [ $this, 'capture_content_snapshot' ], 10, 4 );
		add_action( 'add_attachment', [ $this, 'capture_media_metadata_on_upload' ] );
	}

	// ═══════════════════════════════════════
	// RETENTION ENGINE
	// ═══════════════════════════════════════

	public function schedule_retention_cron(): void {
		if ( ! wp_next_scheduled( 'megagovern_daily_retention_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'megagovern_daily_retention_cleanup' );
		}
	}

	public function enforce_retention_policy(): void {
		global $wpdb;
		$days_to_keep = self::get_retention_days();
		$this->prune_table( $wpdb->prefix . 'mega_declaration_log', 'logged_at', $days_to_keep );
		$this->prune_table( $wpdb->prefix . 'mega_content_snapshots', 'declared_at', $days_to_keep );
	}

	/**
	 * Delete rows older than the retention window from a given table.
	 *
	 * @param string $table_name  Fully-prefixed table name.
	 * @param string $date_column Column to compare against NOW().
	 * @param int    $days        Retention window in days.
	 */
	private function prune_table( string $table_name, string $date_column, int $days ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		if ( ! $table_exists ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `%s` WHERE `%s` < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$table_name,
				$date_column,
				$days
			)
		);
	}

	public static function get_retention_days(): int {
		$is_pro = false;
		if ( class_exists( '\MegaGovern\License' ) ) {
			$is_pro = License::is_pro() || License::is_agency();
		}
		return $is_pro ? 365 : 90;
	}

	public static function get_local_site_id(): string {
		$site_id = get_option( 'megagovern_local_site_id', '' );

		if ( empty( $site_id ) ) {
			$site_id = 'mg_' . wp_generate_password( 24, false, false );
			update_option( 'megagovern_local_site_id', $site_id, true );
		}

		return $site_id;
	}

	public static function count_logs( int $days ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'mega_declaration_log';

		if ( ! class_exists( '\MegaGovern\Db' ) || ! method_exists( '\MegaGovern\Db', 'log_table_exists' ) ) {
			return 0;
		}

		if ( ! Db::log_table_exists() ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `%s` WHERE logged_at > DATE_SUB(NOW(), INTERVAL %d DAY)",
				$table,
				$days
			)
		);

		return (int) $count;
	}

	private function map_declaration_type( $type ): string {
		$map = [
			0 => 'Human Made',
			1 => 'AI Modified',
			2 => 'Fully AI-Generated',
			3 => 'Synthetic Media',
		];
		return $map[ (int) $type ] ?? 'Unknown Type (' . (int) $type . ')';
	}

	// ═══════════════════════════════════════
	// CONTENT SNAPSHOT CAPTURE
	// ═══════════════════════════════════════

	private function maybe_create_snapshot_table(): void {
		global $wpdb;
		$table   = $wpdb->prefix . 'mega_content_snapshots';
		$charset = $wpdb->get_charset_collate();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE `{$table}` (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			declaration_type TINYINT NOT NULL,
			content_hash VARCHAR(64) NOT NULL,
			content_snapshot LONGTEXT NOT NULL,
			declared_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY declared_at (declared_at)
		) {$charset};";

		dbDelta( $sql );
	}

	public function capture_content_snapshot( int $post_id, string $new_type, string $old_type, int $user_id ): void {
		if ( ! $this->is_pro() ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$this->maybe_create_snapshot_table();

		global $wpdb;
		$table = $wpdb->prefix . 'mega_content_snapshots';

		$declaration_type = Declaration::is_valid( $new_type ) ? $this->type_to_int( $new_type ) : 0;
		$content          = wp_kses_post( $post->post_content );
		$hash             = hash( 'sha256', $content );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `%s` WHERE post_id = %d AND declared_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
				$table,
				$post_id
			)
		);

		if ( $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				[
					'declaration_type' => $declaration_type,
					'content_hash'     => $hash,
					'content_snapshot' => $content,
					'declared_at'      => current_time( 'mysql' ),
				],
				[ 'id' => $exists ],
				[ '%d', '%s', '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table,
				[
					'post_id'          => $post_id,
					'declaration_type' => $declaration_type,
					'content_hash'     => $hash,
					'content_snapshot' => $content,
					'declared_at'      => current_time( 'mysql' ),
				],
				[ '%d', '%d', '%s', '%s', '%s' ]
			);
		}
	}

	private function is_pro(): bool {
		if ( class_exists( '\MegaGovern\License' ) ) {
			return License::is_pro() || License::is_agency();
		}
		return false;
	}

	private function type_to_int( string $type ): int {
		$map = [
			'human'        => 0,
			'ai_assisted'  => 1,
			'ai_generated' => 2,
			'deepfake'     => 3,
		];
		return $map[ $type ] ?? 0;
	}

	private function get_content_snapshots( int $days ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'mega_content_snapshots';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $exists ) {
			return [];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `%s` WHERE declared_at > DATE_SUB(NOW(), INTERVAL %d DAY) ORDER BY declared_at DESC",
				$table,
				$days
			),
			ARRAY_A
		);

		return $results ?: [];
	}

	// ═══════════════════════════════════════
	// MEDIA PROVENANCE CAPTURE
	// ═══════════════════════════════════════

	public function capture_media_metadata_on_upload( int $attachment_id ): void {
		$mime = get_post_mime_type( $attachment_id );
		if ( ! $mime || strpos( $mime, 'image/' ) !== 0 ) {
			return;
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return;
		}

		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		$metadata = [
			'captured_at'         => current_time( 'mysql' ),
			'original_filename'   => basename( $file_path ),
			'file_size_bytes'     => $wp_filesystem->size( $file_path ),
			'mime_type'           => $mime,
			'exif_present'        => false,
			'iptc_digital_source' => null,
			'c2pa_manifest_found' => false,
		];

		if ( function_exists( 'exif_read_data' ) && in_array( $mime, [ 'image/jpeg', 'image/tiff' ], true ) ) {
			$exif = @exif_read_data( $file_path );
			if ( $exif ) {
				$metadata['exif_present']       = true;
				$metadata['exif_software']      = $exif['Software'] ?? null;
				$metadata['exif_description']   = $exif['ImageDescription'] ?? null;
			}
		}

		$iptc_size_info = getimagesize( $file_path, $info );
		if ( $iptc_size_info && ! empty( $info['APP13'] ) ) {
			$iptc = iptcparse( $info['APP13'] );
			if ( $iptc ) {
				$metadata['iptc_digital_source'] = $iptc['2#116'][0] ?? null;
				$metadata['iptc_raw_present']    = true;
			}
		}

		$metadata['c2pa_manifest_found'] = $this->detect_c2pa_presence( $file_path );

		update_post_meta( $attachment_id, '_megagovern_upload_provenance', wp_json_encode( $metadata ) );
	}

	private function detect_c2pa_presence( string $file_path ): bool {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		if ( ! $wp_filesystem->exists( $file_path ) || ! $wp_filesystem->is_readable( $file_path ) ) {
			return false;
		}

		$content = $wp_filesystem->get_contents( $file_path );
		if ( false === $content || empty( $content ) ) {
			return false;
		}

		$chunk = substr( $content, 0, 65536 );

		return ( false !== strpos( $chunk, 'c2pa' ) || false !== strpos( $chunk, 'C2PA' ) );
	}

	private function get_media_provenance_records(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$attachment_ids = $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_megagovern_declaration'"
		);

		$records = [];
		foreach ( $attachment_ids as $attachment_id ) {
			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			$raw = get_post_meta( $attachment_id, '_megagovern_upload_provenance', true );
			if ( ! $raw ) {
				continue;
			}

			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				$records[ $attachment_id ] = $decoded;
			}
		}

		return $records;
	}

	// ═══════════════════════════════════════
	// TRUST & COMPLIANCE REPORT
	// ═══════════════════════════════════════

	private function generate_trust_report(): string {
		$stats      = class_exists( '\MegaGovern\Registry' ) ? Registry::get_stats() : [];
		$undeclared = class_exists( '\MegaGovern\Registry' ) ? Registry::count_undeclared() : 0;
		$total      = (int) ( $stats['total'] ?? 0 ) + $undeclared;
		$human      = (int) ( $stats['human'] ?? 0 );
		$assisted   = (int) ( $stats['ai_assisted'] ?? 0 );
		$generated  = (int) ( $stats['ai_generated'] ?? 0 );
		$deepfake   = (int) ( $stats['deepfake'] ?? 0 );
		$coverage   = $total > 0 ? round( ( (int) ( $stats['total'] ?? 0 ) / $total ) * 100 ) : 0;

		$report  = "═══════════════════════════════════════\n";
		$report .= "  MEGAGOVERN — TRUST & COMPLIANCE REPORT\n";
		$report .= "═══════════════════════════════════════\n\n";

		$report .= "1. GOVERNANCE OVERVIEW\n";
		$report .= "─────────────────────\n";
		$report .= "Total Content:           " . number_format_i18n( $total ) . "\n";
		$report .= "Declared Content:        " . number_format_i18n( (int) ( $stats['total'] ?? 0 ) ) . "\n";
		$report .= "Undeclared Content:      " . number_format_i18n( $undeclared ) . "\n";
		$report .= "Coverage Rate:           " . $coverage . "%\n\n";

		$report .= "2. DECLARATION BREAKDOWN\n";
		$report .= "────────────────────────\n";
		$report .= "Human Made:              " . number_format_i18n( $human ) . "\n";
		$report .= "AI Modified:             " . number_format_i18n( $assisted ) . "\n";
		$report .= "Fully AI-Generated:      " . number_format_i18n( $generated ) . "\n";
		$report .= "Synthetic / Deepfake:    " . number_format_i18n( $deepfake ) . "\n\n";

		$report .= "3. TRANSPARENCY MEASURES\n";
		$report .= "────────────────────────\n";
		$report .= "AI.txt Published:        " . ( file_exists( ABSPATH . 'ai.txt' ) ? 'Yes' : 'No' ) . "\n";
		$report .= "Verification Page:       " . ( get_option( 'megagovern_verify_updated' ) ? 'Active' : 'Not active' ) . "\n";
		$report .= "Content Labels:          " . ( get_option( 'megagovern_label_position' ) ? 'Enabled' : 'Disabled' ) . "\n\n";

		$report .= "4. EVIDENCE RETENTION\n";
		$report .= "──────────────────────\n";
		$report .= "Retention Period:        " . self::get_retention_days() . " days\n";
		$report .= "Audit Log Entries:       " . number_format_i18n( self::count_logs( self::get_retention_days() ) ) . "\n\n";

		$report .= "5. COMPLIANCE GAPS\n";
		$report .= "───────────────────\n";
		$report .= "AI Content Labeling:     Supported\n";
		$report .= "Full C2PA Verification:  Partial (presence-only)\n";
		$report .= "Emotion Recognition:     Not Supported\n\n";

		if ( $undeclared > 0 ) {
			$report .= "6. RECOMMENDATIONS\n";
			$report .= "───────────────────\n";
			/* translators: %d: number of undeclared items */
			$report .= "- Declare " . number_format_i18n( $undeclared ) . " remaining content items\n";
			$report .= "- Review Regulatory Alerts tab for upcoming deadlines\n";
			$report .= "- Ensure AI.txt is accessible at " . home_url( '/ai.txt' ) . "\n\n";
		}

		$report .= "═══════════════════════════════════════\n";
		$report .= "  IMPORTANT LEGAL DISCLAIMER\n";
		$report .= "═══════════════════════════════════════\n";
		$report .= "This report is generated by the MegaGovern plugin for\n";
		$report .= "transparency and documentation purposes only. It does NOT\n";
		$report .= "constitute legal advice, legal compliance certification,\n";
		$report .= "or a guarantee of regulatory compliance. Legal compliance\n";
		$report .= "depends on your jurisdiction, your content, and your legal\n";
		$report .= "counsel. Meganova Agency makes no representations or\n";
		$report .= "warranties regarding the completeness or accuracy of this\n";
		$report .= "data for legal purposes.\n\n";
		$report .= "Users should consult qualified legal counsel for\n";
		$report .= "compliance determinations specific to their situation.\n\n";

		$report .= "Generated: " . current_time( 'mysql' ) . "\n";
		$report .= "Report ID: " . self::get_local_site_id() . "\n";

		return $report;
	}

	// ═══════════════════════════════════════
	// EVIDENCE BUNDLE BUILDER
	// ═══════════════════════════════════════

	public function build_and_download_bundle(): void {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'megagovern_evidence_download' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'megagovern' ) );
		}

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'megagovern_manage_compliance' ) ) {
			wp_die( esc_html__( 'You do not have permission to download evidence bundles.', 'megagovern' ) );
		}

		$is_pro       = $this->is_pro();
		$retention    = self::get_retention_days();
		$domain       = wp_parse_url( site_url(), PHP_URL_HOST );
		$timestamp    = gmdate( 'Y-m-d-H-i-s' );
		$unique       = substr( md5( uniqid( '', true ) ), 0, 8 );
		$zip_filename = sprintf( 'mega-govern-evidence-%s-%s-%s.zip', sanitize_file_name( $domain ), $timestamp, $unique );
		$zip_filepath = get_temp_dir() . $zip_filename;

		$zip = new \ZipArchive();
		if ( $zip->open( $zip_filepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true ) {
			wp_die( esc_html__( 'Failed to initialize zip compressor.', 'megagovern' ) );
		}

		// ═══════════════════════════════════
		// 1. MANIFEST (Both Tiers)
		// ═══════════════════════════════════
		$manifest  = "═══════════════════════════════════════\n";
		$manifest .= "  MEGAGOVERN GOVERNANCE ARCHIVE\n";
		$manifest .= "═══════════════════════════════════════\n\n";
		$manifest .= "Target Domain:       " . $domain . "\n";
		$manifest .= "Generation Date:     " . current_time( 'mysql' ) . "\n";
		$manifest .= "Plugin Version:      " . MEGAGOVERN_VERSION . "\n";
		$manifest .= "Tier Status:         " . ( $is_pro ? "PRO (365-Day Extended Ledger)" : "FREE (90-Day Baseline Log)" ) . "\n";
		$manifest .= "Retention Window:    " . $retention . " days\n";
		$manifest .= "Site ID:             " . self::get_local_site_id() . "\n";
		$manifest .= "Total Anchored Logs: " . number_format_i18n( self::count_logs( $retention ) ) . "\n";
		$manifest .= "File Hash (SHA-256): " . hash( 'sha256', $domain . $timestamp ) . "\n";

		// LEGAL DISCLAIMER — BOTH TIERS
		$manifest .= "\n═══════════════════════════════════════\n";
		$manifest .= "  IMPORTANT LEGAL DISCLAIMER\n";
		$manifest .= "═══════════════════════════════════════\n";
		$manifest .= "This evidence bundle is a transparency and documentation\n";
		$manifest .= "toolkit output generated by the MegaGovern plugin. It\n";
		$manifest .= "does NOT constitute legal compliance certification,\n";
		$manifest .= "legal advice, or a guarantee of regulatory compliance.\n\n";
		$manifest .= "Legal compliance depends on your jurisdiction, your\n";
		$manifest .= "content, and your legal counsel. Meganova Agency makes\n";
		$manifest .= "no representations or warranties regarding the\n";
		$manifest .= "completeness or accuracy of this data for legal purposes.\n\n";
		$manifest .= "Users should consult qualified legal counsel for\n";
		$manifest .= "compliance determinations specific to their situation.\n";

		if ( $is_pro ) {
			$manifest .= "\n--- Bundle Contents ---\n";
			$manifest .= "01_Compliance_Summary/  TRUST_REPORT + Framework_Metrics + Executive_Report\n";
			$manifest .= "02_Audit_Trails/        Full declaration change history\n";
			$manifest .= "03_Public_Disclosures/  ai.txt + published AI usage policy\n";
			$manifest .= "04_Integrity_Proof/     Database checksums and entry hashes\n";
			$manifest .= "05_Content_Snapshots/   Content as it existed at time of declaration\n";
			$manifest .= "06_Media_Provenance/    Metadata captured at original upload time\n";
			$zip->addFromString( 'MANIFEST_README.txt', $manifest );
		} else {
			$manifest .= "\n--- Bundle Contents ---\n";
			$manifest .= "TRUST_REPORT.txt        Governance overview + compliance summary\n";
			$manifest .= "ai.txt                  Machine-readable transparency file\n";
			$manifest .= "classification_snapshot.csv  Current declaration status\n";
			$zip->addFromString( 'MANIFEST.txt', $manifest );
		}

		// ═══════════════════════════════════
		// 2. TRUST REPORT (Both Tiers)
		// ═══════════════════════════════════
		$trust_report = $this->generate_trust_report();

		if ( $is_pro ) {
			$zip->addFromString( '01_Compliance_Summary/TRUST_REPORT.txt', $trust_report );
		} else {
			$zip->addFromString( 'TRUST_REPORT.txt', $trust_report );
		}

		// ═══════════════════════════════════
		// 3. AI.TXT (Both Tiers)
		// ═══════════════════════════════════
		$aitxt_content = '';
		if ( class_exists( '\MegaGovern\AITxt' ) ) {
			$aitxt_content = AITxt::get_content();
		}

		if ( $is_pro ) {
			$zip->addFromString( '03_Public_Disclosures/ai.txt', $aitxt_content );
		} else {
			$zip->addFromString( 'ai.txt', $aitxt_content );
		}

		// ═══════════════════════════════════
		// 4. CLASSIFICATION / AUDIT DATA
		// ═══════════════════════════════════
		if ( $is_pro ) {
			// PRO: Structured folders.

			$metrics_csv = $this->generate_framework_metrics_csv();
			$zip->addFromString( '01_Compliance_Summary/Framework_Metrics.csv', $metrics_csv );

			if ( class_exists( '\MegaGovern\PDF_Engine' ) && method_exists( '\MegaGovern\PDF_Engine', 'build_summary_pdf' ) ) {
				$pdf_content = \MegaGovern\PDF_Engine::build_summary_pdf();
				if ( $pdf_content ) {
					$zip->addFromString( '01_Compliance_Summary/Executive_Report.pdf', $pdf_content );
				}
			}

			$timeline_csv = $this->compile_governance_csv( 365 );
			$zip->addFromString( '02_Audit_Trails/Governance_Timeline.csv', $timeline_csv );

			$policy_content = $this->get_ai_policy_text();
			if ( $policy_content ) {
				$zip->addFromString( '03_Public_Disclosures/AI_Usage_Policy.txt', $policy_content );
			}

			$hashes_json = $this->generate_integrity_hashes_json();
			$zip->addFromString( '04_Integrity_Proof/Database_Hashes.json', $hashes_json );

			// Content snapshots — one file per declaration event.
			$this->add_content_snapshots_to_zip( $zip, $retention );

			// Media provenance — one file per declared attachment.
			$this->add_media_provenance_to_zip( $zip );

		} else {
			// FREE: Flat structure — classification snapshot only.
			$snapshot_csv = $this->compile_classification_snapshot_csv( $retention );
			$zip->addFromString( 'classification_snapshot.csv', $snapshot_csv );
		}

		$zip->close();

		// ═══════════════════════════════════
		// 5. STREAM FILE TO BROWSER & EXIT
		// ═══════════════════════════════════
		$this->flush_file_to_browser( $zip_filepath, $zip_filename );
	}

	private function add_content_snapshots_to_zip( \ZipArchive $zip, int $days ): void {
		$snapshots = $this->get_content_snapshots( $days );

		if ( empty( $snapshots ) ) {
			$zip->addFromString(
				'05_Content_Snapshots/README.txt',
				"No content snapshots recorded yet. Snapshots are captured automatically\n" .
				"each time a declaration is made or changed, starting from plugin v1.0.5.\n"
			);
			return;
		}

		foreach ( $snapshots as $snap ) {
			$declared_date = gmdate( 'Y-m-d', strtotime( $snap['declared_at'] ) );
			$filename      = sprintf(
				'05_Content_Snapshots/%d_%s.html',
				(int) $snap['post_id'],
				sanitize_file_name( $declared_date )
			);

			$type_label = $this->map_declaration_type( (int) $snap['declaration_type'] );

			$html  = "<!DOCTYPE html><html><head><meta charset='utf-8'>";
			$html .= '<title>' . esc_html__( 'Content Snapshot', 'megagovern' ) . ' — Post #' . (int) $snap['post_id'] . '</title></head><body>';
			$html .= '<p><strong>' . esc_html__( 'Post ID', 'megagovern' ) . ':</strong> ' . (int) $snap['post_id'] . '</p>';
			$html .= '<p><strong>' . esc_html__( 'Declared As', 'megagovern' ) . ':</strong> ' . esc_html( $type_label ) . '</p>';
			$html .= '<p><strong>' . esc_html__( 'Declared At', 'megagovern' ) . ':</strong> ' . esc_html( $snap['declared_at'] ) . '</p>';
			$html .= '<p><strong>' . esc_html__( 'Content Hash (SHA-256)', 'megagovern' ) . ':</strong> ' . esc_html( $snap['content_hash'] ) . '</p>';
			$html .= '<hr><div>' . wp_kses_post( $snap['content_snapshot'] ) . '</div>';
			$html .= '</body></html>';

			$zip->addFromString( $filename, $html );
		}
	}

	private function add_media_provenance_to_zip( \ZipArchive $zip ): void {
		$records = $this->get_media_provenance_records();

		if ( empty( $records ) ) {
			$zip->addFromString(
				'06_Media_Provenance/README.txt',
				"No media provenance records found. Provenance metadata is captured\n" .
				"automatically at upload time, starting from plugin v1.0.5. Media\n" .
				"uploaded before this version will not have a provenance record.\n"
			);
			return;
		}

		foreach ( $records as $attachment_id => $metadata ) {
			$filename = sprintf( '06_Media_Provenance/%d_metadata.json', $attachment_id );
			$zip->addFromString( $filename, wp_json_encode( $metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		}
	}

	// ═══════════════════════════════════════
	// CSV GENERATORS
	// ═══════════════════════════════════════

	private function compile_classification_snapshot_csv( int $days ): string {
		global $wpdb;

		$csv = "Post ID,Title,Post Type,Declaration Status,Declared By,Declared At\n";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_type,
						pm_decl.meta_value as declaration,
						pm_by.meta_value as declared_by,
						pm_at.meta_value as declared_at
				 FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} pm_decl ON p.ID = pm_decl.post_id AND pm_decl.meta_key = '_megagovern_declaration'
				 LEFT JOIN {$wpdb->postmeta} pm_by ON p.ID = pm_by.post_id AND pm_by.meta_key = '_megagovern_declared_by'
				 LEFT JOIN {$wpdb->postmeta} pm_at ON p.ID = pm_at.post_id AND pm_at.meta_key = '_megagovern_declared_at'
				 WHERE p.post_status = 'publish'
				 AND p.post_modified > DATE_SUB(NOW(), INTERVAL %d DAY)
				 ORDER BY p.post_modified DESC",
				$days
			)
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				$declaration  = $row->declaration ?: 'undeclared';
				$declared_by  = '—';
				if ( $row->declared_by ) {
					$user        = get_userdata( $row->declared_by );
					$declared_by = $user ? $user->display_name : 'User #' . $row->declared_by;
				}
				$declared_at = $row->declared_at ?: '—';
				$csv        .= sprintf(
					"%d,\"%s\",%s,%s,\"%s\",%s\n",
					$row->ID,
					str_replace( '"', '""', $row->post_title ),
					$row->post_type,
					$declaration,
					str_replace( '"', '""', $declared_by ),
					$declared_at
				);
			}
		}

		return $csv;
	}

	private function compile_governance_csv( int $days ): string {
		global $wpdb;
		$table = $wpdb->prefix . 'mega_declaration_log';

		if ( ! class_exists( '\MegaGovern\Db' ) || ! method_exists( '\MegaGovern\Db', 'log_table_exists' ) ) {
			return "No log table found.\n";
		}

		if ( ! Db::log_table_exists() ) {
			return "No log table found.\n";
		}

		$csv = "Log ID,Post ID,Action,Declaration Type,User ID,Previous Type,Note,Logged At\n";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `%s` WHERE logged_at > DATE_SUB(NOW(), INTERVAL %d DAY) ORDER BY logged_at DESC",
				$table,
				$days
			)
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				$type_label = $this->map_declaration_type( $row->declaration_type );
				$prev_label = $row->previous_type ? $this->map_declaration_type( $row->previous_type ) : '—';
				$csv       .= sprintf(
					"%d,%d,%s,%s,%d,%s,\"%s\",%s\n",
					$row->id,
					$row->post_id,
					$row->action,
					$type_label,
					$row->user_id,
					$prev_label,
					str_replace( '"', '""', $row->note ?: '' ),
					$row->logged_at
				);
			}
		}

		return $csv;
	}

	private function generate_framework_metrics_csv(): string {
		$csv  = "# ═══════════════════════════════════════\n";
		$csv .= "# LEGAL DISCLAIMER\n";
		$csv .= "# ═══════════════════════════════════════\n";
		$csv .= "# This table reflects feature support within the MegaGovern\n";
		$csv .= "# plugin and does NOT constitute a legal compliance\n";
		$csv .= "# determination or legal advice. Consult qualified legal\n";
		$csv .= "# counsel for compliance determinations specific to your\n";
		$csv .= "# jurisdiction and use case.\n";
		$csv .= "# ═══════════════════════════════════════\n\n";
		$csv .= "Article,Jurisdiction,Requirement,Feature Support,Service Module,Status\n";

		$articles = [
			[ 'Art. 50 (1)', 'EU AI Act', 'Transparency for AI-generated content', 'Supported', 'Content Declaration + Labels', 'Supported' ],
			[ 'Art. 50 (2)', 'EU AI Act', 'Disclosure of AI-manipulated media', 'Partial', 'Media Declaration + C2PA Presence Check (Pro)', 'Partial' ],
			[ 'Art. 50 (3)', 'EU AI Act', 'Emotion recognition disclosure', 'Not Supported', 'Not in scope', 'Not Supported' ],
			[ 'Art. 50 (4)', 'EU AI Act', 'Machine-readable AI labeling', 'Supported', 'AI.txt Generator', 'Supported' ],
			[ 'Art. 52', 'EU AI Act', 'Transparency obligations for AI systems', 'Supported', 'Verification Page + Registry', 'Supported' ],
			[ 'CCPA/CPRA', 'US State (California)', 'Automated decision-making disclosure', 'Partial', 'Content Declaration', 'Partial' ],
			[ 'SB 942 / AB 853', 'US State (California)', 'Latent (file-embedded) AI disclosure', 'Partial', 'Media Provenance Capture (Pro)', 'Partial' ],
			[ 'Deep Synthesis Provisions', 'China', 'AI-generated content watermarking', 'Partial', 'Labels + C2PA Presence Check', 'Partial' ],
		];

		foreach ( $articles as $a ) {
			$csv .= sprintf(
				"\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
				$a[0], $a[1], $a[2], $a[3], $a[4], $a[5]
			);
		}

		return $csv;
	}

	// ═══════════════════════════════════════
	// UTILITY METHODS
	// ═══════════════════════════════════════

	private function get_ai_policy_text(): string {
		$policy_page_id = get_option( 'megagovern_policy_page_id', 0 );

		if ( $policy_page_id ) {
			$page = get_post( $policy_page_id );
			if ( $page && 'publish' === $page->post_status ) {
				return wp_strip_all_tags( $page->post_content );
			}
		}

		$intro = get_option( 'megagovern_policy_intro', '' );
		if ( $intro ) {
			return wp_strip_all_tags( $intro );
		}

		return __( 'No AI usage policy published yet.', 'megagovern' );
	}

	private function generate_integrity_hashes_json(): string {
		global $wpdb;

		$data = [
			'generated_at'   => current_time( 'mysql' ),
			'site_url'       => site_url(),
			'site_id'        => self::get_local_site_id(),
			'plugin_version' => MEGAGOVERN_VERSION,
			'hashes'         => [],
		];

		$log_table = $wpdb->prefix . 'mega_declaration_log';

		if ( class_exists( '\MegaGovern\Db' ) && method_exists( '\MegaGovern\Db', 'log_table_exists' ) && Db::log_table_exists() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$log_count = $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM `%s`", $log_table )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$latest_log = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM `%s` ORDER BY logged_at DESC LIMIT 1", $log_table )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$checksum_row = $wpdb->get_row(
				$wpdb->prepare( "CHECKSUM TABLE `%s`", $log_table )
			);

			$data['hashes']['declaration_log'] = [
				'table_name'        => $log_table,
				'total_rows'        => (int) $log_count,
				'latest_entry_id'   => $latest_log ? (int) $latest_log->id : 0,
				'latest_entry_hash' => $latest_log ? hash( 'sha256', wp_json_encode( $latest_log ) ) : null,
				'table_checksum'    => $checksum_row ? (int) $checksum_row->Checksum : 0,
			];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$postmeta_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
				'_megagovern_declaration'
			)
		);

		$data['hashes']['declaration_postmeta'] = [
			'total_declarations' => (int) $postmeta_count,
			'meta_key'           => '_megagovern_declaration',
		];

		return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	private function flush_file_to_browser( string $filepath, string $filename ): void {
		if ( ! file_exists( $filepath ) ) {
			wp_die( esc_html__( 'Evidence bundle file not found.', 'megagovern' ) );
		}

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . basename( $filename ) . '"' );
		header( 'Content-Length: ' . filesize( $filepath ) );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		$content = $wp_filesystem->get_contents( $filepath );
		if ( false !== $content ) {
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$wp_filesystem->delete( $filepath );

		exit;
	}
}